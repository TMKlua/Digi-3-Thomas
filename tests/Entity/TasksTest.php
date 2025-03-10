<?php

namespace App\Tests\Entity;

use App\Entity\Tasks;
use App\Entity\User;
use App\Entity\Project;
use App\Entity\TasksComments;
use App\Entity\TasksAttachments;
use App\Enum\TaskStatus;
use App\Enum\TaskPriority;
use App\Enum\TaskComplexity;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class TasksTest extends TestCase
{
    private Tasks $task;

    protected function setUp(): void
    {
        $this->task = new Tasks();
    }

    public function testGettersAndSetters(): void
    {
        // Test name
        $name = 'Test Task';
        $this->task->setTaskName($name);
        $this->assertEquals($name, $this->task->getTaskName());

        // Test description
        $description = 'This is a test task';
        $this->task->setTaskDescription($description);
        $this->assertEquals($description, $this->task->getTaskDescription());

        // Test status
        $status = TaskStatus::IN_PROGRESS;
        $this->task->setTaskStatus($status);
        $this->assertEquals($status, $this->task->getTaskStatus());

        // Test priority
        $priority = TaskPriority::HIGH;
        $this->task->setTaskPriority($priority);
        $this->assertEquals($priority, $this->task->getTaskPriority());

        // Test complexity
        $complexity = TaskComplexity::MODERATE;
        $this->task->setTaskComplexity($complexity);
        $this->assertEquals($complexity, $this->task->getTaskComplexity());

        // Test project
        $project = new Project();
        $this->task->setTaskProject($project);
        $this->assertSame($project, $this->task->getTaskProject());

        // Test assigned to
        $user = new User();
        $this->task->setTaskAssignedTo($user);
        $this->assertSame($user, $this->task->getTaskAssignedTo());

        // Test start date
        $date = new \DateTime();
        $this->task->setTaskStartDate($date);
        $this->assertEquals($date, $this->task->getTaskStartDate());

        // Test end date
        $date = new \DateTime('+1 week');
        $this->task->setTaskEndDate($date);
        $this->assertEquals($date, $this->task->getTaskEndDate());

        // Test target date
        $date = new \DateTime('+2 weeks');
        $this->task->setTaskTargetDate($date);
        $this->assertEquals($date, $this->task->getTaskTargetDate());

        // Test created at
        $this->assertInstanceOf(\DateTimeInterface::class, $this->task->getTaskCreatedAt());

        // Test updated at
        $date = new \DateTime();
        $this->task->setTaskUpdatedAt($date);
        $this->assertEquals($date, $this->task->getTaskUpdatedAt());

        // Test updated by
        $user = new User();
        $this->task->setTaskUpdatedBy($user);
        $this->assertSame($user, $this->task->getTaskUpdatedBy());
    }

    public function testDefaultValues(): void
    {
        // Test default status
        $this->assertEquals(TaskStatus::NEW, $this->task->getTaskStatus());

        // Test default priority
        $this->assertEquals(TaskPriority::MEDIUM, $this->task->getTaskPriority());

        // Test default created at
        $this->assertInstanceOf(\DateTimeInterface::class, $this->task->getTaskCreatedAt());

        // Test default collections
        $this->assertCount(0, $this->task->getComments());
        $this->assertCount(0, $this->task->getAttachments());
    }

    public function testCollections(): void
    {
        // Test comments collection
        $this->assertInstanceOf(Collection::class, $this->task->getComments());
        $this->assertCount(0, $this->task->getComments());

        // Test attachments collection
        $this->assertInstanceOf(Collection::class, $this->task->getAttachments());
        $this->assertCount(0, $this->task->getAttachments());
    }
} 