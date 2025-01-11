<?php

namespace App\Service;

use App\Entity\User; // Importez votre entité User
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PermissionService
{
    // Définition de la hiérarchie des rôles
    private const ROLE_HIERARCHY = [
        'ROLE_ADMIN' => 10,
        'ROLE_RESPONSABLE' => 9,
        'ROLE_PROJECT_MANAGER' => 8,
        'ROLE_LEAD_DEVELOPER' => 7,
        'ROLE_DEVELOPER' => 6,
        'ROLE_USER' => 5
    ];

    private $tokenStorage;
    private $authorizationChecker;

    /**
     * Constructeur avec injection des dépendances
     * 
     * @param TokenStorageInterface $tokenStorage Permet d'accéder à l'utilisateur connecté
     * @param AuthorizationCheckerInterface $authorizationChecker Vérifie les autorisations
     */
    public function __construct(
        TokenStorageInterface $tokenStorage, 
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Récupère l'utilisateur actuellement connecté
     * 
     * @return User|null L'utilisateur connecté ou null
     */
    private function getCurrentUser(): ?User
    {
        $token = $this->tokenStorage->getToken();
        return $token ? $token->getUser() : null;
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     * 
     * @param string $role Le rôle à vérifier
     * @return bool True si l'utilisateur a le rôle, false sinon
     */
    public function hasRole(string $role): bool
    {
        $user = $this->getCurrentUser();
        return $user && $user->getUserRole() === $role;
    }

    /**
     * Vérifie si l'utilisateur peut accéder aux pages de paramètres
     * 
     * @return bool True si l'accès est autorisé, false sinon
     */
    public function canAccessParameterPages(): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        // L'admin a un accès total
        if ($user->getUserRole() === 'ROLE_ADMIN') return true;

        $allowedRoles = [
            'ROLE_RESPONSABLE', 
            'ROLE_PROJECT_MANAGER'
        ];

        return in_array($user->getUserRole(), $allowedRoles);
    }

    /**
     * Vérifie si l'utilisateur peut accéder à la configuration
     * 
     * @return bool True si l'accès à la configuration est autorisé
     */
    public function canAccessConfiguration(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    /**
     * Vérifie si l'utilisateur peut gérer les projets
     * 
     * @return bool True si la gestion des projets est autorisée
     */
    public function canManageProjects(): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        // L'admin a un accès total
        if ($user->getUserRole() === 'ROLE_ADMIN') return true;

        $managementRoles = [
            'ROLE_PROJECT_MANAGER'
        ];

        return in_array($user->getUserRole(), $managementRoles);
    }

    /**
     * Vérifie si l'utilisateur peut gérer un autre utilisateur
     * 
     * @param User $targetUser L'utilisateur cible
     * @return bool True si la gestion est autorisée
     */
    public function canManageUser(User $targetUser): bool
    {
        $currentUser = $this->getCurrentUser();
        if (!$currentUser) return false;

        // L'admin peut tout faire
        if ($currentUser->getUserRole() === 'ROLE_ADMIN') return true;

        // Récupérer le niveau hiérarchique des rôles
        $currentUserLevel = self::ROLE_HIERARCHY[$currentUser->getUserRole()] ?? 0;
        $targetUserLevel = self::ROLE_HIERARCHY[$targetUser->getUserRole()] ?? 0;

        // Un utilisateur ne peut gérer que des utilisateurs de niveau inférieur
        return $currentUserLevel > $targetUserLevel;
    }

    /**
     * Vérifie si l'utilisateur peut voir la liste des utilisateurs
     * 
     * @return bool True si la visualisation est autorisée
     */
    public function canViewUserList(): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        // L'admin a un accès total
        if ($user->getUserRole() === 'ROLE_ADMIN') return true;

        $allowedRoles = [
            'ROLE_RESPONSABLE', 
            'ROLE_PROJECT_MANAGER'
        ];

        return in_array($user->getUserRole(), $allowedRoles);
    }

    /**
     * Vérifie si l'utilisateur peut voir la liste des clients
     * 
     * @return bool True si la visualisation est autorisée
     */
    public function canViewCustomerList(): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        // L'admin a un accès total
        if ($user->getUserRole() === 'ROLE_ADMIN') return true;

        $allowedRoles = [
            'ROLE_RESPONSABLE', 
            'ROLE_PROJECT_MANAGER'
        ];

        return in_array($user->getUserRole(), $allowedRoles);
    }

    /**
     * Vérifie si l'utilisateur peut éditer un client
     * 
     * @return bool True si l'édition est autorisée
     */
    public function canEditCustomer(): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        $editRoles = [
            'ROLE_ADMIN', 
            'ROLE_RESPONSABLE'
        ];

        return in_array($user->getUserRole(), $editRoles);
    }

    /**
     * Vérifie si l'utilisateur peut éditer un utilisateur
     * 
     * @return bool True si l'édition est autorisée
     */
    public function canEditUser(): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        $editRoles = [
            'ROLE_ADMIN', 
            'ROLE_RESPONSABLE'
        ];

        return in_array($user->getUserRole(), $editRoles);
    }

    /**
     * Obtient les rôles autorisés à voir un type de liste
     * 
     * @param string $listType Type de liste ('users', 'customers', etc.)
     * @return array Liste des rôles autorisés
     */
    public function getAllowedRolesForList(string $listType): array
    {
        $rolesMap = [
            'users' => ['ROLE_ADMIN', 'ROLE_RESPONSABLE', 'ROLE_PROJECT_MANAGER'],
            'customers' => ['ROLE_ADMIN', 'ROLE_RESPONSABLE', 'ROLE_PROJECT_MANAGER'],
            'projects' => ['ROLE_ADMIN', 'ROLE_RESPONSABLE', 'ROLE_PROJECT_MANAGER']
        ];

        return $rolesMap[$listType] ?? [];
    }

    /**
     * Vérifie si un utilisateur peut accéder à une liste spécifique
     * 
     * @param string $listType Type de liste
     * @return bool True si l'accès est autorisé
     */
    public function canAccessList(string $listType): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        $allowedRoles = $this->getAllowedRolesForList($listType);
        return in_array($user->getUserRole(), $allowedRoles);
    }
}
