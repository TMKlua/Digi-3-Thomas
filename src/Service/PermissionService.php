<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Customers;
use Symfony\Bundle\SecurityBundle\Security;

class PermissionService
{
    // Utilisation des constantes de User
    private const ROLE_HIERARCHY = [
        User::ROLE_USER => [
            'parent' => null,
            'permissions' => ['view_general_pages']
        ],
        User::ROLE_DEVELOPER => [
            'parent' => User::ROLE_USER,
            'permissions' => ['view_projects', 'edit_own_tasks']
        ],
        User::ROLE_LEAD_DEVELOPER => [
            'parent' => User::ROLE_DEVELOPER,
            'permissions' => ['manage_team_tasks']
        ],
        User::ROLE_PROJECT_MANAGER => [
            'parent' => User::ROLE_LEAD_DEVELOPER,
            'permissions' => ['edit_users', 'manage_projects', 'view_users']
        ],
        User::ROLE_RESPONSABLE => [
            'parent' => User::ROLE_PROJECT_MANAGER,
            'permissions' => ['delete_users', 'manage_all_projects', 'view_users']
        ],
        User::ROLE_ADMIN => [
            'parent' => User::ROLE_RESPONSABLE,
            'permissions' => ['*']
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
        if (!$user instanceof User) {
            return false;
        }

        return in_array($user->getUserRole(), [
            User::ROLE_PROJECT_MANAGER,
            User::ROLE_RESPONSABLE,
            User::ROLE_ADMIN
        ]);
    }

    /**
     * Vérifie si l'utilisateur connecté peut éditer des utilisateurs
     */
    public function canEditUser(): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return in_array($user->getUserRole(), [
            User::ROLE_RESPONSABLE,
            User::ROLE_ADMIN
        ]);
    }

    /**
     * Vérifie si l'utilisateur connecté peut gérer un utilisateur spécifique
     */
    public function canManageUser(User $targetUser): bool
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            return false;
        }

        // Un admin peut tout faire
        if ($currentUser->getUserRole() === User::ROLE_ADMIN) {
            return true;
        }

        // Un responsable peut gérer tous les utilisateurs sauf les admins
        if ($currentUser->getUserRole() === User::ROLE_RESPONSABLE) {
            return $targetUser->getUserRole() !== User::ROLE_ADMIN;
        }

        // Un chef de projet peut gérer les développeurs et utilisateurs
        if ($currentUser->getUserRole() === User::ROLE_PROJECT_MANAGER) {
            return in_array($targetUser->getUserRole(), [
                User::ROLE_USER,
                User::ROLE_DEVELOPER
            ]);
        }

        return false;
    }

    /**
     * Vérifie les permissions de suppression
     */
    public function canDeleteUser(User $targetUser): bool
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            return false;
        }

        // Un admin peut supprimer n'importe qui sauf lui-même
        if ($currentUser->getUserRole() === User::ROLE_ADMIN) {
            return $currentUser->getId() !== $targetUser->getId();
        }

        // Un responsable peut supprimer tous les utilisateurs sauf les admins et lui-même
        if ($currentUser->getUserRole() === User::ROLE_RESPONSABLE) {
            return $targetUser->getUserRole() !== User::ROLE_ADMIN 
                && $currentUser->getId() !== $targetUser->getId();
        }

        return false;
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

    public function canViewUser(User $userToView): bool
    {
        $currentUser = $this->security->getUser();
        
        if (!$currentUser instanceof User) {
            return false;
        }

        $roleHierarchy = [
            User::ROLE_USER => 1,
            User::ROLE_DEVELOPER => 2,
            User::ROLE_LEAD_DEVELOPER => 3,
            User::ROLE_PROJECT_MANAGER => 4,
            User::ROLE_RESPONSABLE => 5,
            User::ROLE_ADMIN => 6
        ];

        if ($currentUser->getUserRole() === User::ROLE_PROJECT_MANAGER) {
            return $roleHierarchy[$userToView->getUserRole()] < $roleHierarchy[User::ROLE_PROJECT_MANAGER];
        }

        $viewRoles = [
            User::ROLE_RESPONSABLE, 
            User::ROLE_ADMIN
        ];

        return in_array($currentUser->getUserRole(), $viewRoles);
    }

    public function canViewUserListForProjectManager(User $userToView): bool
    {
        $currentUser = $this->security->getUser();
        
        if (!$currentUser instanceof User) {
            return false;
        }

        // Hiérarchie des rôles pour comparaison
        $roleHierarchy = [
            User::ROLE_USER => 1,
            User::ROLE_DEVELOPER => 2,
            User::ROLE_LEAD_DEVELOPER => 3,
            User::ROLE_PROJECT_MANAGER => 4,
            User::ROLE_RESPONSABLE => 5,
            User::ROLE_ADMIN => 6
        ];

        // Vérification spécifique pour le Chef de Projet
        if ($currentUser->getUserRole() === User::ROLE_PROJECT_MANAGER) {
            // Le Chef de Projet peut voir uniquement les rôles inférieurs
            return $roleHierarchy[$userToView->getUserRole()] < $roleHierarchy[User::ROLE_PROJECT_MANAGER];
        }

        // Pour les autres rôles, utiliser la méthode existante
        return $this->canViewUser($userToView);
    }

    // Nouvelles méthodes pour la gestion des clients
    public function canViewCustomerList(): bool
    {
        $user = $this->security->getUser();
        return $user instanceof User && in_array($user->getUserRole(), [
            User::ROLE_PROJECT_MANAGER,
            User::ROLE_RESPONSABLE,
            User::ROLE_ADMIN
        ]);
    }

    public function canViewCustomerForProjectManager(Customers $customer): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) return false;
        
        return in_array($user->getUserRole(), [
            User::ROLE_ADMIN, 
            User::ROLE_RESPONSABLE,
            User::ROLE_PROJECT_MANAGER
        ]);
    }

    public function canEditCustomer(): bool
    {
        $user = $this->security->getUser();
        return $user instanceof User && in_array($user->getUserRole(), [
            User::ROLE_ADMIN
        ]);
    }

    public function canManageCustomer(Customers $customer): bool
    {
        return $this->canEditCustomer();
    }

    public function canDeleteCustomer(): bool
    {
        $user = $this->security->getUser();
        return $user instanceof User && $user->getUserRole() === User::ROLE_ADMIN;
    }
}
