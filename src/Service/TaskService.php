<?php

namespace App\Service;

use App\Entity\Tasks;
use App\Entity\User;
use App\Entity\Project;
use App\Repository\TasksRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class TaskService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TasksRepository $taskRepository,
        private PermissionService $permissionService,
        private Security $security
    ) {}

    public function createTask(array $data): Tasks
    {
        if (!$this->permissionService->hasPermission('create_task')) {
            throw new \RuntimeException('Permission denied to create task');
        }

        $task = new Tasks();
        $task->setTaskName($data['name']);
        $task->setTaskDescription($data['description'] ?? '');
        $task->setTaskStatus($data['status'] ?? 'new');
        $task->setTaskPriority($data['priority'] ?? 'medium');
        $task->setTaskStartDate($data['startDate'] ?? new \DateTime());
        $task->setTaskTargetDate($data['targetDate'] ?? new \DateTime('+1 week'));

        if (isset($data['project'])) {
            $task->setTaskProject($data['project']);
        }
        if (isset($data['assignedTo'])) {
            $task->setTaskAssignedTo($data['assignedTo']);
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    public function updateTaskStatus(Tasks $task, string $newStatus): Tasks
    {
        if (!$this->permissionService->hasPermission('edit_task')) {
            throw new \RuntimeException('Permission denied to update task status');
        }

        $task->setTaskStatus($newStatus);
        $task->setTaskUpdatedAt(new \DateTime());
        $task->setTaskUpdatedBy($this->security->getUser());

        $this->entityManager->flush();

        return $task;
    }

    public function assignTask(Tasks $task, User $assignee): Tasks
    {
        if (!$this->permissionService->hasPermission('assign_task')) {
            throw new \RuntimeException('Permission denied to assign task');
        }

        $task->setTaskAssignedTo($assignee);
        $task->setTaskUpdatedAt(new \DateTime());
        $task->setTaskUpdatedBy($this->security->getUser());

        $this->entityManager->flush();

        return $task;
    }

    public function getTasksByProject(Project $project): array
    {
        return $this->taskRepository->findByProject($project);
    }

    public function getTasksByAssignee(User $user): array
    {
        return $this->taskRepository->findByAssignedUser($user);
    }

    public function getTasksByStatus(string $status): array
    {
        return $this->taskRepository->findByStatus($status);
    }

    public function getTasksByPriority(string $priority): array
    {
        return $this->taskRepository->findByPriority($priority);
    }

    public function getTaskWithFullData(int $id): ?Tasks
    {
        return $this->taskRepository->findTaskWithFullData($id);
    }

    public function searchTasks(array $filters): array
    {
        return $this->taskRepository->searchTasks($filters);
    }
} 