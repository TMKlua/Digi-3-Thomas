<?php

namespace App\Security\Voter;

use App\Entity\Project;
use App\Entity\User;
use App\Service\PermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les permissions sur les projets
 */
class ProjectVoter extends Voter
{
    // Définition des actions possibles sur un projet
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const CREATE = 'create';
    public const MANAGE_TASKS = 'manage_tasks';

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
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::CREATE, self::MANAGE_TASKS])) {
            return false;
        }

        // Pour CREATE, le sujet peut être null
        if ($attribute === self::CREATE) {
            return true;
        }

        // Si le sujet n'est pas un projet, retourner false
        if (!$subject instanceof Project) {
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

        // Pour CREATE, vérifier si l'utilisateur peut créer un projet
        if ($attribute === self::CREATE) {
            return $this->permissionService->canCreateProject();
        }

        /** @var Project $project */
        $project = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($project, $user),
            self::EDIT => $this->canEdit($project, $user),
            self::DELETE => $this->canDelete($project, $user),
            self::MANAGE_TASKS => $this->canManageTasks($project, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    /**
     * Vérifie si l'utilisateur peut voir le projet
     */
    private function canView(Project $project, User $user): bool
    {
        // Les administrateurs peuvent voir tous les projets
        if ($this->permissionService->hasPermission('view_all_projects')) {
            return true;
        }

        // Le chef de projet peut voir ses projets
        if ($project->getProjectManager() === $user) {
            return true;
        }

        // Les utilisateurs assignés à une tâche du projet peuvent voir le projet
        foreach ($project->getTasks() as $task) {
            if ($task->getTaskAssignedTo() === $user) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur peut modifier le projet
     */
    private function canEdit(Project $project, User $user): bool
    {
        return $this->permissionService->canEditProject($project);
    }

    /**
     * Vérifie si l'utilisateur peut supprimer le projet
     */
    private function canDelete(Project $project, User $user): bool
    {
        // Seuls les administrateurs et le chef de projet peuvent supprimer un projet
        if (!$this->permissionService->canDeleteProject()) {
            return false;
        }

        // Vérifier si le projet a des tâches
        if (count($project->getTasks()) > 0) {
            // Ne pas autoriser la suppression si le projet a des tâches
            return false;
        }

        return true;
    }

    /**
     * Vérifie si l'utilisateur peut gérer les tâches du projet
     */
    private function canManageTasks(Project $project, User $user): bool
    {
        // Les administrateurs peuvent gérer toutes les tâches
        if ($this->permissionService->hasPermission('manage_all_tasks')) {
            return true;
        }

        // Le chef de projet peut gérer les tâches de ses projets
        if ($project->getProjectManager() === $user) {
            return true;
        }

        // Les lead développeurs peuvent gérer les tâches des projets auxquels ils sont assignés
        if ($this->permissionService->hasPermission('manage_team_tasks')) {
            foreach ($project->getTasks() as $task) {
                if ($task->getTaskAssignedTo() === $user) {
                    return true;
                }
            }
        }

        return false;
    }
} 