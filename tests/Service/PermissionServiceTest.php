<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\Tasks;
use App\Service\PermissionService;
use App\Service\RoleHierarchyService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use App\Enum\UserRole;

class PermissionServiceTest extends TestCase
{
    private PermissionService $permissionService;
    /** @var Security&MockObject */
    private $security;
    /** @var RoleHierarchyService&MockObject */
    private $roleHierarchy;
    private User $user;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->roleHierarchy = $this->createMock(RoleHierarchyService::class);
        $this->permissionService = new PermissionService(
            $this->security,
            $this->roleHierarchy
        );
        $this->user = new User();
        $this->user->setUserEmail('test@example.com');
        $this->user->setUserFirstName('Test');
        $this->user->setUserLastName('User');
        $this->user->setUserRole(UserRole::ADMIN);
    }

    /**
     * @dataProvider providePermissionTests
     */
    public function testHasPermission(UserRole $userRole, string $permission, bool $expectedResult): void
    {
        // Create mock user with roles
        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('getUserRole')
            ->willReturn($userRole);

        // Set up security mock
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        // Set up role hierarchy mock
        $this->roleHierarchy->expects($this->once())
            ->method('hasPermission')
            ->with($userRole, $permission)
            ->willReturn($expectedResult);

        // Test permission
        $result = $this->permissionService->hasPermission($permission);
        $this->assertEquals($expectedResult, $result);
    }

    public function providePermissionTests(): array
    {
        return [
            'admin_has_all_permissions' => [
                UserRole::ADMIN,
                'any_permission',
                true
            ],
            'project_manager_can_manage_projects' => [
                UserRole::PROJECT_MANAGER,
                'manage_projects',
                true
            ],
            'project_manager_cannot_manage_users' => [
                UserRole::PROJECT_MANAGER,
                'manage_users',
                false
            ],
            'developer_can_view_projects' => [
                UserRole::DEVELOPER,
                'view_projects',
                true
            ],
            'user_can_view_own_profile' => [
                UserRole::USER,
                'view_own_profile',
                true
            ],
            'user_cannot_manage_system' => [
                UserRole::USER,
                'manage_system',
                false
            ]
        ];
    }

    public function testNoUserHasNoPermissions(): void
    {
        // Set up security mock to return null (no user)
        $this->security->expects($this->exactly(3))
            ->method('getUser')
            ->willReturn(null);

        // Test various permissions
        $this->assertFalse($this->permissionService->hasPermission('view_projects'));
        $this->assertFalse($this->permissionService->hasPermission('manage_users'));
        $this->assertFalse($this->permissionService->hasPermission('any_permission'));
    }

    public function testUserWithMultipleRoles(): void
    {
        // Create mock user with multiple roles
        $user = $this->createMock(User::class);
        $user->method('getRoles')->willReturn(['ROLE_DEVELOPER', 'ROLE_PROJECT_MANAGER']);

        // Set up security mock
        $this->security->method('getUser')->willReturn($user);

        // Test permissions from both roles
        $this->assertTrue($this->permissionService->hasPermission('manage_projects'));
        $this->assertTrue($this->permissionService->hasPermission('view_code'));
        $this->assertFalse($this->permissionService->hasPermission('manage_system'));
    }

    public function testAdminOverride(): void
    {
        // Create mock admin user
        $user = $this->createMock(User::class);
        $user->method('getRoles')->willReturn(['ROLE_ADMIN']);

        // Set up security mock
        $this->security->method('getUser')->willReturn($user);

        // Test various permissions that should all be granted for admin
        $this->assertTrue($this->permissionService->hasPermission('manage_system'));
        $this->assertTrue($this->permissionService->hasPermission('manage_users'));
        $this->assertTrue($this->permissionService->hasPermission('any_custom_permission'));
    }

    public function testRoleHierarchy(): void
    {
        // Create mock project manager
        $user = $this->createMock(User::class);
        $user->method('getRoles')->willReturn(['ROLE_PROJECT_MANAGER']);

        // Set up security mock
        $this->security->method('getUser')->willReturn($user);

        // Test inherited permissions
        $this->assertTrue($this->permissionService->hasPermission('view_projects')); // Developer permission
        $this->assertTrue($this->permissionService->hasPermission('manage_projects')); // Project Manager permission
        $this->assertTrue($this->permissionService->hasPermission('view_own_profile')); // Basic user permission
        $this->assertFalse($this->permissionService->hasPermission('manage_system')); // Admin permission
    }

    public function testCanEditProject(): void
    {
        // Arrange
        $project = new Project();
        $projectManager = new User();
        $projectManager->setUserRole(UserRole::PROJECT_MANAGER);
        $project->setProjectManager($projectManager);

        // Test 1: Project Manager can edit their own project
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($projectManager);

        $this->roleHierarchy->expects($this->once())
            ->method('hasPermission')
            ->with(UserRole::PROJECT_MANAGER, 'edit_projects')
            ->willReturn(true);

        $result = $this->permissionService->canEditProject($project);
        $this->assertTrue($result);
    }

    public function testCanEditTask(): void
    {
        // Arrange
        $task = new Tasks();
        $assignedUser = new User();
        $assignedUser->setUserRole(UserRole::DEVELOPER);
        $task->setTaskAssignedTo($assignedUser);

        // Test 1: Assigned user can edit their own task
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($assignedUser);

        $this->roleHierarchy->expects($this->once())
            ->method('hasPermission')
            ->with(UserRole::DEVELOPER, 'edit_own_tasks')
            ->willReturn(true);

        $result = $this->permissionService->canEditTask($task);
        $this->assertTrue($result);
    }

    public function testHasPermissionWithLoggedInUser(): void
    {
        // Configure the security mock to return our test user
        $this->security->method('getUser')
            ->willReturn($this->user);
        
        // Configure the role hierarchy mock to return true for the permission
        $this->roleHierarchy->method('hasPermission')
            ->with(UserRole::ADMIN, 'test_permission')
            ->willReturn(true);
        
        // Test that hasPermission returns true
        $this->assertTrue($this->permissionService->hasPermission('test_permission'));
    }

    public function testHasPermissionWithNoLoggedInUser(): void
    {
        // Configure the security mock to return null (no logged in user)
        $this->security->method('getUser')
            ->willReturn(null);
        
        // Test that hasPermission returns false when no user is logged in
        $this->assertFalse($this->permissionService->hasPermission('test_permission'));
    }
} 