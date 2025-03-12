<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Customers;
use App\Entity\Project;
use App\Entity\Tasks;
use App\Enum\UserRole;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Service de gestion des permissions utilisateur
 * 
 * Ce service centralise toutes les vérifications de permissions dans l'application.
 * Il utilise le service RoleHierarchyService pour déterminer les permissions en fonction du rôle de l'utilisateur.
 */
class PermissionService
{
    public function __construct(
        private Security $security,
        private RoleHierarchyService $roleHierarchy
    ) {}

    /**
     * Récupère l'utilisateur actuellement connecté
     * 
     * @return User|null L'utilisateur connecté ou null si aucun utilisateur n'est connecté
     */
    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user : null;
    }

    /**
     * Vérifie si l'utilisateur courant possède une permission spécifique
     * 
     * @param string $permission La permission à vérifier
     * @return bool True si l'utilisateur a la permission, false sinon
     */
    public function hasPermission(string $permission): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;
        
        $userRole = $user->getUserRole()->value;
        return $this->roleHierarchy->hasPermission($userRole, $permission);
    }

    // Gestion des projets
    
    /**
     * Vérifie si l'utilisateur peut créer un projet
     * 
     * @return bool True si l'utilisateur peut créer un projet, false sinon
     */
    public function canCreateProject(): bool
    {
        return $this->hasPermission('create_projects');
    }

    /**
     * Vérifie si l'utilisateur peut modifier un projet
     * 
     * @param Project $project Le projet à modifier
     * @return bool True si l'utilisateur peut modifier le projet, false sinon
     */
    public function canEditProject(Project $project): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        if ($this->hasPermission('edit_projects')) {
            return true;
        }

        return $project->getProjectManager() === $user;
    }

    /**
     * Vérifie si l'utilisateur peut supprimer un projet
     * 
     * @return bool True si l'utilisateur peut supprimer un projet, false sinon
     */
    public function canDeleteProject(): bool
    {
        return $this->hasPermission('delete_projects');
    }

    /**
     * Vérifie si l'utilisateur peut voir un projet
     * 
     * @param Project $project Le projet à consulter
     * @return bool True si l'utilisateur peut voir le projet, false sinon
     */
    public function canViewProject(Project $project): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        if ($this->hasPermission('view_all_projects')) {
            return true;
        }

        if ($project->getProjectManager() === $user) {
            return true;
        }

        // Vérifier si l'utilisateur est assigné à une tâche du projet
        foreach ($project->getTasks() as $task) {
            if ($task->getTaskAssignedTo() === $user) {
                return true;
            }
        }

        return false;
    }

    // Gestion des tâches
    
    /**
     * Vérifie si l'utilisateur peut voir une tâche
     * 
     * @param Tasks $task La tâche à consulter
     * @return bool True si l'utilisateur peut voir la tâche, false sinon
     */
    public function canViewTask(Tasks $task): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        if ($this->hasPermission('view_all_tasks')) {
            return true;
        }

        if ($task->getTaskAssignedTo() === $user) {
            return true;
        }

        $project = $task->getTaskProject();
        if ($project && $project->getProjectManager() === $user) {
            return true;
        }

        return false;
    }
    
    /**
     * Vérifie si l'utilisateur peut créer une tâche dans un projet
     * 
     * @param Project $project Le projet dans lequel créer la tâche
     * @return bool True si l'utilisateur peut créer une tâche, false sinon
     */
    public function canCreateTask(Project $project): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        return $this->hasPermission('create_project_tasks') || 
               $project->getProjectManager() === $user;
    }

    /**
     * Vérifie si l'utilisateur peut modifier une tâche
     * 
     * @param Tasks $task La tâche à modifier
     * @return bool True si l'utilisateur peut modifier la tâche, false sinon
     */
    public function canEditTask(Tasks $task): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        if ($this->hasPermission('manage_team_tasks')) {
            return true;
        }

        return $task->getTaskAssignedTo() === $user && 
               $this->hasPermission('edit_own_tasks');
    }

    /**
     * Vérifie si l'utilisateur peut assigner une tâche
     * 
     * @return bool True si l'utilisateur peut assigner une tâche, false sinon
     */
    public function canAssignTask(): bool
    {
        return $this->hasPermission('assign_tasks');
    }

    // Gestion des clients
    
    /**
     * Vérifie si l'utilisateur peut voir la liste des clients
     * 
     * @return bool True si l'utilisateur peut voir la liste des clients, false sinon
     */
    public function canViewCustomerList(): bool
    {
        return $this->hasPermission('view_customers');
    }

    /**
     * Vérifie si l'utilisateur peut modifier un client
     * 
     * @return bool True si l'utilisateur peut modifier un client, false sinon
     */
    public function canEditCustomer(): bool
    {
        return $this->hasPermission('manage_customers');
    }

    /**
     * Vérifie si l'utilisateur peut supprimer un client
     * 
     * @return bool True si l'utilisateur peut supprimer un client, false sinon
     */
    public function canDeleteCustomer(): bool
    {
        return $this->hasPermission('manage_customers');
    }

    // Gestion des utilisateurs
    
    /**
     * Vérifie si l'utilisateur peut voir la liste des utilisateurs
     * 
     * @return bool True si l'utilisateur peut voir la liste des utilisateurs, false sinon
     */
    public function canViewUserList(): bool
    {
        return $this->hasPermission('manage_users');
    }

    /**
     * Vérifie si l'utilisateur peut modifier un utilisateur
     * 
     * @return bool True si l'utilisateur peut modifier un utilisateur, false sinon
     */
    public function canEditUser(): bool
    {
        return $this->hasPermission('manage_users');
    }

    /**
     * Vérifie si l'utilisateur peut gérer les utilisateurs
     * 
     * @return bool True si l'utilisateur peut gérer les utilisateurs, false sinon
     */
    public function canManageUsers(): bool
    {
        return $this->hasPermission('manage_users');
    }

    /**
     * Vérifie si l'utilisateur peut gérer les rôles
     * 
     * @return bool True si l'utilisateur peut gérer les rôles, false sinon
     */
    public function canManageRoles(): bool
    {
        return $this->hasPermission('manage_roles');
    }

    // Configuration système
    
    /**
     * Vérifie si l'utilisateur peut voir la configuration
     * 
     * @return bool True si l'utilisateur peut voir la configuration, false sinon
     */
    public function canViewConfiguration(): bool
    {
        return $this->hasPermission('manage_configuration');
    }

    /**
     * Vérifie si l'utilisateur peut modifier la configuration
     * 
     * @return bool True si l'utilisateur peut modifier la configuration, false sinon
     */
    public function canEditConfiguration(): bool
    {
        return $this->hasPermission('manage_configuration');
    }

    /**
     * Vérifie si l'utilisateur peut accéder à la configuration
     * 
     * @return bool True si l'utilisateur peut accéder à la configuration, false sinon
     */
    public function canAccessConfiguration(): bool
    {
        return $this->hasPermission('manage_configuration');
    }

    // Statistiques
    
    /**
     * Vérifie si l'utilisateur peut voir les statistiques
     * 
     * @return bool True si l'utilisateur peut voir les statistiques, false sinon
     */
    public function canViewStatistics(): bool
    {
        return $this->hasPermission('view_global_statistics') || 
               $this->hasPermission('view_project_statistics');
    }

    /**
     * Vérifie si l'utilisateur peut voir les statistiques globales
     * 
     * @return bool True si l'utilisateur peut voir les statistiques globales, false sinon
     */
    public function canViewGlobalStatistics(): bool
    {
        return $this->hasPermission('view_global_statistics');
    }

    /**
     * Vérifie si l'utilisateur peut voir les statistiques de projet
     * 
     * @return bool True si l'utilisateur peut voir les statistiques de projet, false sinon
     */
    public function canViewProjectStatistics(): bool
    {
        return $this->hasPermission('view_project_statistics');
    }

    // Gestion des commentaires et pièces jointes
    
    /**
     * Vérifie si l'utilisateur peut ajouter un commentaire à une tâche
     * 
     * @param Tasks $task La tâche concernée
     * @return bool True si l'utilisateur peut ajouter un commentaire, false sinon
     */
    public function canAddComment(Tasks $task): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        return $task->getTaskAssignedTo() === $user && 
               $this->hasPermission('comment_on_assigned_tasks');
    }

    /**
     * Vérifie si l'utilisateur peut ajouter une pièce jointe à une tâche
     * 
     * @param Tasks $task La tâche concernée
     * @return bool True si l'utilisateur peut ajouter une pièce jointe, false sinon
     */
    public function canAddAttachment(Tasks $task): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        return $task->getTaskAssignedTo() === $user && 
               $this->hasPermission('upload_task_attachments');
    }
}
