<?php

namespace App\Controller\Parameter;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

#[Route('/parameter/users')]
class UserController extends AbstractCrudController
{
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        PermissionService $permissionService,
        Security $security,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct($entityManager, $validator, $logger, $permissionService, $security);
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    protected function getEntityClass(): string
    {
        return User::class;
    }

    protected function getRepository()
    {
        return $this->userRepository;
    }

    protected function getEntityName(): string
    {
        return 'Utilisateur';
    }

    protected function getTemplatePrefix(): string
    {
        return 'parameter/user';
    }

    protected function canView(): bool
    {
        return $this->permissionService->hasPermission('view_users');
    }

    protected function canEdit(): bool
    {
        return $this->permissionService->hasPermission('edit_users');
    }

    protected function canDelete(): bool
    {
        return $this->permissionService->hasPermission('delete_users');
    }

    #[Route('/', name: 'app_parameter_users')]
    public function index(): Response
    {
        // Vérifier si l'utilisateur est authentifié
        $currentUser = $this->security->getUser();
        if (!$currentUser) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié');
        }

        // Vérifier si l'utilisateur peut voir la liste des utilisateurs
        if (!$this->canView()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les permissions nécessaires pour voir la liste des utilisateurs');
        }

        return $this->render('parameter/user/index.html.twig', [
            'users' => $this->userRepository->findAll(),
            'user' => $this->security->getUser(),
            'canEdit' => $this->canEdit(),
            'canDelete' => $this->canDelete(),
            'canManageRoles' => $this->permissionService->hasPermission('manage_roles')
        ]);
    }

    protected function validateData(array $data): void
    {
        $requiredFields = ['firstName', 'lastName', 'email', 'role'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Le champ $field est obligatoire");
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Format d\'email invalide');
        }
    }

    protected function createEntity(array $data): object
    {
        $this->denyAccessUnlessGranted('create', null, 'Vous n\'avez pas les permissions nécessaires pour créer un utilisateur.');
        
        $user = new User();
        $this->updateEntity($user, $data);
        $tempPassword = $this->generateTemporaryPassword($user);
        
        $this->logger->info('Mot de passe temporaire généré', [
            'email' => $user->getUserEmail(),
            'tempPassword' => $tempPassword
        ]);
        
        return $user;
    }

    protected function updateEntity(object $entity, array $data): void
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException('L\'entité doit être un utilisateur');
        }

        $this->denyAccessUnlessGranted('edit', $entity, 'Vous n\'avez pas les permissions nécessaires pour modifier cet utilisateur.');

        if ($entity->getId() && $entity->getUserRole() !== $data['role']) {
            $this->denyAccessUnlessGranted('change_role', $entity, 'Vous n\'avez pas les permissions nécessaires pour changer le rôle de cet utilisateur.');
        }

        $entity->setUserFirstName($data['firstName'])
               ->setUserLastName($data['lastName'])
               ->setUserEmail($data['email'])
               ->setUserRole($data['role']);
    }

    private function generateTemporaryPassword(User $user): string
    {
        $tempPassword = bin2hex(random_bytes(8));
        $hashedPassword = $this->passwordHasher->hashPassword($user, $tempPassword);
        $user->setPassword($hashedPassword);
        return $tempPassword;
    }

    protected function getRequestData(Request $request): array
    {
        return [
            'firstName' => $request->request->get('firstName'),
            'lastName' => $request->request->get('lastName'),
            'email' => $request->request->get('email'),
            'role' => $request->request->get('role')
        ];
    }
}
