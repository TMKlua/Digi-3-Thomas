<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\RoleHierarchyService;
use PHPUnit\Framework\TestCase;

class RoleHierarchyServiceTest extends TestCase
{
    private RoleHierarchyService $roleHierarchyService;

    protected function setUp(): void
    {
        $this->roleHierarchyService = new RoleHierarchyService();
    }

    public function testGetReachableRoleNames(): void
    {
        $adminRoles = $this->roleHierarchyService->getReachableRoleNames(['ROLE_ADMIN']);
        $this->assertContains('ROLE_ADMIN', $adminRoles);
        $this->assertContains('ROLE_PROJECT_MANAGER', $adminRoles);
        $this->assertContains('ROLE_USER', $adminRoles);

        $pmRoles = $this->roleHierarchyService->getReachableRoleNames(['ROLE_PROJECT_MANAGER']);
        $this->assertContains('ROLE_PROJECT_MANAGER', $pmRoles);
        $this->assertContains('ROLE_USER', $pmRoles);
        $this->assertNotContains('ROLE_ADMIN', $pmRoles);

        $userRoles = $this->roleHierarchyService->getReachableRoleNames(['ROLE_USER']);
        $this->assertContains('ROLE_USER', $userRoles);
        $this->assertNotContains('ROLE_PROJECT_MANAGER', $userRoles);
        $this->assertNotContains('ROLE_ADMIN', $userRoles);
    }

    public function testIsGranted(): void
    {
        $this->assertTrue($this->roleHierarchyService->isGranted(['ROLE_ADMIN'], 'ROLE_USER'));
        $this->assertTrue($this->roleHierarchyService->isGranted(['ROLE_ADMIN'], 'ROLE_PROJECT_MANAGER'));
        $this->assertTrue($this->roleHierarchyService->isGranted(['ROLE_ADMIN'], 'ROLE_ADMIN'));

        $this->assertTrue($this->roleHierarchyService->isGranted(['ROLE_PROJECT_MANAGER'], 'ROLE_USER'));
        $this->assertTrue($this->roleHierarchyService->isGranted(['ROLE_PROJECT_MANAGER'], 'ROLE_PROJECT_MANAGER'));
        $this->assertFalse($this->roleHierarchyService->isGranted(['ROLE_PROJECT_MANAGER'], 'ROLE_ADMIN'));

        $this->assertTrue($this->roleHierarchyService->isGranted(['ROLE_USER'], 'ROLE_USER'));
        $this->assertFalse($this->roleHierarchyService->isGranted(['ROLE_USER'], 'ROLE_PROJECT_MANAGER'));
        $this->assertFalse($this->roleHierarchyService->isGranted(['ROLE_USER'], 'ROLE_ADMIN'));
    }

