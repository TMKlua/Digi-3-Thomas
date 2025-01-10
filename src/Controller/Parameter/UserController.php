<?php

namespace App\Controller\Parameter;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/parameter/users')]
#[IsGranted('ROLE_PROJECT_MANAGER')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_parameter_users')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Get current user
        $currentUser = $this->getUser();
        
        // Get all users
        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->render('parameter/users.html.twig', [
            'user' => $currentUser,
            'users' => $users
        ]);
    }

    #[Route('/add', name: 'app_parameter_user_add', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function add(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        try {
            $user = new User();
            $user->setUserFirstName($request->request->get('firstName'))
                ->setUserLastName($request->request->get('lastName'))
                ->setUserEmail($request->request->get('email'))
                ->setUserRole($request->request->get('role'));

            // Generate a random password
            $tempPassword = bin2hex(random_bytes(8));
            $hashedPassword = $passwordHasher->hashPassword($user, $tempPassword);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'tempPassword' => $tempPassword // À envoyer par email dans une vraie application
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Une erreur est survenue lors de la création de l\'utilisateur: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/delete/{id}', name: 'app_parameter_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $user = $entityManager->getRepository(User::class)->find($id);
            
            if (!$user) {
                throw new \Exception('Utilisateur non trouvé');
            }

            // Vérifier qu'on ne supprime pas l'utilisateur connecté
            if ($user === $this->getUser()) {
                throw new \Exception('Vous ne pouvez pas supprimer votre propre compte');
            }

            $entityManager->remove($user);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/edit/{id}', name: 'app_parameter_user_edit', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $user = $entityManager->getRepository(User::class)->find($id);
            
            if (!$user) {
                throw new \Exception('Utilisateur non trouvé');
            }

            $user->setUserFirstName($request->request->get('firstName'))
                ->setUserLastName($request->request->get('lastName'))
                ->setUserEmail($request->request->get('email'))
                ->setUserRole($request->request->get('role'));

            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Utilisateur modifié avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la modification: ' . $e->getMessage()
            ], 400);
        }
    }
}
