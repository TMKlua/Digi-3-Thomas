<?php

namespace App\Tests\Service;

use App\Entity\Tasks;
use App\Entity\TasksAttachments;
use App\Entity\User;
use App\Repository\TasksAttachmentsRepository;
use App\Service\TasksAttachmentsService;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class TasksAttachmentsServiceTest extends TestCase
{
    private TasksAttachmentsService $attachmentsService;
    private EntityManagerInterface|MockObject $entityManager;
    private TasksAttachmentsRepository|MockObject $attachmentsRepository;
    private PermissionService|MockObject $permissionService;
    private Security|MockObject $security;
    private SluggerInterface|MockObject $slugger;
    private string $attachmentsDirectory;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->attachmentsRepository = $this->createMock(TasksAttachmentsRepository::class);
        $this->permissionService = $this->createMock(PermissionService::class);
        $this->security = $this->createMock(Security::class);
        $this->slugger = $this->createMock(SluggerInterface::class);
        $this->attachmentsDirectory = sys_get_temp_dir();

        $this->attachmentsService = new TasksAttachmentsService(
            $this->entityManager,
            $this->attachmentsRepository,
            $this->permissionService,
            $this->security,
            $this->slugger,
            $this->attachmentsDirectory
        );
    }

    public function testUploadAttachment(): void
    {
        // Arrange
        $task = new Tasks();
        $user = new User();
        $file = $this->createMock(UploadedFile::class);
        $description = 'Test attachment';

        $file->expects($this->once())
            ->method('getClientOriginalName')
            ->willReturn('test.txt');

        $file->expects($this->once())
            ->method('guessExtension')
            ->willReturn('txt');

        $file->expects($this->once())
            ->method('getSize')
            ->willReturn(1024);

        $file->expects($this->once())
            ->method('getMimeType')
            ->willReturn('text/plain');

        $this->permissionService->expects($this->once())
            ->method('canEditTask')
            ->with($task)
            ->willReturn(true);

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->slugger->expects($this->once())
            ->method('slug')
            ->willReturn('test');

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(TasksAttachments::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $attachment = $this->attachmentsService->uploadAttachment($task, $file, $description);

        // Assert
        $this->assertInstanceOf(TasksAttachments::class, $attachment);
        $this->assertEquals($task, $attachment->getTask());
        $this->assertEquals($description, $attachment->getDescription());
        $this->assertEquals($user, $attachment->getUploadedBy());
    }

    public function testGetAttachmentsByTask(): void
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

        $attachments = [new TasksAttachments(), new TasksAttachments()];
        $this->attachmentsRepository->expects($this->once())
            ->method('findByTask')
            ->with($task)
            ->willReturn($attachments);

        // Act
        $result = $this->attachmentsService->getAttachmentsByTask($task);

        // Assert
        $this->assertEquals($attachments, $result);
    }

    public function testDeleteAttachment(): void
    {
        // Arrange
        $task = new Tasks();
        $attachment = new TasksAttachments();
        $attachment->setTask($task);
        $attachment->setFileName('test.txt');

        $this->attachmentsRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($attachment);

        $this->permissionService->expects($this->once())
            ->method('canEditTask')
            ->with($task)
            ->willReturn(true);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($attachment);

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $this->attachmentsService->deleteAttachment(1);
    }

    public function testGetRecentAttachments(): void
    {
        // Arrange
        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('view_team_tasks')
            ->willReturn(true);

        $attachments = [new TasksAttachments(), new TasksAttachments()];
        $this->attachmentsRepository->expects($this->once())
            ->method('findRecentAttachments')
            ->with(10)
            ->willReturn($attachments);

        // Act
        $result = $this->attachmentsService->getRecentAttachments();

        // Assert
        $this->assertEquals($attachments, $result);
        $this->assertCount(2, $result);
    }

    public function testCanAccessAttachment(): void
    {
        // Arrange
        $user = new User();
        $attachment = new TasksAttachments();
        $attachment->setUploadedBy($user);

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        // Act
        $result = $this->attachmentsService->canAccessAttachment($attachment);

        // Assert
        $this->assertTrue($result);
    }

    public function testCanAccessAttachmentWithTeamPermission(): void
    {
        // Arrange
        $user = new User();
        $otherUser = new User();
        $attachment = new TasksAttachments();
        $attachment->setUploadedBy($otherUser);

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('view_team_tasks')
            ->willReturn(true);

        // Act
        $result = $this->attachmentsService->canAccessAttachment($attachment);

        // Assert
        $this->assertTrue($result);
    }

    public function testGetAttachmentStats(): void
    {
        // Arrange
        $attachment1 = new TasksAttachments();
        $attachment1->setFileSize(1000);
        $attachment1->setMimeType('image/jpeg');

        $attachment2 = new TasksAttachments();
        $attachment2->setFileSize(2000);
        $attachment2->setMimeType('application/pdf');

        $attachment3 = new TasksAttachments();
        $attachment3->setFileSize(1500);
        $attachment3->setMimeType('image/jpeg');

        $this->attachmentsRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$attachment1, $attachment2, $attachment3]);

        // Act
        $stats = $this->attachmentsService->getAttachmentStats();

        // Assert
        $this->assertEquals(3, $stats['total_count']);
        $this->assertEquals(4500, $stats['total_size']);
        $this->assertEquals([
            'image/jpeg' => 2,
            'application/pdf' => 1
        ], $stats['by_type']);
    }

    public function testCleanupOrphanedAttachments(): void
    {
        // Arrange
        $orphanedAttachment1 = new TasksAttachments();
        $orphanedAttachment1->setFileName('orphaned1.txt');
        $orphanedAttachment2 = new TasksAttachments();
        $orphanedAttachment2->setFileName('orphaned2.txt');

        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('manage_tasks')
            ->willReturn(true);

        $this->attachmentsRepository->expects($this->once())
            ->method('findOrphanedAttachments')
            ->willReturn([$orphanedAttachment1, $orphanedAttachment2]);

        $this->entityManager->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive(
                [$orphanedAttachment1],
                [$orphanedAttachment2]
            );

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $count = $this->attachmentsService->cleanupOrphanedAttachments();

        // Assert
        $this->assertEquals(2, $count);
    }
} 