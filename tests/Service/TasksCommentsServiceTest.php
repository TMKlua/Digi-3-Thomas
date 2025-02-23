<?php

namespace App\Tests\Service;

use App\Entity\Tasks;
use App\Entity\TasksComments;
use App\Entity\User;
use App\Repository\TasksCommentsRepository;
use App\Service\TasksCommentsService;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class TasksCommentsServiceTest extends TestCase
{
    private TasksCommentsService $commentsService;
    private EntityManagerInterface|MockObject $entityManager;
    private TasksCommentsRepository|MockObject $commentsRepository;
    private PermissionService|MockObject $permissionService;
    private Security|MockObject $security;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->commentsRepository = $this->createMock(TasksCommentsRepository::class);
        $this->permissionService = $this->createMock(PermissionService::class);
        $this->security = $this->createMock(Security::class);

        $this->commentsService = new TasksCommentsService(
            $this->entityManager,
            $this->commentsRepository,
            $this->permissionService,
            $this->security
        );
    }

    public function testCreateComment(): void
    {
        // Arrange
        $task = new Tasks();
        $user = new User();
        $content = 'Test comment content';

        $this->permissionService->expects($this->once())
            ->method('canAddComment')
            ->with($task)
            ->willReturn(true);

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(TasksComments::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $comment = $this->commentsService->createComment($task, $content);

        // Assert
        $this->assertInstanceOf(TasksComments::class, $comment);
        $this->assertEquals($task, $comment->getTask());
        $this->assertEquals($content, $comment->getContent());
        $this->assertEquals($user, $comment->getUser());
    }

    public function testGetCommentsByTask(): void
    {
        // Arrange
        $task = new Tasks();
        $user = new User();
        $task->setTaskAssignedTo($user);

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->permissionService->expects($this->once())
            ->method('canEditTask')
            ->with($task)
            ->willReturn(false);

        $comments = [new TasksComments(), new TasksComments()];
        $this->commentsRepository->expects($this->once())
            ->method('findByTask')
            ->with($task)
            ->willReturn($comments);

        // Act
        $result = $this->commentsService->getCommentsByTask($task);

        // Assert
        $this->assertEquals($comments, $result);
    }

    public function testGetRecentComments(): void
    {
        // Arrange
        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('view_team_tasks')
            ->willReturn(true);

        $comments = [new TasksComments(), new TasksComments()];
        $this->commentsRepository->expects($this->once())
            ->method('findRecentComments')
            ->with(10)
            ->willReturn($comments);

        // Act
        $result = $this->commentsService->getRecentComments();

        // Assert
        $this->assertEquals($comments, $result);
        $this->assertCount(2, $result);
    }

    public function testSearchComments(): void
    {
        // Arrange
        $searchTerm = 'test';

        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('view_team_tasks')
            ->willReturn(true);

        $comments = [new TasksComments()];
        $this->commentsRepository->expects($this->once())
            ->method('searchComments')
            ->with($searchTerm)
            ->willReturn($comments);

        // Act
        $result = $this->commentsService->searchComments($searchTerm);

        // Assert
        $this->assertEquals($comments, $result);
        $this->assertCount(1, $result);
    }
} 