    /**
     * @dataProvider provideRoleHierarchyTests
     */
    public function testHasRole(string $userRole, string $requiredRole, bool $expectedResult): void
    {
        // Act
        $result = $this->roleHierarchyService->hasRole($userRole, $requiredRole);
        
        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    public function provideRoleHierarchyTests(): array
    {
        return [
            'admin_has_admin_role' => [
                User::ROLE_ADMIN,
                User::ROLE_ADMIN,
                true
            ],
            'admin_has_user_role' => [
                User::ROLE_ADMIN,
                User::ROLE_USER,
                true
            ],
            'user_does_not_have_admin_role' => [
                User::ROLE_USER,
                User::ROLE_ADMIN,
                false
            ],
            'project_manager_has_developer_role' => [
                User::ROLE_PROJECT_MANAGER,
                User::ROLE_DEVELOPER,
                true
            ],
            'developer_does_not_have_project_manager_role' => [
                User::ROLE_DEVELOPER,
                User::ROLE_PROJECT_MANAGER,
                false
            ]
        ];
    }

    /**
     * @dataProvider providePermissionTests
     */
    public function testHasPermission(string $role, string $permission, bool $expectedResult): void
    {
        // Act
        $result = $this->roleHierarchyService->hasPermission($role, $permission);
        
        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    public function providePermissionTests(): array
    {
        return [
            'admin_can_manage_configuration' => [
                User::ROLE_ADMIN,
                'manage_configuration',
                true
            ],
            'user_can_view_own_profile' => [
                User::ROLE_USER,
                'view_own_profile',
                true
            ],
            'developer_can_view_projects' => [
                User::ROLE_DEVELOPER,
                'view_projects',
                true
            ],
            'user_cannot_manage_users' => [
                User::ROLE_USER,
                'manage_users',
                false
            ],
            'project_manager_can_create_projects' => [
                User::ROLE_PROJECT_MANAGER,
                'create_projects',
                true
            ],
            'lead_developer_can_manage_team_tasks' => [
                User::ROLE_LEAD_DEVELOPER,
                'manage_team_tasks',
                true
            ]
        ];
    }

    public function testGetPermissionsForRole(): void
    {
        // Test ROLE_ADMIN permissions
        $adminPermissions = $this->roleHierarchyService->getPermissionsForRole(User::ROLE_ADMIN);
        $this->assertContains('manage_configuration', $adminPermissions);
        $this->assertContains('view_logs', $adminPermissions);
        $this->assertContains('manage_system_settings', $adminPermissions);
        $this->assertContains('manage_users', $adminPermissions);

        // Test ROLE_PROJECT_MANAGER permissions
        $pmPermissions = $this->roleHierarchyService->getPermissionsForRole(User::ROLE_PROJECT_MANAGER);
        $this->assertContains('create_projects', $pmPermissions);
        $this->assertContains('edit_projects', $pmPermissions);
        $this->assertContains('view_project_statistics', $pmPermissions);
        $this->assertNotContains('manage_configuration', $pmPermissions);

        // Test ROLE_DEVELOPER permissions
        $devPermissions = $this->roleHierarchyService->getPermissionsForRole(User::ROLE_DEVELOPER);
        $this->assertContains('view_projects', $devPermissions);
        $this->assertContains('edit_own_tasks', $devPermissions);
        $this->assertNotContains('manage_users', $devPermissions);
        $this->assertNotContains('create_projects', $devPermissions);

        // Test ROLE_USER permissions
        $userPermissions = $this->roleHierarchyService->getPermissionsForRole(User::ROLE_USER);
        $this->assertContains('view_own_profile', $userPermissions);
        $this->assertContains('view_assigned_tasks', $userPermissions);
        $this->assertNotContains('manage_configuration', $userPermissions);
        $this->assertNotContains('create_projects', $userPermissions);
    }

    public function testRoleHierarchyInheritance(): void
    {
        // Test ROLE_ADMIN inherits all roles
        $adminRoles = $this->roleHierarchyService->getRoleHierarchy(User::ROLE_ADMIN);
        $this->assertContains(User::ROLE_PROJECT_MANAGER, $adminRoles);
        $this->assertContains(User::ROLE_DEVELOPER, $adminRoles);
        $this->assertContains(User::ROLE_USER, $adminRoles);

        // Test ROLE_PROJECT_MANAGER inherits appropriate roles
        $pmRoles = $this->roleHierarchyService->getRoleHierarchy(User::ROLE_PROJECT_MANAGER);
        $this->assertContains(User::ROLE_DEVELOPER, $pmRoles);
        $this->assertContains(User::ROLE_USER, $pmRoles);
        $this->assertNotContains(User::ROLE_ADMIN, $pmRoles);

        // Test ROLE_DEVELOPER inherits only USER role
        $devRoles = $this->roleHierarchyService->getRoleHierarchy(User::ROLE_DEVELOPER);
        $this->assertContains(User::ROLE_USER, $devRoles);
        $this->assertNotContains(User::ROLE_PROJECT_MANAGER, $devRoles);
        $this->assertNotContains(User::ROLE_ADMIN, $devRoles);

        // Test ROLE_USER has no inherited roles
        $userRoles = $this->roleHierarchyService->getRoleHierarchy(User::ROLE_USER);
        $this->assertEmpty($userRoles);
    }
} 