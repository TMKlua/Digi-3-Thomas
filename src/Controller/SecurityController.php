<?php

namespace App\Controller;

use App\Entity\User;
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
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\AppCustomAuthenticator;

class SecurityController extends AbstractController
{
    private RateLimiterFactory $loginLimiter;
    private LoggerInterface $logger;
    private AppCustomAuthenticator $authenticator;
    private PermissionService $permissionService;
    private const TOKEN_LENGTH = 40;
    private const TOKEN_EXPIRATION = '+1 hour';

    public function __construct(
        RateLimiterFactory $loginLimiter,
        LoggerInterface $logger,
        AppCustomAuthenticator $authenticator,
        PermissionService $permissionService
    ) {
        $this->loginLimiter = $loginLimiter;
        $this->logger = $logger;
        $this->authenticator = $authenticator;
        $this->permissionService = $permissionService;
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
        // Vérifier si l'utilisateur a le droit de créer des utilisateurs
        if (!$this->permissionService->canManageUsers()) {
            return $this->createJsonResponse(false, 'Vous n\'avez pas les permissions nécessaires pour créer des utilisateurs.');
        }

        try {
            $submittedToken = $request->request->get('csrf_token');
            if (!$this->isCsrfTokenValid('authenticate', $submittedToken)) {
                return $this->createJsonResponse(false, 'Token invalide');
            }

            $firstName = trim(strip_tags($request->request->get('first_name', '')));
            $lastName = trim(strip_tags($request->request->get('last_name', '')));
            $email = trim(strip_tags($request->request->get('email', '')));
            $plainPassword = $request->request->get('password');
            $role = $request->request->get('role', User::ROLE_USER);

            // Validation des champs requis
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

            // Validation du rôle
            if (!in_array($role, User::VALID_ROLES, true)) {
                return $this->createJsonResponse(false, 'Rôle invalide');
            }

            // Vérifier si l'utilisateur a le droit d'attribuer ce rôle
            if (!$this->permissionService->canManageRoles()) {
                return $this->createJsonResponse(false, 'Vous n\'avez pas les permissions nécessaires pour attribuer ce rôle.');
            }

            // Validation du mot de passe
            $passwordErrors = $this->validatePassword($plainPassword);
            if (!empty($passwordErrors)) {
                return $this->createJsonResponse(false, implode(', ', $passwordErrors));
            }

            // Vérification de l'unicité de l'email
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['userEmail' => $email]);
            if ($existingUser) {
                return $this->createJsonResponse(false, 'Cet email est déjà utilisé');
            }

            // Création de l'utilisateur
            $user = User::create(
                $passwordHasher,
                $firstName,
                $lastName,
                $email,
                $plainPassword,
                $role
            );

            try {
                $entityManager->persist($user);
                $entityManager->flush();
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la persistance : ' . $e->getMessage());
                return $this->createJsonResponse(false, 'Erreur lors de la création du compte');
            }

            // Authentification automatique
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

    #[Route('/auth/reset-password', name: 'app_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        TokenGeneratorInterface $tokenGenerator
    ): Response {
        // Vérifier si l'utilisateur a le droit de réinitialiser les mots de passe
        if (!$this->permissionService->hasPermission('manage_users')) {
            return $this->createJsonResponse(false, 'Vous n\'avez pas les permissions nécessaires pour réinitialiser les mots de passe.');
        }

        $data = json_decode($request->getContent(), true);
        $userEmail = trim(strip_tags($data['email'] ?? ''));

        if (empty($userEmail)) {
            return $this->createJsonResponse(false, 'Email requis');
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

    #[Route('/auth/reset-password/{token}', name: 'app_reset_password_confirm', methods: ['POST'])]
    public function resetPasswordConfirm(
        string $token,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Vérifier si l'utilisateur a le droit de réinitialiser les mots de passe
        if (!$this->permissionService->hasPermission('manage_users')) {
            return $this->createJsonResponse(false, 'Vous n\'avez pas les permissions nécessaires pour réinitialiser les mots de passe.');
        }

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
        $this->logger->debug('Hash du mot de passe généré:', ['hashedPassword' => !empty($hashedPassword)]);
        $user->setPassword($hashedPassword);
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->createJsonResponse(true, 'Votre mot de passe a été réinitialisé avec succès.');
    }
}
