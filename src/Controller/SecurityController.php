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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

/**
 * Contrôleur gérant l'authentification et la gestion des utilisateurs
 */
class SecurityController extends AbstractController
{
    private RateLimiterFactory $loginLimiter;
    // Longueur du token pour la réinitialisation du mot de passe
    private const TOKEN_LENGTH = 40;
    // Durée de validité du token de réinitialisation
    private const TOKEN_EXPIRATION = '+1 hour';

    public function __construct(RateLimiterFactory $loginLimiter)
    {
        $this->loginLimiter = $loginLimiter;
    }

    /**
     * Génère un token sécurisé pour la réinitialisation du mot de passe
     */
    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }

    /**
     * Crée une réponse JSON standardisée
     */
    private function createJsonResponse(bool $success, string $message = '', array $data = [], int $status = Response::HTTP_OK): Response
    {
        return $this->json(array_merge(['success' => $success, 'message' => $message], $data), $status);
    }

    /**
     * Page de connexion et traitement du formulaire de connexion
     */
    #[Route('/auth', name: 'app_auth', methods: ['POST', 'GET'])]
    public function auth(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // Redirection si déjà connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Protection contre le brute force
        $limiter = $this->loginLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->render('auth/auth.html.twig', [
                'error' => 'Trop de tentatives de connexion. Veuillez réessayer dans 5 minutes.',
                'last_username' => $authenticationUtils->getLastUsername(),
            ]);
        }

        // Gestion des erreurs de connexion
        $error = $authenticationUtils->getLastAuthenticationError();
        $errorMessage = $error ? 'Identifiants incorrects.' : null;

        return $this->render('auth/auth.html.twig', [
            'error' => $errorMessage,
            'last_username' => $authenticationUtils->getLastUsername(),
        ]);
    }

    /**
     * Valide le format et la complexité du mot de passe
     */
    private function validatePassword(string $password): array
    {
        $errors = [];
        $constraints = [
            'minLength' => 8,
            'requireSpecialChar' => true,
            'requireNumber' => true,
            'requireUppercase' => true
        ];

        if (strlen($password) < $constraints['minLength']) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères';
        }
        if ($constraints['requireSpecialChar'] && !preg_match('/[^a-zA-Z\d]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial';
        }
        if ($constraints['requireNumber'] && !preg_match('/\d/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un chiffre';
        }
        if ($constraints['requireUppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une majuscule';
        }

        return $errors;
    }

    /**
     * Valide le format de l'email
     */
    private function validateEmail(string $email): array
    {
        $errors = [];
        $validator = Validation::createValidator();
        $emailConstraints = new Assert\Email([
            'message' => 'L\'adresse email "{{ value }}" n\'est pas valide.',
            'mode' => Assert\Email::VALIDATION_MODE_STRICT
        ]);

        $violations = $validator->validate($email, $emailConstraints);
        if (count($violations) > 0) {
            $errors[] = $violations[0]->getMessage();
        }

        return $errors;
    }

    /**
     * Inscription d'un nouvel utilisateur
     */
    #[Route('/auth/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        UserAuthenticatorInterface $userAuthenticator,
        #[Autowire(service: 'security.authenticator.form_login.main')] AuthenticatorInterface $authenticator
    ): Response {
        // Protection CSRF
        $submittedToken = $request->request->get('csrf_token');
        if (!$this->isCsrfTokenValid('register', $submittedToken)) {
            return $this->createJsonResponse(false, 'Token invalide');
        }

        // Nettoyage et validation des données
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
            if (empty($value)) {
                return $this->createJsonResponse(false, sprintf('Le champ %s est requis', str_replace('_', ' ', $field)));
            }
        }

        $emailErrors = $this->validateEmail($email);
        if (!empty($emailErrors)) {
            return $this->createJsonResponse(false, $emailErrors[0]);
        }

        $passwordErrors = $this->validatePassword($plainPassword);
        if (!empty($passwordErrors)) {
            return $this->createJsonResponse(false, implode(', ', $passwordErrors));
        }

        // Création et enregistrement de l'utilisateur
        $user = new User();
        $user->setUserFirstName($firstName)
            ->setUserLastName($lastName)
            ->setUserEmail($email);

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->createJsonResponse(false, (string) $errors);
        }

        $existingUser = $entityManager->getRepository(User::class)->findOneBy([
            'userEmail' => $email
        ]);
        if ($existingUser) {
            return $this->createJsonResponse(false, 'Cet email est déjà utilisé');
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        $userAuthenticator->authenticateUser($user, $authenticator, $request);

        return $this->createJsonResponse(true, 'Inscription réussie ! Vous allez être redirigé.', [
            'redirect' => $this->generateUrl('app_dashboard')
        ]);
    }

    /**
     * Déconnexion de l'utilisateur
     */
    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // La déconnexion est gérée par le firewall
    }

    /**
     * Demande de réinitialisation du mot de passe
     */
    #[Route('/auth/reset-password', name: 'app_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        TokenGeneratorInterface $tokenGenerator
    ): Response {
        $data = json_decode($request->getContent(), true);
        $userEmail = trim(strip_tags($data['email'] ?? ''));

        if (empty($userEmail)) {
            return $this->createJsonResponse(false, 'Email requis');
        }

        $emailErrors = $this->validateEmail($userEmail);
        if (!empty($emailErrors)) {
            return $this->createJsonResponse(false, $emailErrors[0]);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy([
            'userEmail' => $userEmail
        ]);

        if (!$user) {
            return $this->createJsonResponse(false, 'Aucun compte trouvé avec cet email');
        }

        $resetToken = $this->generateSecureToken();
        $user->setResetToken($resetToken);
        $user->setResetTokenExpiresAt(new \DateTime(self::TOKEN_EXPIRATION));

        $entityManager->persist($user);
        $entityManager->flush();

        $resetLink = $this->generateUrl('app_reset_password_confirm', ['token' => $resetToken], 0);

        $email = (new TemplatedEmail())
            ->from('louisbousquet13@gmail.com')
            ->to($user->getUserEmail())
            ->subject('Digi-3 - Réinitialisation de votre mot de passe')
            ->html(
                "<h1>Réinitialisation de votre mot de passe</h1>" .
                "<p>Bonjour,</p>" .
                "<p>Une demande de réinitialisation de mot de passe a été effectuée pour votre compte. " .
                "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</p>" .
                "<p>Pour réinitialiser votre mot de passe, cliquez sur le lien suivant :</p>" .
                "<a href='" . $resetLink . "'>Réinitialiser mon mot de passe</a>" .
                "<p>Ce lien expirera dans 1 heure.</p>" .
                "<p>L'équipe Digi-3</p>"
            );

        try {
            $mailer->send($email);
            return $this->createJsonResponse(true, 'Si un compte existe avec cet email, vous recevrez un lien de réinitialisation.');
        } catch (\Exception $e) {
            return $this->createJsonResponse(false, 'Erreur lors de l\'envoi de l\'email', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Confirmation de la réinitialisation du mot de passe avec le token
     */
    #[Route('/auth/reset-password/{token}', name: 'app_reset_password_confirm', methods: ['POST'])]
    public function resetPasswordConfirm(
        string $token,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $entityManager->getRepository(User::class)->findOneBy([
            'resetToken' => $token
        ]);

        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTime()) {
            return $this->createJsonResponse(false, 'Ce lien de réinitialisation est invalide ou a expiré.');
        }

        $plainPassword = $request->request->get('password');
        $passwordErrors = $this->validatePassword($plainPassword);

        if (!empty($passwordErrors)) {
            return $this->createJsonResponse(false, implode(', ', $passwordErrors));
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->createJsonResponse(true, 'Votre mot de passe a été réinitialisé avec succès.');
    }
}
