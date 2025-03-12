<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Enum\UserRole;
use App\Service\PermissionService;
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
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\AppCustomAuthenticator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/auth')]
class SecurityController extends AbstractController
{
    private const TOKEN_LENGTH = 40;
    private const TOKEN_EXPIRATION = '+1 hour';

    public function __construct(
        private readonly RateLimiterFactory $loginLimiter,
        private readonly LoggerInterface $logger,
        private readonly AppCustomAuthenticator $authenticator,
        private readonly PermissionService $permissionService,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    /**
     * Page d'authentification
     */
    #[Route('', name: 'app_auth', methods: ['GET', 'POST'])]
    public function auth(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // Si l'utilisateur est déjà connecté, le rediriger vers le tableau de bord
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Limiter les tentatives de connexion
        $limiter = $this->loginLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->render('auth/auth.html.twig', [
                'error' => 'Trop de tentatives de connexion. Veuillez réessayer dans 5 minutes.',
                'last_username' => $authenticationUtils->getLastUsername(),
            ]);
        }

        // Récupérer l'erreur d'authentification s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        $errorMessage = null;
        
        if ($error) {
            $this->logger->warning('Erreur d\'authentification: ' . $error->getMessage());
            $errorMessage = $error->getMessage();
        }

        // Générer un nouveau token CSRF pour le formulaire
        $csrfToken = $this->csrfTokenManager->getToken('authenticate')->getValue();

        // Rendre le template d'authentification
        return $this->render('auth/auth.html.twig', [
            'error' => $errorMessage,
            'last_username' => $authenticationUtils->getLastUsername(),
            'csrf_token' => $csrfToken,
        ]);
    }

    /**
     * Inscription d'un nouvel utilisateur
     */
    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        UserAuthenticatorInterface $userAuthenticator,
    ): Response {
        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('register', $submittedToken)) {
            return $this->json([
                'success' => false,
                'message' => 'Token CSRF invalide.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer les données du formulaire
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $firstName = $request->request->get('first_name');
        $lastName = $request->request->get('last_name');

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['userEmail' => $email]);
        if ($existingUser) {
            return $this->json([
                'success' => false,
                'message' => 'Cet email est déjà utilisé.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Valider le mot de passe
        $passwordValidation = $this->validatePassword($password);
        if (!$passwordValidation['valid']) {
            return $this->json([
                'success' => false,
                'message' => $passwordValidation['message']
            ], Response::HTTP_BAD_REQUEST);
        }

        // Créer le nouvel utilisateur en utilisant la méthode statique create
        $user = User::create(
            $passwordHasher,
            $firstName,
            $lastName,
            $email,
            $password,
            UserRole::USER
        );

        // Persister l'utilisateur
        $entityManager->persist($user);
        $entityManager->flush();

        // Connecter automatiquement l'utilisateur
        return $userAuthenticator->authenticateUser(
            $user,
            $this->authenticator,
            $request
        );
    }

    /**
     * Déconnexion
     */
    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // Cette méthode peut rester vide, la déconnexion est gérée par le firewall
        throw new \LogicException('Cette méthode ne devrait jamais être appelée.');
    }

    /**
     * Réinitialisation du mot de passe
     */
    #[Route('/reset-password', name: 'app_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
    ): Response {
        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('reset_password', $submittedToken)) {
            return $this->json([
                'success' => false,
                'message' => 'Token CSRF invalide.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $email = $request->request->get('email');
        $user = $entityManager->getRepository(User::class)->findOneBy(['userEmail' => $email]);

        // Même si l'utilisateur n'existe pas, on renvoie un message de succès pour des raisons de sécurité
        if (!$user) {
            return $this->json([
                'success' => true,
                'message' => 'Si votre email est enregistré, vous recevrez un lien de réinitialisation.'
            ]);
        }

        // Générer un token de réinitialisation
        $token = $this->generateSecureToken();
        $user->setResetToken($token);
        $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        $entityManager->flush();

        // Envoyer l'email de réinitialisation
        $email = (new TemplatedEmail())
            ->to($user->getUserEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context([
                'resetUrl' => $this->generateUrl('app_reset_password_confirm', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
                'user' => $user,
                'expiration' => '1 heure'
            ]);

        $mailer->send($email);

        return $this->json([
            'success' => true,
            'message' => 'Si votre email est enregistré, vous recevrez un lien de réinitialisation.'
        ]);
    }

    /**
     * Confirmation de réinitialisation du mot de passe
     */
    #[Route('/reset-password/{token}', name: 'app_reset_password_confirm', methods: ['GET', 'POST'])]
    public function resetPasswordConfirm(
        string $token,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Vérifier si le token existe
        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);
        
        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            return $this->render('auth/reset_password_error.html.twig', [
                'error' => 'Ce lien de réinitialisation est invalide ou a expiré.'
            ]);
        }

        // Si c'est une requête POST, traiter le formulaire
        if ($request->isMethod('POST')) {
            $submittedToken = $request->request->get('_csrf_token');
            if (!$this->isCsrfTokenValid('reset_password_confirm', $submittedToken)) {
                return $this->render('auth/reset_password_confirm.html.twig', [
                    'token' => $token,
                    'error' => 'Token CSRF invalide.'
                ]);
            }

            $password = $request->request->get('password');
            
            // Valider le mot de passe
            $passwordValidation = $this->validatePassword($password);
            if (!$passwordValidation['valid']) {
                return $this->render('auth/reset_password_confirm.html.twig', [
                    'token' => $token,
                    'error' => $passwordValidation['message']
                ]);
            }

            // Mettre à jour le mot de passe
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            $entityManager->flush();

            // Rediriger vers la page de connexion
            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
            return $this->redirectToRoute('app_auth');
        }

        // Afficher le formulaire de réinitialisation
        return $this->render('auth/reset_password_confirm.html.twig', [
            'token' => $token
        ]);
    }

    /**
     * Valide un mot de passe selon les critères de sécurité
     */
    private function validatePassword(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une lettre majuscule.';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une lettre minuscule.';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
        }
        
        if (empty($errors)) {
            return ['valid' => true];
        }
        
        return [
            'valid' => false,
            'message' => implode(' ', $errors)
        ];
    }

    /**
     * Génère un token sécurisé pour la réinitialisation de mot de passe
     */
    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
