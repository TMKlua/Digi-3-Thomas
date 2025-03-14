<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/admin/users', name: 'admin_users')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/user/{id}/edit', name: 'admin_user_edit')]
    public function edit(User $user, Request $request, PasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            $roles = $request->request->get('roles', []);
            $user->setUserRole($roles);

            // Si le mot de passe est modifié
            if ($request->request->get('password')) {
                $password = $request->request->get('password');
                $hashedPassword = $passwordHasher->hash($password);  // Utilisation du PasswordHasher
                $user->setPassword($hashedPassword);
            }

            $this->entityManager->flush();  // Utilisation de l'EntityManager injecté

            $this->addFlash('success', 'Utilisateur modifié avec succès.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user_edit.html.twig', [
            'user' => $user,
        ]);
    }
}
