<?php

namespace App\Tests\Service;

use App\Entity\Task;
use App\Entity\User;
use App\Entity\Project;
use App\Repository\TaskRepository;
use App\Service\TaskService;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Security;

class TaskServiceTest extends TestCase
{
    private TaskService $taskService;
    private EntityManagerInterface $entityManager;
    private TaskRepository $taskRepository;
    private PermissionService $permissionService;
    private Security $security;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->taskRepository = $this->createMock(TaskRepository::class);
        $this->permissionService = $this->createMock(PermissionService::class);
        $this->security = $this->createMock(Security::class);

        $this->taskService = new TaskService(
            $this->entityManager,
            $this->taskRepository,
            $this->permissionService,
            $this->security
        );
    }

    public function testCreateTask(): void
    {
        // Create test user and project
        $user = $this->createMock(User::class);
        $project = $this->createMock(Project::class);

        // Set up security mock
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        // Set up permission check
        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('create_task')
            ->willReturn(true);

        // Set up entity manager expectations
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Task::class));
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Create task data
        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'dueDate' => new \DateTime('+1 week'),
            'priority' => 'high',
            'project' => $project
        ];

        // Create the task
        $task = $this->taskService->createTask($taskData);

        // Assert task properties
        $this->assertEquals($taskData['title'], $task->getTitle());
        $this->assertEquals($taskData['description'], $task->getDescription());
        $this->assertEquals($taskData['priority'], $task->getPriority());
        $this->assertEquals($user, $task->getCreatedBy());
        $this->assertEquals($project, $task->getProject());
    }

    public function testUpdateTaskStatus(): void
    {
        // Create existing task
        $task = $this->createMock(Task::class);
        $user = $this->createMock(User::class);

        // Set up repository mock
        $this->taskRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($task);

        // Set up security mock
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        // Set up permission check
        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('edit_task')
            ->willReturn(true);

        // Set up task expectations
        $task->expects($this->once())
            ->method('setStatus')
            ->with('in_progress');

        // Set up entity manager expectations
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Update the task status
        $this->taskService->updateTaskStatus(1, 'in_progress');
    }

    public function testAssignTask(): void
    {
        // Create test task and user
        $task = $this->createMock(Task::class);
        $assignee = $this->createMock(User::class);
        $currentUser = $this->createMock(User::class);

        // Set up repository mock
        $this->taskRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($task);

        // Set up security mock
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($currentUser);

        // Set up permission check
        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('assign_task')
            ->willReturn(true);

        // Set up task expectations
        $task->expects($this->once())
            ->method('setAssignedTo')
            ->with($assignee);

        // Set up entity manager expectations
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Assign the task
        $this->taskService->assignTask(1, $assignee);
    }

    public function testGetTasksByProject(): void
    {
        // Create test project
        $project = $this->createMock(Project::class);
        
        // Create test tasks
        $tasks = [
            $this->createMock(Task::class),
            $this->createMock(Task::class)
        ];

        // Set up repository mock
        $this->taskRepository->expects($this->once())
            ->method('findByProject')
            ->with($project)
            ->willReturn($tasks);

        // Get tasks
        $result = $this->taskService->getTasksByProject($project);

        // Assert result
        $this->assertEquals($tasks, $result);
        $this->assertCount(2, $result);
    }

    public function testGetTasksByAssignee(): void
    {
        // Create test user
        $user = $this->createMock(User::class);
        
        // Create test tasks
        $tasks = [
            $this->createMock(Task::class),
            $this->createMock(Task::class)
        ];

        // Set up repository mock
        $this->taskRepository->expects($this->once())
            ->method('findByAssignee')
            ->with($user)
            ->willReturn($tasks);

        // Get tasks
        $result = $this->taskService->getTasksByAssignee($user);

        // Assert result
        $this->assertEquals($tasks, $result);
        $this->assertCount(2, $result);
    }
} 