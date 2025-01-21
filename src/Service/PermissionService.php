<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class PermissionService
{
    // Définition de la hiérarchie des rôles
    private const ROLE_HIERARCHY = [
        'ROLE_USER' => [
            'parent' => null,
            'permissions' => ['view_general_pages']
        ],
        'ROLE_DEVELOPER' => [
            'parent' => 'ROLE_USER',
            'permissions' => ['view_projects', 'edit_own_tasks']
        ],
        'ROLE_LEAD_DEVELOPER' => [
            'parent' => 'ROLE_DEVELOPER',
            'permissions' => ['manage_team_tasks']
        ],
        'ROLE_PROJECT_MANAGER' => [
            'parent' => 'ROLE_LEAD_DEVELOPER',
            'permissions' => ['edit_users', 'manage_projects', 'view_users']
        ],
        'ROLE_RESPONSABLE' => [
            'parent' => 'ROLE_PROJECT_MANAGER',
            'permissions' => ['delete_users', 'manage_all_projects', 'view_users']
        ],
        'ROLE_ADMIN' => [
            'parent' => 'ROLE_RESPONSABLE',
            'permissions' => ['*'] // Tous les droits
        ]
    ];

    public function __construct(
        private Security $security
    ) {}

    public function hasPermission(?User $user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        // Vérification sécurisée de getUserRole
        $userRole = $this->getUserRoleSafely($user);
        
        while ($userRole !== null) {
            $roleConfig = self::ROLE_HIERARCHY[$userRole];
            
            if (in_array('*', $roleConfig['permissions']) || 
                in_array($permission, $roleConfig['permissions'])) {
                return true;
            }
            
            $userRole = $roleConfig['parent'];
        }
        
        return false;
    }

    private function getUserRoleSafely(?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        // Vérification explicite et sécurisée
        try {
            $role = method_exists($user, 'getUserRole') 
                ? $user->getUserRole() 
                : ($user->getRoles()[0] ?? null);
            
            return $role ?: 'ROLE_USER';
        } catch (\Exception $e) {
            // Log de l'erreur si nécessaire
            return 'ROLE_USER';
        }
    }

    /**
     * Vérifie si l'utilisateur connecté peut voir la liste des utilisateurs
     */
    public function canViewUserList(): bool
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return false;
        }

        return $this->hasPermission($user, 'view_users');
    }

    /**
     * Vérifie si l'utilisateur connecté peut éditer des utilisateurs
     */
    public function canEditUser(): bool
    {
        $user = $this->security->getUser();
        return $this->hasPermission($user, 'edit_users');
    }

    /**
     * Vérifie si l'utilisateur connecté peut gérer un utilisateur spécifique
     */
    public function canManageUser(?User $userToManage): bool
    {
        $currentUser = $this->security->getUser();

        if (!$currentUser instanceof User || !$userToManage) {
            return false;
        }

        // Vérification des permissions basée sur la hiérarchie
        return $this->hasPermission($currentUser, 'edit_users') || 
               $this->hasPermission($currentUser, 'delete_users');
    }

    /**
     * Vérifie les permissions de suppression
     */
    public function canDeleteUser(?User $userToDelete = null): bool
    {
        $currentUser = $this->security->getUser();
        
        // Un utilisateur ne peut pas se supprimer lui-même
        if ($currentUser === $userToDelete) {
            return false;
        }

        return $this->hasPermission($currentUser, 'delete_users');
    }

    public function getAllowedRolesForList(string $listType): array
    {
        $rolesMap = [
            'users' => ['view_users'],
            'customers' => ['manage_customers'],
            'projects' => ['manage_projects']
        ];

        $allowedRoles = [];
        $currentRole = $this->getUserRoleSafely($this->security->getUser());

        foreach (self::ROLE_HIERARCHY as $role => $config) {
            // Si le rôle actuel est inférieur ou égal au rôle en cours de vérification
            if ($this->isRoleAllowed($currentRole, $role)) {
                $permissions = $config['permissions'];
                if (array_intersect($rolesMap[$listType], $permissions) || 
                    in_array('*', $permissions)) {
                    $allowedRoles[] = $role;
                }
            }
        }

        return $allowedRoles;
    }

    private function isRoleAllowed(string $currentRole, string $targetRole): bool
    {
        if ($currentRole === $targetRole) {
            return true;
        }

        $currentRoleConfig = self::ROLE_HIERARCHY[$currentRole];
        while ($currentRoleConfig['parent'] !== null) {
            if ($currentRoleConfig['parent'] === $targetRole) {
                return true;
            }
            $currentRoleConfig = self::ROLE_HIERARCHY[$currentRoleConfig['parent']];
        }

        return false;
    }

    public function canAccessList(string $listType): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) return false;

        $allowedRoles = $this->getAllowedRolesForList($listType);
        return in_array($user->getUserRole(), $allowedRoles);
    }
}