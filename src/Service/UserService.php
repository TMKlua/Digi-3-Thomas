<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private PermissionService $permissionService,
        private Security $security,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function createUser(array $data): User
    {
        if (!$this->permissionService->hasPermission('create_user')) {
            throw new \RuntimeException('Permission denied to create user');
        }

        $user = new User();
        $user->setUserEmail($data['email']);
        $user->setUserFirstName($data['firstName']);
        $user->setUserLastName($data['lastName']);
        $user->setUserRole($data['role'] ?? User::ROLE_USER);

        if (isset($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function updateUser(int $id, array $data): User
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        if (!$this->permissionService->hasPermission('edit_user')) {
            throw new \RuntimeException('Permission denied to edit user');
        }

        if (isset($data['email'])) {
            $user->setUserEmail($data['email']);
        }
        if (isset($data['firstName'])) {
            $user->setUserFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $user->setUserLastName($data['lastName']);
        }
        if (isset($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        $user->setUserUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $user;
    }

    public function deleteUser(User $user): void
    {
        if (!$this->permissionService->hasPermission('delete_user')) {
            throw new \RuntimeException('Permission denied to delete user');
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function updateUserRoles(User $user, array $roles): User
    {
        if (!$this->permissionService->hasPermission('manage_user_roles')) {
            throw new \RuntimeException('Permission denied to manage user roles');
        }

        $user->setUserRole($roles[0]);
        $user->setUserUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    public function findByRole(string $role): array
    {
        return $this->userRepository->findByRole($role);
    }

    public function findActive(): array
    {
        return $this->userRepository->findActive();
    }

    public function search(string $term): array
    {
        return $this->userRepository->search($term);
    }

    public function findInactiveForPeriod(\DateTime $since): array
    {
        return $this->userRepository->findInactiveForPeriod($since);
    }
} 