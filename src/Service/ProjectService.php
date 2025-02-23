<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ProjectService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectRepository $projectRepository,
        private PermissionService $permissionService,
        private Security $security
    ) {}

    public function createProject(array $data): Project
    {
        if (!$this->permissionService->hasPermission('create_projects')) {
            throw new \RuntimeException('Permission denied to create project');
        }

        $project = new Project();
        $project->setProjectName($data['name']);
        $project->setProjectDescription($data['description'] ?? '');
        $project->setProjectStartDate($data['startDate'] ?? new \DateTime());
        $project->setProjectTargetDate($data['endDate'] ?? new \DateTime('+1 month'));
        $project->setProjectStatus('new');

        /** @var User $user */
        $user = $this->security->getUser();
        $project->setProjectManager($user);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }

    public function updateProject(int $id, array $data): Project
    {
        $project = $this->projectRepository->find($id);
        if (!$project) {
            throw new \RuntimeException('Project not found');
        }

        if (!$this->permissionService->hasPermission('edit_projects')) {
            throw new \RuntimeException('Permission denied to edit project');
        }

        if (isset($data['name'])) {
            $project->setProjectName($data['name']);
        }
        if (isset($data['description'])) {
            $project->setProjectDescription($data['description']);
        }
        if (isset($data['status'])) {
            $project->setProjectStatus($data['status']);
        }
        if (isset($data['startDate'])) {
            $project->setProjectStartDate($data['startDate']);
        }
        if (isset($data['endDate'])) {
            $project->setProjectTargetDate($data['endDate']);
        }

        $project->setProjectUpdatedAt(new \DateTime());
        $project->setProjectUpdatedBy($this->security->getUser());

        $this->entityManager->flush();

        return $project;
    }

    public function deleteProject(int $id): void
    {
        $project = $this->projectRepository->find($id);
        if (!$project) {
            throw new \RuntimeException('Project not found');
        }

        if (!$this->permissionService->hasPermission('delete_project')) {
            throw new \RuntimeException('Permission denied to delete project');
        }

        $this->entityManager->remove($project);
        $this->entityManager->flush();
    }

    public function getProjectsByUser(User $user): array
    {
        return $this->projectRepository->findByManager($user);
    }

    public function getActiveProjects(): array
    {
        return $this->projectRepository->findByStatus('active');
    }

    public function getProjectWithFullData(int $id): ?Project
    {
        return $this->projectRepository->findProjectWithFullData($id);
    }

    public function searchProjects(array $filters): array
    {
        return $this->projectRepository->searchProjects($filters);
    }
} 