<?php

namespace App\Service;

use App\Entity\User;

class RoleHierarchyService
{
    private const ROLE_HIERARCHY = [
        User::ROLE_ADMIN => [
            User::ROLE_RESPONSABLE,
            User::ROLE_PROJECT_MANAGER,
            User::ROLE_LEAD_DEVELOPER,
            User::ROLE_DEVELOPER,
            User::ROLE_USER
        ],
        User::ROLE_RESPONSABLE => [
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
        User::ROLE_USER => [
            'view_general_pages',
            'view_own_profile'
        ],
        User::ROLE_DEVELOPER => [
            'view_projects',
            'edit_own_tasks'
        ],
        User::ROLE_LEAD_DEVELOPER => [
            'manage_team_tasks',
            'view_team_performance'
        ],
        User::ROLE_PROJECT_MANAGER => [
            'view_users',
            'view_customers',
            'manage_projects'
        ],
        User::ROLE_RESPONSABLE => [
            'edit_users',
            'delete_users',
            'manage_customers'
        ],
        User::ROLE_ADMIN => [
            'manage_configuration',
            'manage_system_settings'
        ]
    ];

    public function getRoleHierarchy(string $role): array
    {
        return self::ROLE_HIERARCHY[$role] ?? [];
    }

    public function hasRole(string $userRole, string $requiredRole): bool
    {
        if ($userRole === $requiredRole) {
            return true;
        }

        return in_array($requiredRole, self::ROLE_HIERARCHY[$userRole] ?? []);
    }

    public function getPermissionsForRole(string $role): array
    {
        $permissions = [];
        
        // Ajouter les permissions du rôle actuel
        if (isset(self::ROLE_PERMISSIONS[$role])) {
            $permissions = array_merge($permissions, self::ROLE_PERMISSIONS[$role]);
        }

        // Ajouter les permissions des rôles inférieurs
        foreach ($this->getRoleHierarchy($role) as $inheritedRole) {
            if (isset(self::ROLE_PERMISSIONS[$inheritedRole])) {
                $permissions = array_merge($permissions, self::ROLE_PERMISSIONS[$inheritedRole]);
            }
        }

        return array_unique($permissions);
    }

    public function hasPermission(string $userRole, string $permission): bool
    {
        $permissions = $this->getPermissionsForRole($userRole);
        return in_array($permission, $permissions);
    }
} 