<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\AppCustomAuthenticator;
use Symfony\Component\Mime\Email;

class SecurityController extends AbstractController
{
    private RateLimiterFactory $loginLimiter;
    private LoggerInterface $logger;
    private AppCustomAuthenticator $authenticator;
    private const TOKEN_LENGTH = 40;
    private const TOKEN_EXPIRATION = '+1 hour';

    public function __construct(
        RateLimiterFactory $loginLimiter,
        LoggerInterface $logger,
        AppCustomAuthenticator $authenticator
    ) {
        $this->loginLimiter = $loginLimiter;
        $this->logger = $logger;
        $this->authenticator = $authenticator;
    }

    private function validatePassword(string $password): array
    {
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une majuscule';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une minuscule';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un chiffre';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial';
        }
        return $errors;
    }

    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }

    private function createJsonResponse(bool $success, string $message = '', array $data = [], int $status = Response::HTTP_OK): Response
    {
        return $this->json(array_merge(['success' => $success, 'message' => $message], $data), $status);
    }

    #[Route('/auth', name: 'app_auth', methods: ['POST', 'GET'])]
    public function auth(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $limiter = $this->loginLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->render('auth/auth.html.twig', [
                'error' => 'Trop de tentatives de connexion. Veuillez réessayer dans 5 minutes.',
                'last_username' => $authenticationUtils->getLastUsername(),
            ]);
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $errorMessage = $error ? 'Identifiants incorrects.' : null;

        return $this->render('auth/auth.html.twig', [
            'error' => $errorMessage,
            'last_username' => $authenticationUtils->getLastUsername(),
        ]);
    }

    #[Route('/auth/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        UserAuthenticatorInterface $userAuthenticator,
        Security $security
    ): Response {
        try {
            $submittedToken = $request->request->get('csrf_token');
            if (!$this->isCsrfTokenValid('authenticate', $submittedToken)) {
                return $this->createJsonResponse(false, 'Token invalide');
            }

            $firstName = trim(strip_tags($request->request->get('first_name', '')));
            $lastName = trim(strip_tags($request->request->get('last_name', '')));
            $email = trim(strip_tags($request->request->get('email', '')));
            $plainPassword = $request->request->get('password');

            $requiredFields = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => $plainPassword
            ];

            foreach ($requiredFields as $field => $value) {
                $this->logger->debug("Champ {$field}: " . ($value ?: 'vide'));
                if (empty($value)) {
                    return $this->createJsonResponse(false, sprintf('Le champ %s est requis', str_replace('_', ' ', $field)));
                }
            }

            $passwordErrors = $this->validatePassword($plainPassword);
            if (!empty($passwordErrors)) {
                return $this->createJsonResponse(false, implode(', ', $passwordErrors));
            }

            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['userEmail' => $email]);
            if ($existingUser) {
                return $this->createJsonResponse(false, 'Cet email est déjà utilisé');
            }

            $hashedPassword = $passwordHasher->hashPassword(new User(), $plainPassword);

            $user = User::create(
                $firstName,
                $lastName,
                $email,
                $hashedPassword
            );

            $this->logger->debug('Données utilisateur avant persist:', [
                'firstName' => $user->getUserFirstName(),
                'lastName' => $user->getUserLastName(),
                'email' => $user->getUserEmail(),
                'hasPassword' => !empty($user->getPassword()),
                'dateFrom' => $user->getUserDateFrom()->format('Y-m-d H:i:s'),
                'avatar' => $user->getUserAvatar(),
                'role' => $user->getUserRole()
            ]);

            try {
                $entityManager->persist($user);
                $entityManager->flush();
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la persistance : ' . $e->getMessage());
                return $this->createJsonResponse(false, 'Erreur lors de la création du compte');
            }

            try {
                $userAuthenticator->authenticateUser(
                    $user,
                    $this->authenticator,
                    $request
                );
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de l\'authentification : ' . $e->getMessage());
            }

            return $this->json([
                'success' => true,
                'message' => 'Inscription réussie !',
                'redirect' => $this->generateUrl('app_dashboard')
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'inscription : ' . $e->getMessage());
            return $this->createJsonResponse(false, 'Une erreur est survenue lors de l\'inscription');
        }
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request, EntityManagerInterface $entityManager, TokenGeneratorInterface $tokenGenerator, MailerInterface $mailer): Response
    {
        // Utiliser 'email' car c'est le nom du champ dans votre formulaire HTML
        $email = $request->request->get('email');
        
        if (!$email) {
            return $this->json([
                'success' => false,
                'message' => 'Email requis'
            ]);
        }
    
        // Utiliser 'userEmail' car c'est le nom de la propriété dans votre entité User
        $user = $entityManager->getRepository(User::class)->findOneBy(['userEmail' => $email]);
    
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Aucun compte trouvé avec cet email.'
            ]);
        }
    
        // Générer un token
        $token = $tokenGenerator->generateToken();
        $user->setResetToken($token);
        $entityManager->persist($user);
        $entityManager->flush();
    
        try {
            // Utiliser getUserEmail() pour obtenir l'adresse email de l'utilisateur
            $emailMessage = (new Email())
                ->from('no-reply@digi-3.com')
                ->to($user->getUserEmail())  // Modifié ici pour utiliser getUserEmail()
                ->subject('Réinitialisation de votre mot de passe')
                ->text("Pour réinitialiser votre mot de passe, cliquez sur le lien suivant : \n" . $this->generateUrl('app_reset_password', ['token' => $token], true));
    
            $mailer->send($emailMessage);
    
            return $this->json([
                'success' => true,
                'message' => 'Un email de réinitialisation a été envoyé.'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['POST'])]
    public function resetPassword($token, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Lien invalide ou expiré.');
            return $this->redirectToRoute('app_auth');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password');
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $user->setResetToken(null); // Supprimer le token après utilisation
            $entityManager->flush();

            $this->addFlash('success', 'Mot de passe réinitialisé avec succès.');
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('security/reset_password.html.twig', ['token' => $token]);
    }
}
