<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Enum\UserRole;
use App\Service\PermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TasksRepository;
use App\Repository\ProjectRepository;

/**
 * Voter pour les permissions sur les utilisateurs
 */
class UserVoter extends Voter
{
    // Définition des actions possibles sur un utilisateur
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const CREATE = 'create';
    public const CHANGE_ROLE = 'change_role';

    private PermissionService $permissionService;
    private TasksRepository $tasksRepository;
    private ProjectRepository $projectRepository;

    public function __construct(
        PermissionService $permissionService,
        EntityManagerInterface $entityManager
    ) {
        $this->permissionService = $permissionService;
        $this->tasksRepository = $entityManager->getRepository('App\Entity\Tasks');
        $this->projectRepository = $entityManager->getRepository('App\Entity\Project');
    }

    /**
     * Détermine si ce voter supporte l'attribut et le sujet donnés
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        // Si l'attribut n'est pas l'un de ceux que nous supportons, retourner false
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::CREATE, self::CHANGE_ROLE])) {
            return false;
        }

        // Pour CREATE, le sujet peut être null
        if ($attribute === self::CREATE) {
            return true;
        }

        // Si le sujet n'est pas un utilisateur, retourner false
        if (!$subject instanceof User) {
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

        // Pour CREATE, vérifier si l'utilisateur peut créer un utilisateur
        if ($attribute === self::CREATE) {
            return $this->permissionService->canManageUsers();
        }

        /** @var User $targetUser */
        $targetUser = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($targetUser, $user),
            self::EDIT => $this->canEdit($targetUser, $user),
            self::DELETE => $this->canDelete($targetUser, $user),
            self::CHANGE_ROLE => $this->canChangeRole($targetUser, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    /**
     * Vérifie si l'utilisateur peut voir l'utilisateur cible
     */
    private function canView(User $targetUser, User $user): bool
    {
        // L'utilisateur peut toujours se voir lui-même
        if ($targetUser === $user) {
            return true;
        }

        // Les administrateurs peuvent voir tous les utilisateurs
        if ($this->permissionService->canViewUserList()) {
            return true;
        }

        // Les chefs de projet peuvent voir les utilisateurs assignés à leurs projets
        if ($this->permissionService->hasPermission('view_team_members')) {
            // Récupérer les tâches assignées à l'utilisateur cible
            $assignedTasks = $this->tasksRepository->findBy(['taskAssignedTo' => $targetUser]);
            
            foreach ($assignedTasks as $task) {
                if ($task->getTaskProject()->getProjectManager() === $user) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur peut modifier l'utilisateur cible
     */
    private function canEdit(User $targetUser, User $user): bool
    {
        // L'utilisateur peut toujours se modifier lui-même (sauf son rôle)
        if ($targetUser === $user) {
            return true;
        }

        // Seuls les administrateurs peuvent modifier d'autres utilisateurs
        return $this->permissionService->canEditUser();
    }

    /**
     * Vérifie si l'utilisateur peut supprimer l'utilisateur cible
     */
    private function canDelete(User $targetUser, User $user): bool
    {
        // Un utilisateur ne peut pas se supprimer lui-même
        if ($targetUser === $user) {
            return false;
        }

        // Vérifier si l'utilisateur a la permission de supprimer des utilisateurs
        if (!$this->permissionService->canManageUsers()) {
            return false;
        }

        // Vérifier si l'utilisateur a des tâches assignées
        $assignedTasks = $this->tasksRepository->findBy(['taskAssignedTo' => $targetUser]);
        if (count($assignedTasks) > 0) {
            return false;
        }

        // Vérifier si l'utilisateur est chef de projet
        $managedProjects = $this->projectRepository->findByManager($targetUser);
        if (count($managedProjects) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Vérifie si l'utilisateur peut changer le rôle de l'utilisateur cible
     */
    private function canChangeRole(User $targetUser, User $user): bool
    {
        // Un utilisateur ne peut pas changer son propre rôle
        if ($targetUser === $user) {
            return false;
        }

        // Seuls les administrateurs peuvent changer les rôles
        return $this->permissionService->canManageRoles();
    }
} 