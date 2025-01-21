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
use Symfony\Bundle\SecurityBundle\Security;
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
        private PermissionService $permissionService,
        private Security $security
    ) {}

    #[Route('/', name: 'app_parameter_users')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $currentUser = $this->security->getUser();

        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié');
        }

        if (!$this->permissionService->canViewUserList()) {
            throw $this->createAccessDeniedException('Accès non autorisé pour votre rôle');
        }

        $canEdit = $this->permissionService->canEditUser();
        $canDelete = $this->permissionService->canDeleteUser(null);

        $queryBuilder = $this->userRepository->createQueryBuilder('u')
            ->where('u.userRole != :adminRole')
            ->setParameter('adminRole', 'ROLE_ADMIN');

        switch ($currentUser->getUserRole()) {
            case 'ROLE_PROJECT_MANAGER':
                $queryBuilder
                    ->andWhere('u.userRole IN (:allowedRoles)')
                    ->setParameter('allowedRoles', ['ROLE_DEVELOPER', 'ROLE_LEAD_DEVELOPER']);
                break;
            
            case 'ROLE_LEAD_DEVELOPER':
                $queryBuilder
                    ->andWhere('u.userRole IN (:allowedRoles)')
                    ->setParameter('allowedRoles', ['ROLE_DEVELOPER']);
                break;
            
            case 'ROLE_RESPONSABLE':
            case 'ROLE_ADMIN':
                break;
            
            default:
                throw $this->createAccessDeniedException('Accès non autorisé');
        }

        $users = $queryBuilder->getQuery()->getResult();

        return $this->render('parameter/users.html.twig', [
            'user' => $currentUser,
            'users' => $users,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
        ]);
    }

    #[Route('/add', name: 'app_parameter_user_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        if (!$this->permissionService->canEditUser()) {
            return $this->json([
                'success' => false,
                'error' => 'Vous n\'avez pas les permissions nécessaires'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $userData = [
                'firstName' => $request->request->get('firstName'),
                'lastName' => $request->request->get('lastName'),
                'email' => $request->request->get('email'),
                'role' => $request->request->get('role')
            ];

            $this->validateUserData($userData);

            $user = new User();
            $user->setUserFirstName($userData['firstName'])
                 ->setUserLastName($userData['lastName'])
                 ->setUserEmail($userData['email'])
                 ->setUserRole($userData['role']);

            $tempPassword = $this->generateTemporaryPassword($user);

            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                return $this->json([
                    'success' => false,
                    'error' => (string) $errors
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->logger->info('Utilisateur créé', [
                'id' => $user->getId(),
                'email' => $user->getUserEmail()
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'userId' => $user->getId()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur de création d\'utilisateur', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
        
        if (!$userToEdit) {
            return $this->json([
                'success' => false,
                'error' => 'Utilisateur non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$this->permissionService->canManageUser($userToEdit)) {
            return $this->json([
                'success' => false,
                'error' => 'Vous n\'avez pas les permissions nécessaires'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $userData = [
                'firstName' => $request->request->get('firstName'),
                'lastName' => $request->request->get('lastName'),
                'email' => $request->request->get('email'),
                'role' => $request->request->get('role')
            ];

            $this->validateUserData($userData);

            $userToEdit->setUserFirstName($userData['firstName'])
                        ->setUserLastName($userData['lastName'])
                        ->setUserEmail($userData['email'])
                        ->setUserRole($userData['role']);

            $errors = $this->validator->validate($userToEdit);
            if (count($errors) > 0) {
                return $this->json([
                    'success' => false,
                    'error' => (string) $errors
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            $this->logger->info('Utilisateur modifié', [
                'id' => $userToEdit->getId(),
                'email' => $userToEdit->getUserEmail()
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Utilisateur modifié avec succès'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur de modification d\'utilisateur', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
        
        if (!$userToDelete) {
            return $this->json([
                'success' => false,
                'error' => 'Utilisateur non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$this->permissionService->canDeleteUser($userToDelete)) {
            return $this->json([
                'success' => false,
                'error' => 'Vous n\'avez pas les permissions nécessaires'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->entityManager->remove($userToDelete);
            $this->entityManager->flush();

            $this->logger->info('Utilisateur supprimé', [
                'id' => $userToDelete->getId(),
                'email' => $userToDelete->getUserEmail()
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur de suppression d\'utilisateur', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    private function validateUserData(array $userData): void
    {
        $requiredFields = ['firstName', 'lastName', 'email', 'role'];
        
        foreach ($requiredFields as $field) {
            if (empty($userData[$field])) {
                throw new \InvalidArgumentException("Le champ $field est obligatoire");
            }
        }

        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Format d\'email invalide');
        }
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
