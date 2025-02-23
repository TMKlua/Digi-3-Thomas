<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Customers;
use App\Entity\Project;
use App\Entity\Tasks;
use Symfony\Bundle\SecurityBundle\Security;

class PermissionService
{
    public function __construct(
        private Security $security,
        private RoleHierarchyService $roleHierarchy
    ) {}

    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user : null;
    }

    public function hasPermission(string $permission): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;
        
        return $this->roleHierarchy->hasPermission($user->getUserRole(), $permission);
    }

    // Gestion des projets
    public function canCreateProject(): bool
    {
        return $this->hasPermission('create_projects');
    }

    public function canEditProject(Project $project): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        if ($this->hasPermission('edit_projects')) {
            return true;
        }

        return $project->getProjectManager() === $user;
    }

    public function canDeleteProject(): bool
    {
        return $this->hasPermission('delete_projects');
    }

    // Gestion des tâches
    public function canCreateTask(Project $project): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        return $this->hasPermission('create_project_tasks') || 
               $project->getProjectManager() === $user;
    }

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

    public function canAssignTask(): bool
    {
        return $this->hasPermission('assign_tasks');
    }

    // Gestion des clients
    public function canViewCustomerList(): bool
    {
        return $this->hasPermission('view_customers');
    }

    public function canEditCustomer(): bool
    {
        return $this->hasPermission('manage_customers');
    }

    public function canDeleteCustomer(): bool
    {
        return $this->hasPermission('manage_customers');
    }

    // Gestion des utilisateurs
    public function canViewUserList(): bool
    {
        return $this->hasPermission('manage_users');
    }

    public function canEditUser(): bool
    {
        return $this->hasPermission('manage_users');
    }

    public function canManageUsers(): bool
    {
        return $this->hasPermission('manage_users');
    }

    public function canManageRoles(): bool
    {
        return $this->hasPermission('manage_roles');
    }

    // Configuration système
    public function canViewConfiguration(): bool
    {
        return $this->hasPermission('manage_configuration');
    }

    public function canEditConfiguration(): bool
    {
        return $this->hasPermission('manage_configuration');
    }

    public function canAccessConfiguration(): bool
    {
        return $this->hasPermission('manage_configuration');
    }

    // Statistiques
    public function canViewStatistics(): bool
    {
        return $this->hasPermission('view_global_statistics') || 
               $this->hasPermission('view_project_statistics');
    }

    public function canViewGlobalStatistics(): bool
    {
        return $this->hasPermission('view_global_statistics');
    }

    public function canViewProjectStatistics(): bool
    {
        return $this->hasPermission('view_project_statistics');
    }

    // Gestion des commentaires et pièces jointes
    public function canAddComment(Tasks $task): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        return $task->getTaskAssignedTo() === $user && 
               $this->hasPermission('comment_on_assigned_tasks');
    }

    public function canAddAttachment(Tasks $task): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        return $task->getTaskAssignedTo() === $user && 
               $this->hasPermission('upload_task_attachments');
    }
}
