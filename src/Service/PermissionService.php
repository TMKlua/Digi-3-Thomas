<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Customers;
use App\Service\RoleHierarchyService;
use Symfony\Bundle\SecurityBundle\Security;

class PermissionService
{
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
            'permissions' => ['view_users', 'view_customers', 'manage_projects']
        ],
        User::ROLE_RESPONSABLE => [
            'parent' => User::ROLE_PROJECT_MANAGER,
            'permissions' => [
                'edit_users', 
                'delete_users', 
                'manage_customers', 
                'manage_all_projects'
            ]
        ],
        User::ROLE_ADMIN => [
            'parent' => User::ROLE_RESPONSABLE,
            'permissions' => [
                '*',
                'manage_configuration'  // Permission spécifique pour la configuration
            ]
        ]
    ];

    private const PERMISSION_DOMAINS = [
        'user' => [
            'view' => 'view_users',
            'edit' => 'edit_users',
            'delete' => 'delete_users'
        ],
        'customer' => [
            'view' => 'view_customers',
            'edit' => 'manage_customers',
            'delete' => 'manage_customers'
        ],
        'project' => [
            'view' => 'view_projects',
            'edit' => 'manage_projects',
            'delete' => 'manage_all_projects'
        ],
        'configuration' => [
            'view' => 'manage_configuration',
            'edit' => 'manage_configuration',
            'delete' => 'manage_configuration'
        ]
    ];

    public function __construct(
        private Security $security,
        private RoleHierarchyService $roleHierarchy
    ) {}

    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user : null;
    }

    private function getUserRole(?User $user): ?string
    {
        if (!$user) return null;
        return $user->getUserRole();
    }

    public function hasPermission(?User $user, string $permission): bool
    {
        if (!$user) return false;
        return $this->roleHierarchy->hasPermission($user->getUserRole(), $permission);
    }

    private function checkPermissionInHierarchy(string $role, string $permission): bool
    {
        $config = self::ROLE_HIERARCHY[$role];
        
        if (in_array('*', $config['permissions']) || 
            in_array($permission, $config['permissions'])) {
            return true;
        }

        return $config['parent'] ? $this->checkPermissionInHierarchy($config['parent'], $permission) : false;
    }

    private function hasHigherOrEqualRole(string $currentRole, string $targetRole): bool
    {
        if ($currentRole === $targetRole) return true;
        
        $current = self::ROLE_HIERARCHY[$currentRole];
        while ($current['parent']) {
            if ($current['parent'] === $targetRole) return true;
            $current = self::ROLE_HIERARCHY[$current['parent']];
        }
        return false;
    }

    private function canManageEntity(string $domain, string $action, $entity = null): bool
    {
        $currentUser = $this->getCurrentUser();
        if (!$currentUser) return false;

        if (!isset(self::PERMISSION_DOMAINS[$domain][$action])) {
            return false;
        }

        $permission = self::PERMISSION_DOMAINS[$domain][$action];
        $hasPermission = $this->hasPermission($currentUser, $permission);

        if ($entity instanceof User) {
            return $hasPermission && $this->checkUserManagementRules($currentUser, $entity);
        }

        return $hasPermission;
    }

    private function checkUserManagementRules(User $currentUser, User $targetUser): bool
    {
        $currentRole = $this->getUserRole($currentUser);
        $targetRole = $this->getUserRole($targetUser);

        // Un utilisateur ne peut pas se gérer lui-même
        if ($currentUser->getId() === $targetUser->getId()) {
            return false;
        }

        // Admin peut gérer tout le monde sauf lui-même
        if ($currentRole === User::ROLE_ADMIN) {
            return true;
        }

        // Responsable peut gérer tous sauf admin et lui-même
        if ($currentRole === User::ROLE_RESPONSABLE) {
            return $targetRole !== User::ROLE_ADMIN;
        }

        // Project Manager ne peut voir que les rôles inférieurs
        if ($currentRole === User::ROLE_PROJECT_MANAGER) {
            return !$this->hasHigherOrEqualRole($targetRole, User::ROLE_PROJECT_MANAGER);
        }

        return false;
    }

    // API publique pour la gestion des utilisateurs
    public function canViewUserList(): bool
    {
        return $this->hasPermission($this->getCurrentUser(), 'view_users');
    }

    public function canViewUser(User $user): bool
    {
        return $this->canManageEntity('user', 'view', $user);
    }

    public function canEditUser(): bool
    {
        return $this->hasPermission($this->getCurrentUser(), 'edit_users');
    }

    public function canManageUser(User $user): bool
    {
        return $this->canManageEntity('user', 'edit', $user);
    }

    public function canDeleteUser(User $user): bool
    {
        $currentUser = $this->getCurrentUser();
        if (!$currentUser || $currentUser->getId() === $user->getId()) {
            return false;
        }
        
        return $this->hasPermission($currentUser, 'delete_users') &&
               $this->roleHierarchy->hasRole($currentUser->getUserRole(), $user->getUserRole());
    }

    // API publique pour la gestion des clients
    public function canViewCustomerList(): bool
    {
        return $this->hasPermission($this->getCurrentUser(), 'view_customers');
    }

    public function canViewCustomerForProjectManager(Customers $customer): bool
    {
        return $this->canManageEntity('customer', 'view', $customer);
    }

    public function canEditCustomer(): bool
    {
        return $this->hasPermission($this->getCurrentUser(), 'manage_customers');
    }

    public function canManageCustomer(Customers $customer): bool
    {
        return $this->canManageEntity('customer', 'edit', $customer);
    }

    public function canDeleteCustomer(): bool
    {
        return $this->hasPermission($this->getCurrentUser(), 'manage_customers');
    }

    public function canAccessConfiguration(): bool
    {
        return $this->hasPermission($this->getCurrentUser(), 'manage_configuration');
    }

    public function canViewConfiguration(): bool
    {
        return $this->canAccessConfiguration();
    }

    public function canEditConfiguration(): bool
    {
        return $this->canAccessConfiguration();
    }
}
