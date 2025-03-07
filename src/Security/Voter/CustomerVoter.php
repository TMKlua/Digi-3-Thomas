<?php

namespace App\Security\Voter;

use App\Entity\Customers;
use App\Entity\User;
use App\Service\PermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les permissions sur les clients
 */
class CustomerVoter extends Voter
{
    // Définition des actions possibles sur un client
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const CREATE = 'create';

    private PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Détermine si ce voter supporte l'attribut et le sujet donnés
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        // Si l'attribut n'est pas l'un de ceux que nous supportons, retourner false
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::CREATE])) {
            return false;
        }

        // Pour CREATE, le sujet peut être null
        if ($attribute === self::CREATE) {
            return true;
        }

        // Si le sujet n'est pas un client, retourner false
        if (!$subject instanceof Customers) {
            return false;
        }

        return true;
    }

    /**
     * Détermine si l'utilisateur a le droit d'effectuer l'action demandée sur le sujet
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Si l'utilisateur n'est pas connecté, refuser l'accès
        if (!$user instanceof User) {
            return false;
        }

        // Pour CREATE, vérifier si l'utilisateur peut créer un client
        if ($attribute === self::CREATE) {
            return $this->permissionService->hasPermission('manage_customers');
        }

        /** @var Customers $customer */
        $customer = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($customer, $user),
            self::EDIT => $this->canEdit($customer, $user),
            self::DELETE => $this->canDelete($customer, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    /**
     * Vérifie si l'utilisateur peut voir le client
     */
    private function canView(Customers $customer, User $user): bool
    {
        // Les administrateurs et les utilisateurs avec la permission view_customers peuvent voir tous les clients
        if ($this->permissionService->canViewCustomerList()) {
            return true;
        }

        // Les chefs de projet peuvent voir les clients de leurs projets
        foreach ($customer->getProjects() as $project) {
            if ($project->getProjectManager() === $user) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur peut modifier le client
     */
    private function canEdit(Customers $customer, User $user): bool
    {
        return $this->permissionService->canEditCustomer();
    }

    /**
     * Vérifie si l'utilisateur peut supprimer le client
     */
    private function canDelete(Customers $customer, User $user): bool
    {
        // Vérifier si l'utilisateur a la permission de supprimer des clients
        if (!$this->permissionService->canDeleteCustomer()) {
            return false;
        }

        // Vérifier si le client a des projets
        if (count($customer->getProjects()) > 0) {
            // Ne pas autoriser la suppression si le client a des projets
            return false;
        }

        return true;
    }
} 