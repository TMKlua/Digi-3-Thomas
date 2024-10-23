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
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class AuthController extends AbstractController
{
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $entityManager;

    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager)
    {
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
    }

    #[Route('/auth', name: 'app_auth')]
    public function authPage(Request $request): Response
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
        }
    
        // Traitement du formulaire d'inscription
        if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
            $user = new User(); // Créer une nouvelle instance de User
            $user->setEmail($registrationForm->get('email')->getData());
            
            // Gérer le mot de passe
            $plainPassword = $registrationForm->get('plainPassword')->getData();
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
    
            try {
                // Enregistrer l'utilisateur dans la base de données
                $this->entityManager->persist($user);
                $this->entityManager->flush();
    
                // Redirection vers le tableau de bord après inscription
                return $this->redirectToRoute('app_dashboard');
            } catch (UniqueConstraintViolationException $e) {
                // Gérer l'erreur de clé unique (email déjà utilisé)
                $this->addFlash('error', 'Un compte existe déjà avec l\'adresse email "' . $user->getEmail() . '".');
            } catch (\Exception $e) {
                // Gérer les autres erreurs
                $this->addFlash('error', 'Une erreur s\'est produite lors de la création de votre compte.');
            }
        }
    
        return $this->render('auth/auth.html.twig', [
            'loginForm' => $loginForm->createView(),
            'registrationForm' => $registrationForm->createView(),
        ]);
    }    

    #[Route('/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        // Récupérer une erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();

        // Dernier nom d'utilisateur entré par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        // Créer le formulaire de connexion
        $loginForm = $this->createForm(LoginForm::class, [
            'email' => $lastUsername,
        ]);

        // Créer le formulaire d'inscription
        $registrationForm = $this->createForm(RegistrationForm::class);

        // Gérer la soumission du formulaire d'inscription
        $registrationForm->handleRequest($request);
        if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
            // Logique d'inscription ici
            $user = $registrationForm->getData();
            $plainPassword = $registrationForm->get('plainPassword')->getData();
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            
            // Utilisation de l'entityManager injecté
            try {
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                // Redirection vers le tableau de bord après inscription
                return $this->redirectToRoute('app_dashboard', ['account_created' => true]);
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('error', 'Un compte existe déjà avec l\'adresse email "' . $user->getEmail() . '".');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur s\'est produite lors de la création de votre compte.');
            }
        }

        return $this->render('auth/auth.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'loginForm' => $loginForm->createView(),
            'registrationForm' => $registrationForm->createView(),
        ]);
    } 

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // Cette méthode peut être vide, Symfony gère la déconnexion automatiquement
    }
}
