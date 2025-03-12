<?php

namespace App\Service;

use App\Entity\User;

/**
 * Service de gestion de la hiérarchie des rôles et des permissions
 */
class RoleHierarchyService
{
    /**
     * Hiérarchie des rôles (du plus élevé au plus bas)
     */
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

    /**
     * Permissions par domaine fonctionnel
     */
    private const PERMISSIONS = [
        // Permissions système
        'system' => [
            'manage_configuration' => [User::ROLE_ADMIN],
            'view_logs' => [User::ROLE_ADMIN],
            'manage_system_settings' => [User::ROLE_ADMIN],
        ],
        
        // Permissions utilisateurs
        'users' => [
            'view_users' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER],
            'create_users' => [User::ROLE_ADMIN],
            'edit_users' => [User::ROLE_ADMIN],
            'delete_users' => [User::ROLE_ADMIN],
            'manage_roles' => [User::ROLE_ADMIN],
            'view_own_profile' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER, User::ROLE_LEAD_DEVELOPER, User::ROLE_DEVELOPER, User::ROLE_USER],
        ],
        
        // Permissions projets
        'projects' => [
            'view_all_projects' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER],
            'create_projects' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER],
            'edit_projects' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER],
            'delete_projects' => [User::ROLE_ADMIN],
            'view_project_statistics' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER],
            'view_projects' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER, User::ROLE_LEAD_DEVELOPER, User::ROLE_DEVELOPER],
        ],
        
        // Permissions tâches
        'tasks' => [
            'view_all_tasks' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER],
            'create_project_tasks' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER, User::ROLE_LEAD_DEVELOPER],
            'edit_own_tasks' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER, User::ROLE_LEAD_DEVELOPER, User::ROLE_DEVELOPER, User::ROLE_USER],
            'manage_team_tasks' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER, User::ROLE_LEAD_DEVELOPER],
            'assign_tasks' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER, User::ROLE_LEAD_DEVELOPER],
            'add_comment' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER, User::ROLE_LEAD_DEVELOPER, User::ROLE_DEVELOPER, User::ROLE_USER],
            'add_attachment' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER, User::ROLE_LEAD_DEVELOPER, User::ROLE_DEVELOPER, User::ROLE_USER],
            'view_all_attachments' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER],
        ],
        
        // Permissions clients
        'customers' => [
            'view_customers' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER],
            'edit_customers' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER],
            'delete_customers' => [User::ROLE_ADMIN],
        ],
        
        // Permissions statistiques
        'statistics' => [
            'view_statistics' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER],
            'view_global_statistics' => [User::ROLE_ADMIN],
        ],
        
        // Permissions dashboard
        'dashboard' => [
            'view_dashboard' => [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER, User::ROLE_LEAD_DEVELOPER, User::ROLE_DEVELOPER, User::ROLE_USER],
        ],
    ];

    /**
     * Vérifie si un rôle a une permission spécifique
     */
    public function hasPermission(string $role, string $permission): bool
    {
        // Pour le débogage
        // echo "Vérification de la permission $permission pour le rôle $role<br>";
        
        // Parcourir tous les domaines de permissions
        foreach (self::PERMISSIONS as $domain => $permissions) {
            if (isset($permissions[$permission])) {
                // Vérifier si le rôle est directement dans la liste des rôles autorisés
                if (in_array($role, $permissions[$permission])) {
                    return true;
                }
                
                // Si le rôle est ROLE_ADMIN, il a toutes les permissions
                if ($role === User::ROLE_ADMIN) {
                    return true;
                }
                
                // Pour les autres rôles, vérifier la hiérarchie
                foreach (self::ROLE_HIERARCHY as $parentRole => $childRoles) {
                    // Si le rôle est un enfant d'un rôle parent qui a la permission
                    if (in_array($role, $childRoles) && in_array($parentRole, $permissions[$permission])) {
                        return true;
                    }
                }
                
                return false;
            }
        }
        
        return false;
    }

    /**
     * Vérifie si un rôle est dans la hiérarchie d'un autre rôle
     */
    private function isRoleInHierarchy(string $role, string $childRole): bool
    {
        if ($role === $childRole) {
            return true;
        }
        
        if (!isset(self::ROLE_HIERARCHY[$role])) {
            return false;
        }
        
        return in_array($childRole, self::ROLE_HIERARCHY[$role]);
    }

    /**
     * Récupère tous les rôles accessibles à partir d'un rôle donné
     */
    public function getReachableRoles(string $role): array
    {
        $reachableRoles = [$role];
        
        if (isset(self::ROLE_HIERARCHY[$role])) {
            $reachableRoles = array_merge($reachableRoles, self::ROLE_HIERARCHY[$role]);
        }
        
        return $reachableRoles;
    }

    /**
     * Récupère toutes les permissions d'un rôle
     */
    public function getRolePermissions(string $role): array
    {
        $permissions = [];
        
        foreach (self::PERMISSIONS as $domain => $domainPermissions) {
            foreach ($domainPermissions as $permission => $roles) {
                if (in_array($role, $roles) || $this->hasPermission($role, $permission)) {
                    $permissions[] = $permission;
                }
            }
        }
        
        return $permissions;
    }
} 