<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Enum\UserRole;
use App\Form\Auth\LoginFormType;
use App\Form\Auth\RegisterFormType;
use App\Form\Auth\ResetPasswordRequestFormType;
use App\Form\Auth\ResetPasswordFormType;
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
    private const TOKEN_EXPIRATION = '+1 hour';

    public function __construct(
        private readonly RateLimiterFactory $loginLimiter,
        private readonly LoggerInterface $logger,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    /**
     * Page d'authentification
     */
    #[Route('', name: 'app_auth', methods: ['GET', 'POST'])]
    public function auth(
        AuthenticationUtils $authenticationUtils, 
        LoggerInterface $logger
    ): Response {
        // Si l'utilisateur est déjà connecté, le rediriger vers le tableau de bord
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Créer les formulaires
        $loginForm = $this->createForm(LoginFormType::class);
        $registrationForm = $this->createForm(RegisterFormType::class);
        
        // Récupérer l'erreur d'authentification s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        
        // Journaliser l'erreur pour le débogage
        if ($error) {
            $logger->debug('Erreur d\'authentification', [
                'message' => $error->getMessage(),
                'class' => get_class($error),
            ]);
        }

        // Rendre le template d'authentification
        return $this->render('auth/auth.html.twig', [
            'error' => $error ? $error->getMessage() : null,
            'last_username' => $lastUsername,
            'login_form' => $loginForm->createView(),
            'registration_form' => $registrationForm->createView(),
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
        AppCustomAuthenticator $authenticator
    ): Response {
        $form = $this->createForm(RegisterFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            // Vérifier si l'utilisateur existe déjà
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['userEmail' => $data['email']]);
            if ($existingUser) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->redirectToRoute('app_auth');
            }

            // Validation du mot de passe (ajout de validation basique)
            if (strlen($data['password']) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->redirectToRoute('app_auth');
            }

            try {
                // Créer le nouvel utilisateur
                $user = new User();
                $user->setUserFirstName($data['first_name']);
                $user->setUserLastName($data['last_name']);
                $user->setUserEmail($data['email']);
                $user->setUserPassword($passwordHasher->hashPassword($user, $data['password']));
                $user->setUserRole(UserRole::USER);
                $user->setUserCreatedAt(new \DateTimeImmutable());
                $user->setUserUpdatedAt(new \DateTimeImmutable());

                // Persister l'utilisateur
                $entityManager->persist($user);
                $entityManager->flush();

                // Connecter automatiquement l'utilisateur
                return $userAuthenticator->authenticateUser(
                    $user,
                    $authenticator,
                    $request
                );
            } catch (\Exception $e) {
                // Journaliser l'erreur
                $this->logger->error('Erreur lors de l\'inscription: ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer.');
                return $this->redirectToRoute('app_auth');
            }
        }

        // En cas d'erreur, rediriger vers la page d'authentification
        return $this->redirectToRoute('app_auth');
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
     * Demande de réinitialisation du mot de passe
     */
    #[Route('/reset-password-request', name: 'app_reset_password_request', methods: ['GET', 'POST'])]
    public function resetPasswordRequest(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        // Si l'utilisateur est déjà connecté, le rediriger vers le tableau de bord
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = $data['email'];
            
            try {
                $user = $entityManager->getRepository(User::class)->findOneBy(['userEmail' => $email]);

                // Même si l'utilisateur n'existe pas, on renvoie un message de succès pour des raisons de sécurité
                if ($user) {
                    // Générer un token de réinitialisation
                    $token = bin2hex(random_bytes(32));
                    $user->setResetToken($token);
                    $user->setResetTokenExpiresAt(new \DateTimeImmutable(self::TOKEN_EXPIRATION));
                    $entityManager->flush();

                    // Envoyer l'email de réinitialisation
                    $resetEmail = (new TemplatedEmail())
                        ->to($user->getUserEmail())
                        ->subject('Réinitialisation de votre mot de passe')
                        ->htmlTemplate('emails/reset_password.html.twig')
                        ->context([
                            'resetUrl' => $this->generateUrl('app_reset_password_confirm', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
                            'user' => $user,
                            'expiration' => '1 heure'
                        ]);

                    $mailer->send($resetEmail);
                    
                    // Journaliser la demande de réinitialisation
                    $this->logger->info('Demande de réinitialisation de mot de passe', [
                        'email' => $email,
                        'ip' => $request->getClientIp(),
                    ]);
                }

                $this->addFlash('success', 'Si votre email est enregistré, vous recevrez un lien de réinitialisation.');
                return $this->redirectToRoute('app_auth');
            } catch (\Exception $e) {
                // Journaliser l'erreur
                $this->logger->error('Erreur lors de la demande de réinitialisation: ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue. Veuillez réessayer ultérieurement.');
                return $this->redirectToRoute('app_auth');
            }
        }

        return $this->render('auth/reset_password_request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Page de confirmation de réinitialisation du mot de passe
     */
    #[Route('/reset-password/{token}', name: 'app_reset_password_confirm', methods: ['GET', 'POST'])]
    public function resetPasswordConfirm(
        string $token,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        // Vérifier si le token est valide et non expiré
        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('error', 'Le lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_auth');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $form->getData();
                
                // Mettre à jour le mot de passe
                $user->setUserPassword($passwordHasher->hashPassword($user, $data['password']));
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);
                $user->setUserUpdatedAt(new \DateTimeImmutable());
                $entityManager->flush();

                // Journaliser la réinitialisation réussie
                $this->logger->info('Réinitialisation de mot de passe réussie', [
                    'user_id' => $user->getId(),
                    'email' => $user->getUserEmail(),
                    'ip' => $request->getClientIp(),
                ]);

                $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
                return $this->redirectToRoute('app_auth');
            } catch (\Exception $e) {
                // Journaliser l'erreur
                $this->logger->error('Erreur lors de la réinitialisation du mot de passe: ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue. Veuillez réessayer ultérieurement.');
            }
        }

        return $this->render('auth/reset_password_confirm.html.twig', [
            'form' => $form->createView(),
            'token' => $token
        ]);
    }

    /**
     * Redirection vers la demande de réinitialisation de mot de passe
     */
    #[Route('/reset-password', name: 'app_reset_password', methods: ['GET'])]
    public function resetPasswordRedirect(): Response
    {
        return $this->redirectToRoute('app_reset_password_request');
    }
}
