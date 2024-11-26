<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\AppCustomAuthenticator;

class SecurityController extends AbstractController
{
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $entityManager;
    private UserAuthenticatorInterface $userAuthenticator;
    private AppCustomAuthenticator $authenticator;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        UserAuthenticatorInterface $userAuthenticator,
        AppCustomAuthenticator $authenticator
    ) {
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
        $this->userAuthenticator = $userAuthenticator;
        $this->authenticator = $authenticator;
    }

    #[Route('/auth', name: 'app_auth')]
    public function authPage(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        // Inscription
        if ($request->isMethod('POST') && $request->request->get('action') === 'register') {
            $user = new User();
            $user->setUserFirstName($request->request->get('first_name'));
            $user->setUserLastName($request->request->get('last_name'));
            $user->setUserEmail($request->request->get('email'));

            // Rôle par défaut
            $user->setUserRole('ROLE_USER');

            // Date d'inscription
            $user->setUserDateFrom(new \DateTime());

            // Avatar par défaut
            $user->setUserAvatar('img/account/default-avatar.png');

            // Hashage du mot de passe
            $plainPassword = $request->request->get('password');
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            try {
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                // Authentification automatique
                return $this->userAuthenticator->authenticateUser(
                    $user,
                    $this->authenticator,
                    $request
                );
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('error', 'Un compte existe déjà avec cet email.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du compte.');
            }
        }

        // Connexion
        if ($request->isMethod('POST') && $request->request->get('action') === 'login') {
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            // Recherche de l'utilisateur par email
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['userEmail' => $email]);

            if ($user && $this->passwordHasher->isPasswordValid($user, $password)) {
                // Authentification de l'utilisateur
                return $this->userAuthenticator->authenticateUser(
                    $user,
                    $this->authenticator,
                    $request
                );
            } else {
                $this->addFlash('error', 'Email ou mot de passe incorrect.');
            }
        }

        // Récupérer la dernière tentative de connexion
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/auth.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode est interceptée par le firewall pour gérer la déconnexion
        throw new \LogicException('Ce point ne devrait jamais être atteint.');
    }
}
