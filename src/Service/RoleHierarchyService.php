<?php

namespace App\Service;

use App\Entity\User;

class RoleHierarchyService
{
    private const ROLE_HIERARCHY = [
        User::ROLE_ADMIN => [
            User::ROLE_PROJECT_MANAGER,
            User::ROLE_LEAD_DEVELOPER,
            User::ROLE_DEVELOPER,
            User::ROLE_USER
        ],
        User::ROLE_PROJECT_MANAGER => [
            User::ROLE_LEAD_DEVELOPER,
            User::ROLE_DEVELOPER,
            User::ROLE_USER
        ],
        User::ROLE_LEAD_DEVELOPER => [
            User::ROLE_DEVELOPER,
            User::ROLE_USER
        ],
        User::ROLE_DEVELOPER => [
            User::ROLE_USER
        ],
        User::ROLE_USER => []
    ];

    private const ROLE_PERMISSIONS = [
        User::ROLE_ADMIN => [
            'manage_configuration',
            'view_logs',
            'manage_system_settings',
            'manage_users',
            'create_projects',
            'edit_projects',
            'view_project_statistics',
            'manage_team_tasks',
            'view_projects',
            'edit_own_tasks',
            'view_own_profile',
            'view_assigned_tasks'
        ],
        User::ROLE_PROJECT_MANAGER => [
            'create_projects',
            'edit_projects',
            'view_project_statistics',
            'manage_team_tasks',
            'view_projects',
            'edit_own_tasks',
            'view_own_profile',
            'view_assigned_tasks'
        ],
        User::ROLE_LEAD_DEVELOPER => [
            'manage_team_tasks',
            'view_projects',
            'edit_own_tasks',
            'view_own_profile',
            'view_assigned_tasks'
        ],
        User::ROLE_DEVELOPER => [
            'view_projects',
            'edit_own_tasks',
            'view_own_profile',
            'view_assigned_tasks'
        ],
        User::ROLE_USER => [
            'view_own_profile',
            'view_assigned_tasks'
        ]
    ];

    public function getReachableRoleNames(array $roles): array
    {
        $allRoles = [];
        foreach ($roles as $role) {
            $allRoles[] = $role;
            $allRoles = array_merge($allRoles, $this->getRoleHierarchy($role));
        }
        return array_unique($allRoles);
    }

    public function isGranted(array $userRoles, string $requiredRole): bool
    {
        $reachableRoles = $this->getReachableRoleNames($userRoles);
        return in_array($requiredRole, $reachableRoles);
    }

    public function hasRole(string $userRole, string $requiredRole): bool
    {
        return $this->isGranted([$userRole], $requiredRole);
    }

    public function hasPermission(string $role, string $permission): bool
    {
        $permissions = $this->getPermissionsForRole($role);
        return in_array($permission, $permissions);
    }

    public function getPermissionsForRole(string $role): array
    {
        $permissions = [];
        $roles = $this->getReachableRoleNames([$role]);
        
        foreach ($roles as $reachableRole) {
            if (isset(self::ROLE_PERMISSIONS[$reachableRole])) {
                $permissions = array_merge($permissions, self::ROLE_PERMISSIONS[$reachableRole]);
            }
        }
        
        return array_unique($permissions);
    }

    public function getRoleHierarchy(string $role): array
    {
        $reachableRoles = [];
        if (isset(self::ROLE_HIERARCHY[$role])) {
            $reachableRoles = self::ROLE_HIERARCHY[$role];
            foreach (self::ROLE_HIERARCHY[$role] as $subRole) {
                $reachableRoles = array_merge($reachableRoles, $this->getRoleHierarchy($subRole));
            }
        }
        return array_unique($reachableRoles);
    }
} 