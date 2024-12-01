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

class SecurityController extends AbstractController
{
    #[Route('/auth', name: 'app_auth', methods: ['POST','GET'])]
    public function auth(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('auth/auth.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'last_username' => $authenticationUtils->getLastUsername(),
        ]);
    }   

    #[Route('/auth/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        UserAuthenticatorInterface $userAuthenticator,
        #[Autowire(service: 'security.authenticator.form_login.main')] AuthenticatorInterface $authenticator
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = new User();
        $user->setUserFirstName($request->request->get('first_name'))
            ->setUserLastName($request->request->get('last_name'))
            ->setUserEmail($request->request->get('email'))
            ->setPassword($request->request->get('password'));

        // Hash password before validation
        $hashedPassword = $passwordHasher->hashPassword($user, $request->request->get('password'));
        $user->setPassword($hashedPassword);

        // Validate user data
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        // Check if email exists
        $existingUser = $entityManager->getRepository(User::class)->findOneBy([
            'userEmail' => $user->getUserEmail()
        ]);
        if ($existingUser) {
            return $this->json(['errors' => 'Email already exists'], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        // Automatically authenticate the user
        $response = $userAuthenticator->authenticateUser(
            $user,
            $authenticator,
            $request
        );

        return $this->json([
            'success' => true,
            'redirect' => $this->generateUrl('app_dashboard')
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // Controller will be intercepted by the logout key on your firewall
    }
}