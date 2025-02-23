<?php

namespace App\Tests\Repository;

use App\Entity\Tasks;
use App\Entity\User;
use App\Entity\Project;
use App\Repository\TasksRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TaskRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private TasksRepository $taskRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->taskRepository = $this->entityManager->getRepository(Tasks::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
        $this->taskRepository = null;
    }

    private function createTestProject(User $user): Project
    {
        $project = new Project();
        $project->setProjectName('Test Project');
        $project->setProjectDescription('Test Project Description');
        $project->setProjectManager($user);
        $project->setProjectStartDate(new \DateTime());
        $project->setProjectTargetDate(new \DateTime('+1 month'));
        $this->entityManager->persist($project);
        return $project;
    }

    private function createTestUser(): User
    {
        $user = new User();
        $user->setUserEmail(uniqid() . '@example.com');
        $user->setPassword('password123');
        $user->setUserRole('ROLE_USER');
        $user->setUserFirstName('Test');
        $user->setUserLastName('User');
        $this->entityManager->persist($user);
        return $user;
    }

    public function testFindByAssignee(): void
    {
        // Create test user and project
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        $this->entityManager->flush();

        // Create test tasks
        $task1 = new Tasks();
        $task1->setTaskName('Task 1');
        $task1->setTaskDescription('Description 1');
        $task1->setTaskProject($project);
        $task1->setTaskAssignedTo($user);
        $task1->setTaskStatus('pending');
        $this->entityManager->persist($task1);

        $task2 = new Tasks();
        $task2->setTaskName('Task 2');
        $task2->setTaskDescription('Description 2');
        $task2->setTaskProject($project);
        $task2->setTaskAssignedTo($user);
        $task2->setTaskStatus('in_progress');
        $this->entityManager->persist($task2);

        $this->entityManager->flush();

        // Test findByAssignee method
        $assignedTasks = $this->taskRepository->findByAssignedUser($user);

        $this->assertCount(2, $assignedTasks);
        $this->assertEquals('Task 1', $assignedTasks[0]->getTaskName());
        $this->assertEquals('Task 2', $assignedTasks[1]->getTaskName());
    }

    public function testFindByProject(): void
    {
        // Create test users and project
        $user1 = $this->createTestUser();
        $user2 = $this->createTestUser();
        $project = $this->createTestProject($user1);
        $this->entityManager->flush();

        // Create test tasks
        $task1 = new Tasks();
        $task1->setTaskName('Project Task 1');
        $task1->setTaskDescription('Project Description 1');
        $task1->setTaskProject($project);
        $task1->setTaskAssignedTo($user1);
        $this->entityManager->persist($task1);

        $task2 = new Tasks();
        $task2->setTaskName('Project Task 2');
        $task2->setTaskDescription('Project Description 2');
        $task2->setTaskProject($project);
        $task2->setTaskAssignedTo($user2);
        $this->entityManager->persist($task2);

        $this->entityManager->flush();

        // Test findByProject method
        $tasks = $this->taskRepository->findByProject($project);

        $this->assertCount(2, $tasks);
        $this->assertEquals('Project Task 1', $tasks[0]->getTaskName());
        $this->assertEquals('Project Task 2', $tasks[1]->getTaskName());
    }

    public function testFindByStatus(): void
    {
        // Create test user and project
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        $this->entityManager->flush();

        // Create test tasks with different statuses
        $pendingTask = new Tasks();
        $pendingTask->setTaskName('Pending Task');
        $pendingTask->setTaskDescription('Pending Description');
        $pendingTask->setTaskProject($project);
        $pendingTask->setTaskAssignedTo($user);
        $pendingTask->setTaskStatus('pending');
        $this->entityManager->persist($pendingTask);

        $completedTask = new Tasks();
        $completedTask->setTaskName('Completed Task');
        $completedTask->setTaskDescription('Completed Description');
        $completedTask->setTaskProject($project);
        $completedTask->setTaskAssignedTo($user);
        $completedTask->setTaskStatus('completed');
        $this->entityManager->persist($completedTask);

        $this->entityManager->flush();

        // Test findByStatus method
        $pendingTasks = $this->taskRepository->findByStatus('pending');
        $completedTasks = $this->taskRepository->findByStatus('completed');

        $this->assertCount(1, $pendingTasks);
        $this->assertCount(1, $completedTasks);
        $this->assertEquals('Pending Task', $pendingTasks[0]->getTaskName());
        $this->assertEquals('Completed Task', $completedTasks[0]->getTaskName());
    }

    public function testFindByPriority(): void
    {
        // Create test user and project
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        $this->entityManager->flush();

        // Create test tasks with different priorities
        $highPriorityTask = new Tasks();
        $highPriorityTask->setTaskName('High Priority Task');
        $highPriorityTask->setTaskDescription('High Priority Description');
        $highPriorityTask->setTaskProject($project);
        $highPriorityTask->setTaskAssignedTo($user);
        $highPriorityTask->setTaskPriority('high');
        $this->entityManager->persist($highPriorityTask);

        $lowPriorityTask = new Tasks();
        $lowPriorityTask->setTaskName('Low Priority Task');
        $lowPriorityTask->setTaskDescription('Low Priority Description');
        $lowPriorityTask->setTaskProject($project);
        $lowPriorityTask->setTaskAssignedTo($user);
        $lowPriorityTask->setTaskPriority('low');
        $this->entityManager->persist($lowPriorityTask);

        $this->entityManager->flush();

        // Test findByPriority method
        $highPriorityTasks = $this->taskRepository->findByPriority('high');
        $lowPriorityTasks = $this->taskRepository->findByPriority('low');

        $this->assertCount(1, $highPriorityTasks);
        $this->assertCount(1, $lowPriorityTasks);
        $this->assertEquals('High Priority Task', $highPriorityTasks[0]->getTaskName());
        $this->assertEquals('Low Priority Task', $lowPriorityTasks[0]->getTaskName());
    }
} 