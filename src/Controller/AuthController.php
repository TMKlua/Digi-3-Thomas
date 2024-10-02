<?php

namespace App\Controller;

use App\Form\LoginForm;
use App\Form\RegistrationForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class AuthController extends AbstractController
{
    #[Route('/auth', name: 'app_auth')]
    public function authPage(Request $request, EntityManagerInterface $entityManager): Response
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
            // Exemple: Enregistrement dans la base de données, redirection, etc.
            $user = $registrationForm->getData();
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->render('auth/auth.html.twig', [
            'loginForm' => $loginForm->createView(),
            'registrationForm' => $registrationForm->createView(),
        ]);
    }
}

