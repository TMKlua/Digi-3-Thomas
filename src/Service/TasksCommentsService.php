<?php

namespace App\Service;

use App\Entity\Tasks;
use App\Entity\TasksComments;
use App\Repository\TasksCommentsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class TasksCommentsService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TasksCommentsRepository $commentsRepository,
        private PermissionService $permissionService,
        private Security $security
    ) {}

    public function createComment(Tasks $task, string $content): TasksComments
    {
        if (!$this->permissionService->canAddComment($task)) {
            throw new \RuntimeException('Permission denied to add comment to this task');
        }

        $comment = new TasksComments();
        $comment->setTask($task);
        $comment->setContent($content);
        $comment->setUser($this->security->getUser());

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $comment;
    }

    public function getCommentsByTask(Tasks $task): array
    {
        if (!$this->permissionService->canEditTask($task) && 
            $task->getTaskAssignedTo() !== $this->security->getUser()) {
            throw new \RuntimeException('Permission denied to view task comments');
        }

        return $this->commentsRepository->findByTask($task);
    }

    public function getCommentsByUser(): array
    {
        $user = $this->security->getUser();
        return $this->commentsRepository->findByUser($user);
    }

    public function getRecentComments(int $limit = 10): array
    {
        if (!$this->permissionService->hasPermission('view_team_tasks')) {
            throw new \RuntimeException('Permission denied to view recent comments');
        }

        return $this->commentsRepository->findRecentComments($limit);
    }

    public function searchComments(string $term): array
    {
        if (!$this->permissionService->hasPermission('view_team_tasks')) {
            throw new \RuntimeException('Permission denied to search comments');
        }

        return $this->commentsRepository->searchComments($term);
    }
} 