<?php

namespace App\Controller\Parameter;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

#[Route('/parameter/users')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private LoggerInterface $logger,
        private UserRepository $userRepository,
        private PermissionService $permissionService
    ) {}

    #[Route('/', name: 'app_parameter_users')]
    public function index(): Response
    {
        $currentUser = $this->getUser();

        // Vérifier si l'utilisateur peut voir la liste des utilisateurs
        if (!$this->permissionService->canViewUserList()) {
            throw $this->createAccessDeniedException('Accès non autorisé');
        }

        // Déterminer si l'utilisateur peut modifier
        $canEdit = $this->permissionService->canEditUser();

        // Requête de base pour filtrer les utilisateurs
        $queryBuilder = $this->userRepository->createQueryBuilder('u')
            ->where('u.userRole != :adminRole')
            ->setParameter('adminRole', 'ROLE_ADMIN');

        // Filtrage selon le rôle de l'utilisateur connecté
        switch ($currentUser->getUserRole()) {
            case 'ROLE_PROJECT_MANAGER':
                // Le chef de projet ne voit que les développeurs
                $queryBuilder
                    ->andWhere('u.userRole IN (:allowedRoles)')
                    ->setParameter('allowedRoles', ['ROLE_DEVELOPER', 'ROLE_LEAD_DEVELOPER']);
                break;
            
            case 'ROLE_RESPONSABLE':
                // Le responsable voit tous les utilisateurs sauf l'admin
                break;
            
            default:
                throw $this->createAccessDeniedException('Accès non autorisé');
        }

        $users = $queryBuilder->getQuery()->getResult();

        return $this->render('parameter/users.html.twig', [
            'users' => $users,
            'user' => $currentUser,
            'canEdit' => $canEdit,
            'isReadOnly' => !$canEdit // Ajout d'un flag pour le mode lecture seule
        ]);
    }

    #[Route('/add', name: 'app_parameter_user_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        // Seul l'admin et le responsable peuvent ajouter des utilisateurs
        if (!$this->permissionService->canEditUser()) {
            return $this->json([
                'success' => false,
                'error' => 'Vous n\'avez pas les permissions nécessaires'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            // Récupérer les données du formulaire
            $userData = [
                'firstName' => $request->request->get('firstName'),
                'lastName' => $request->request->get('lastName'),
                'email' => $request->request->get('email'),
                'role' => $request->request->get('role')
            ];

            // Créer un nouvel utilisateur
            $user = new User();
            $user->setUserFirstName($userData['firstName'])
                 ->setUserLastName($userData['lastName'])
                 ->setUserEmail($userData['email'])
                 ->setUserRole($userData['role']);

            // Générer un mot de passe temporaire
            $tempPassword = $this->generateTemporaryPassword($user);

            // Valider l'utilisateur
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                return $this->json([
                    'success' => false,
                    'error' => (string) $errors
                ], Response::HTTP_BAD_REQUEST);
            }

            // Persister l'utilisateur
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'userId' => $user->getId()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur de création d\'utilisateur : ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/edit/{id}', name: 'app_parameter_user_edit', methods: ['POST'])]
    public function edit(int $id, Request $request): JsonResponse
    {
        $userToEdit = $this->userRepository->find($id);
        
        // Vérifier si l'utilisateur peut gérer cet utilisateur
        if (!$this->permissionService->canManageUser($userToEdit)) {
            return $this->json([
                'success' => false,
                'error' => 'Vous n\'avez pas les permissions nécessaires'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            // Mettre à jour les informations
            $userToEdit->setUserFirstName($request->request->get('firstName'))
                        ->setUserLastName($request->request->get('lastName'))
                        ->setUserEmail($request->request->get('email'))
                        ->setUserRole($request->request->get('role'));

            // Valider l'utilisateur
            $errors = $this->validator->validate($userToEdit);
            if (count($errors) > 0) {
                return $this->json([
                    'success' => false,
                    'error' => (string) $errors
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Utilisateur modifié avec succès'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur de modification d\'utilisateur : ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/delete/{id}', name: 'app_parameter_user_delete', methods: ['POST'])]
    public function delete(int $id): JsonResponse
    {
        $userToDelete = $this->userRepository->find($id);
        
        // Seul l'admin et le responsable peuvent supprimer des utilisateurs
        if (!$this->permissionService->canEditUser()) {
            return $this->json([
                'success' => false,
                'error' => 'Vous n\'avez pas les permissions nécessaires'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            if ($userToDelete === $this->getUser()) {
                return $this->json([
                    'success' => false,
                    'error' => 'Vous ne pouvez pas supprimer votre propre compte'
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->remove($userToDelete);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur de suppression d\'utilisateur : ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    private function createUserFromRequest(array $userData): User
    {
        $user = new User();
        $user->setUserFirstName($userData['firstName'] ?? '')
             ->setUserLastName($userData['lastName'] ?? '')
             ->setUserEmail($userData['email'] ?? '')
             ->setUserRole($userData['role'] ?? 'ROLE_USER');

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function updateUserFromRequest(int $id, array $userData): User
    {
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            throw new \RuntimeException('Utilisateur non trouvé');
        }

        $user->setUserFirstName($userData['firstName'] ?? $user->getUserFirstName())
             ->setUserLastName($userData['lastName'] ?? $user->getUserLastName())
             ->setUserEmail($userData['email'] ?? $user->getUserEmail())
             ->setUserRole($userData['role'] ?? $user->getUserRole());

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->entityManager->flush();
        return $user;
    }

    private function deleteUserById(int $id): void
    {
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            throw new \RuntimeException('Utilisateur non trouvé');
        }

        if ($user === $this->getUser()) {
            throw new \RuntimeException('Vous ne pouvez pas supprimer votre propre compte');
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    private function generateTemporaryPassword(User $user): string
    {
        $tempPassword = bin2hex(random_bytes(8));
        $hashedPassword = $this->passwordHasher->hashPassword($user, $tempPassword);
        $user->setPassword($hashedPassword);
        $this->entityManager->flush();

        // TODO: Envoyer un email avec le mot de passe temporaire
        return $tempPassword;
    }
}
