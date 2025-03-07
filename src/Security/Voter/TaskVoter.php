<?php

namespace App\Security\Voter;

use App\Entity\Tasks;
use App\Entity\User;
use App\Entity\Project;
use App\Service\PermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les permissions sur les tâches
 */
class TaskVoter extends Voter
{
    // Définition des actions possibles sur une tâche
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const CREATE = 'create';
    public const CHANGE_STATUS = 'change_status';
    public const ASSIGN = 'assign';

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
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::CREATE, self::CHANGE_STATUS, self::ASSIGN])) {
            return false;
        }

        // Pour CREATE, le sujet peut être null ou un Project
        if ($attribute === self::CREATE) {
            return $subject === null || $subject instanceof Project;
        }

        // Si le sujet n'est pas une tâche, retourner false
        if (!$subject instanceof Tasks) {
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

        // Pour CREATE, vérifier si l'utilisateur peut créer une tâche
        if ($attribute === self::CREATE) {
            // Si le sujet est un projet, on vérifie si l'utilisateur peut créer une tâche dans ce projet
            if ($subject instanceof Project) {
                return $this->permissionService->canCreateTask($subject);
            }
            // Sinon, on refuse l'accès car on a besoin d'un projet pour créer une tâche
            return false;
        }

        /** @var Tasks $task */
        $task = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($task, $user),
            self::EDIT => $this->canEdit($task, $user),
            self::DELETE => $this->canDelete($task, $user),
            self::CHANGE_STATUS => $this->canChangeStatus($task, $user),
            self::ASSIGN => $this->canAssign($task, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    /**
     * Vérifie si l'utilisateur peut voir la tâche
     */
    private function canView(Tasks $task, User $user): bool
    {
        // Les administrateurs peuvent voir toutes les tâches
        if ($this->permissionService->hasPermission('view_all_tasks')) {
            return true;
        }

        // Le chef de projet peut voir les tâches de ses projets
        if ($task->getTaskProject()->getProjectManager() === $user) {
            return true;
        }

        // L'utilisateur assigné à la tâche peut la voir
        if ($task->getTaskAssignedTo() === $user) {
            return true;
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur peut modifier la tâche
     */
    private function canEdit(Tasks $task, User $user): bool
    {
        // Les administrateurs peuvent modifier toutes les tâches
        if ($this->permissionService->hasPermission('edit_all_tasks')) {
            return true;
        }

        // Le chef de projet peut modifier les tâches de ses projets
        if ($task->getTaskProject()->getProjectManager() === $user) {
            return true;
        }

        // Les lead développeurs peuvent modifier les tâches de leur équipe
        if ($this->permissionService->hasPermission('manage_team_tasks')) {
            // Vérifier si l'utilisateur est assigné à une tâche du même projet
            foreach ($task->getTaskProject()->getTasks() as $projectTask) {
                if ($projectTask->getTaskAssignedTo() === $user) {
                    return true;
                }
            }
        }

        // L'utilisateur assigné à la tâche peut la modifier
        if ($task->getTaskAssignedTo() === $user) {
            return true;
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur peut supprimer la tâche
     */
    private function canDelete(Tasks $task, User $user): bool
    {
        // Seuls les administrateurs et le chef de projet peuvent supprimer une tâche
        if ($this->permissionService->hasPermission('delete_tasks')) {
            return true;
        }

        // Le chef de projet peut supprimer les tâches de ses projets
        if ($task->getTaskProject()->getProjectManager() === $user) {
            return true;
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur peut changer le statut de la tâche
     */
    private function canChangeStatus(Tasks $task, User $user): bool
    {
        // Les administrateurs peuvent changer le statut de toutes les tâches
        if ($this->permissionService->hasPermission('change_task_status')) {
            return true;
        }

        // Le chef de projet peut changer le statut des tâches de ses projets
        if ($task->getTaskProject()->getProjectManager() === $user) {
            return true;
        }

        // L'utilisateur assigné à la tâche peut changer son statut
        if ($task->getTaskAssignedTo() === $user) {
            return true;
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur peut assigner la tâche
     */
    private function canAssign(Tasks $task, User $user): bool
    {
        // Les administrateurs peuvent assigner toutes les tâches
        if ($this->permissionService->hasPermission('assign_tasks')) {
            return true;
        }

        // Le chef de projet peut assigner les tâches de ses projets
        if ($task->getTaskProject()->getProjectManager() === $user) {
            return true;
        }

        // Les lead développeurs peuvent assigner les tâches de leur équipe
        if ($this->permissionService->hasPermission('manage_team_tasks')) {
            // Vérifier si l'utilisateur est assigné à une tâche du même projet
            foreach ($task->getTaskProject()->getTasks() as $projectTask) {
                if ($projectTask->getTaskAssignedTo() === $user) {
                    return true;
                }
            }
        }

        return false;
    }
} 