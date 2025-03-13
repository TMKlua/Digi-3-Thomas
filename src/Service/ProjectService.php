<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Service centralisé pour la gestion des projets
 */
class ProjectService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectRepository $projectRepository,
        private PermissionService $permissionService,
        private SecurityService $securityService
    ) {
    }

    /**
     * Récupère un projet par son ID
     */
    public function getProjectById(int $id): ?Project
    {
        return $this->projectRepository->find($id);
    }

    /**
     * Sauvegarde un projet
     */
    public function saveProject(Project $project): Project
    {
        // Si le projet n'a pas de date de début, définir la date actuelle
        if (!$project->getProjectStartDate()) {
            $project->setProjectStartDate(new \DateTime());
        }
        
        // Si le projet n'a pas de chef de projet, définir l'utilisateur courant
        if (!$project->getProjectManager()) {
            $project->setProjectManager($this->security->getUser());
        }
        
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        
        return $project;
    }

    /**
     * Supprime un projet
     */
    public function deleteProject(Project $project): void
    {
        $this->entityManager->remove($project);
        $this->entityManager->flush();
    }

    /**
     * Récupère tous les projets pour l'utilisateur courant
     */
    public function getProjectsForCurrentUser(): array
    {
        $user = $this->security->getUser();
        
        // Si l'utilisateur peut voir tous les projets, retourner tous les projets
        if ($this->permissionService->hasPermission('view_all_projects')) {
            return $this->projectRepository->findAll();
        }
        
        // Sinon, retourner les projets dont l'utilisateur est chef de projet
        return $this->projectRepository->findBy(['projectManager' => $user]);
    }

    /**
     * Récupère les projets par chef de projet
     */
    public function getProjectsByManager(User $manager): array
    {
        return $this->projectRepository->findBy(['projectManager' => $manager]);
    }

    /**
     * Récupère les projets par statut
     */
    public function getProjectsByStatus(string $status): array
    {
        return $this->projectRepository->findBy(['projectStatus' => $status]);
    }

    /**
     * Récupère les projets par client
     */
    public function getProjectsByCustomer(int $customerId): array
    {
        return $this->projectRepository->findBy(['projectCustomer' => $customerId]);
    }

    /**
     * Récupère les statistiques des projets
     */
    public function getProjectStatistics(): array
    {
        $stats = [
            'total' => $this->projectRepository->count([]),
            'by_status' => [],
            'recent' => $this->projectRepository->findBy([], ['projectStartDate' => 'DESC'], 5),
            'ending_soon' => $this->projectRepository->findEndingSoon(30),
        ];
        
        // Statistiques par statut
        $statusStats = $this->projectRepository->countByStatus();
        foreach ($statusStats as $statusStat) {
            $stats['by_status'][$statusStat['status']] = $statusStat['count'];
        }
        
        return $stats;
    }

    /**
     * Recherche des projets
     */
    public function searchProjects(string $term): array
    {
        return $this->projectRepository->search($term);
    }

    /**
     * Vérifie si un utilisateur est membre d'un projet
     */
    public function isUserProjectMember(User $user, Project $project): bool
    {
        // L'utilisateur est membre s'il est chef de projet
        if ($project->getProjectManager() === $user) {
            return true;
        }
        
        // L'utilisateur est membre s'il est assigné à une tâche du projet
        foreach ($project->getTasks() as $task) {
            if ($task->getTaskAssignedTo() === $user) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Récupère les membres d'un projet
     */
    public function getProjectMembers(Project $project): array
    {
        $members = [];
        
        // Ajouter le chef de projet
        $manager = $project->getProjectManager();
        if ($manager) {
            $members[$manager->getId()] = $manager;
        }
        
        // Ajouter les utilisateurs assignés aux tâches
        foreach ($project->getTasks() as $task) {
            $assignee = $task->getTaskAssignedTo();
            if ($assignee) {
                $members[$assignee->getId()] = $assignee;
            }
        }
        
        return array_values($members);
    }
} 