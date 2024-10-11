<?php

namespace App\Controller;

use App\Entity\User; 
use App\Form\LoginForm;
use App\Form\RegistrationForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthController extends AbstractController
{
    #[Route('/auth', name: 'app_auth')]
    public function authPage(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Formulaire de connexion
        $loginForm = $this->createForm(LoginForm::class);
        $loginForm->handleRequest($request);

        // Formulaire d'inscription
        $registrationForm = $this->createForm(RegistrationForm::class);
        $registrationForm->handleRequest($request);

        // Traitement du formulaire de connexion
        if ($loginForm->isSubmitted() && $loginForm->isValid()) {
            // Logique de connexion ici
            // Exemple: Authentifier l'utilisateur, redirection, etc.
        }

        // Traitement du formulaire d'inscription
        if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
            // Logique d'inscription ici
            $user = $registrationForm->getData();
            $plainPassword = $registrationForm->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            // Redirection après inscription
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/auth.html.twig', [
            'loginForm' => $loginForm->createView(),
            'registrationForm' => $registrationForm->createView(),
        ]);
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Récupérer une erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();

        // Dernier nom d'utilisateur entré par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/auth.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // Cette méthode peut être vide, Symfony gère la déconnexion automatiquement
    }
}
