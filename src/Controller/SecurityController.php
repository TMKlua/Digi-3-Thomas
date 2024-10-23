<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;
use App\Form\LoginForm;
use App\Form\RegistrationForm;
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
    public function authPage(Request $request): Response
    {
        // Formulaire de connexion
        $loginForm = $this->createForm(LoginForm::class);
        $loginForm->handleRequest($request);
    
        // Formulaire d'inscription
        $registrationForm = $this->createForm(RegistrationForm::class);
        $registrationForm->handleRequest($request);
    
        // Traitement du formulaire d'inscription
        if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
            $user = new User();
            $user->setEmail($registrationForm->get('email')->getData());

            // Gérer le mot de passe
            $plainPassword = $registrationForm->get('plainPassword')->getData();
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            try {
                // Enregistrer l'utilisateur dans la base de données
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                // Authentifier l'utilisateur après inscription
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

        return $this->render('auth/auth.html.twig', [
            'loginForm' => $loginForm->createView(),
            'registrationForm' => $registrationForm->createView(),
        ]);
    }    

    #[Route(path: '/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
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
             $user = $registrationForm->getData();
             $plainPassword = $registrationForm->get('plainPassword')->getData();
             $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
             $user->setPassword($hashedPassword);
 
             try {
                 $this->entityManager->persist($user);
                 $this->entityManager->flush();
 
                 // Authentifier l'utilisateur après inscription
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
 

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error,
            'loginForm' => $loginForm->createView(),
            'registrationForm' => $registrationForm->createView(),
            'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
