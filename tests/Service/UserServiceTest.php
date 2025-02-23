<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Security;

class UserServiceTest extends TestCase
{
    private UserService $userService;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private PermissionService $permissionService;
    private Security $security;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->permissionService = $this->createMock(PermissionService::class);
        $this->security = $this->createMock(Security::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $this->userService = new UserService(
            $this->entityManager,
            $this->userRepository,
            $this->permissionService,
            $this->security,
            $this->passwordHasher
        );
    }

    public function testCreateUser(): void
    {
        // Create admin user for permission check
        $adminUser = $this->createMock(User::class);

        // Set up security mock
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($adminUser);

        // Set up permission check
        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('create_user')
            ->willReturn(true);

        // Set up password hasher
        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        // Set up entity manager expectations
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Create user data
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'firstName' => 'Test',
            'lastName' => 'User',
            'roles' => ['ROLE_USER']
        ];

        // Create the user
        $user = $this->userService->createUser($userData);

        // Assert user properties
        $this->assertEquals($userData['email'], $user->getEmail());
        $this->assertEquals($userData['firstName'], $user->getFirstName());
        $this->assertEquals($userData['lastName'], $user->getLastName());
        $this->assertEquals($userData['roles'], $user->getRoles());
    }

    public function testUpdateUser(): void
    {
        // Create existing user
        $user = $this->createMock(User::class);
        $adminUser = $this->createMock(User::class);

        // Set up repository mock
        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        // Set up security mock
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($adminUser);

        // Set up permission check
        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('edit_user')
            ->willReturn(true);

        // Set up user expectations
        $user->expects($this->once())
            ->method('setEmail')
            ->with('updated@example.com');
        
        $user->expects($this->once())
            ->method('setFirstName')
            ->with('Updated');
            
        $user->expects($this->once())
            ->method('setLastName')
            ->with('User');

        // Set up entity manager expectations
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Update data
        $updateData = [
            'email' => 'updated@example.com',
            'firstName' => 'Updated',
            'lastName' => 'User'
        ];

        // Update the user
        $this->userService->updateUser(1, $updateData);
    }

    public function testDeleteUser(): void
    {
        // Create user to delete
        $user = $this->createMock(User::class);
        $adminUser = $this->createMock(User::class);

        // Set up repository mock
        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        // Set up security mock
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($adminUser);

        // Set up permission check
        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('delete_user')
            ->willReturn(true);

        // Set up entity manager expectations
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($user);
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Delete the user
        $this->userService->deleteUser(1);
    }

    public function testUpdateUserRoles(): void
    {
        // Create existing user
        $user = $this->createMock(User::class);
        $adminUser = $this->createMock(User::class);

        // Set up repository mock
        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        // Set up security mock
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($adminUser);

        // Set up permission check
        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('manage_user_roles')
            ->willReturn(true);

        // Set up user expectations
        $user->expects($this->once())
            ->method('setRoles')
            ->with(['ROLE_ADMIN', 'ROLE_USER']);

        // Set up entity manager expectations
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Update roles
        $this->userService->updateUserRoles(1, ['ROLE_ADMIN', 'ROLE_USER']);
    }
} 