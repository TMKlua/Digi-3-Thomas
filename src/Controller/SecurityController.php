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
        if ($request->isMethod('POST') && $request->request->get('action') === 'register') {
            $user = new User();
            $user->setName($request->request->get('name'));
            $user->setEmail($request->request->get('email'));
            
            // Gestion du mot de passe
            $plainPassword = $request->request->get('password');
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
        
            try {
                // Enregistrement de l'utilisateur dans la base de données
                $this->entityManager->persist($user);
                $this->entityManager->flush();
        
                // Authentification automatique de l'utilisateur après l'inscription
                return $this->userAuthenticator->authenticateUser(
                    $user,
                    $this->authenticator,
                    $request
                );
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('error', 'Un compte existe déjà avec l\'adresse email "' . $user->getEmail() . '".');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur s\'est produite lors de la création de votre compte.');
            }
        }
        

        // Gestion de la connexion
        if ($request->isMethod('POST') && $request->request->get('action') === 'login') {
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            // Recherche de l'utilisateur
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user && $this->passwordHasher->isPasswordValid($user, $password)) {
                // Authentification de l'utilisateur
                return $this->userAuthenticator->authenticateUser(
                    $user,
                    $this->authenticator,
                    $request
                );
            } else {
                // Affichage d'une erreur si les informations sont incorrectes
                $this->addFlash('error', 'Identifiants incorrects.');
            }
        }

        // Gestion des erreurs de connexion
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/auth.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
