<?php

namespace App\Tests\Entity;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\Customers;
use App\Entity\Tasks;
use App\Enum\ProjectStatus;
use PHPUnit\Framework\TestCase;

class ProjectTest extends TestCase
{
    private Project $project;

    protected function setUp(): void
    {
        $this->project = new Project();
    }

    public function testGettersAndSetters(): void
    {
        // Test name
        $name = 'Test Project';
        $this->project->setProjectName($name);
        $this->assertEquals($name, $this->project->getProjectName());

        // Test description
        $description = 'This is a test project';
        $this->project->setProjectDescription($description);
        $this->assertEquals($description, $this->project->getProjectDescription());

        // Test status
        $status = ProjectStatus::IN_PROGRESS;
        $this->project->setProjectStatus($status);
        $this->assertEquals($status, $this->project->getProjectStatus());

        // Test manager
        $manager = new User();
        $this->project->setProjectManager($manager);
        $this->assertSame($manager, $this->project->getProjectManager());

        // Test customer
        $customer = new Customers();
        $this->project->setProjectCustomer($customer);
        $this->assertSame($customer, $this->project->getProjectCustomer());

        // Test start date
        $date = new \DateTime();
        $this->project->setProjectStartDate($date);
        $this->assertEquals($date, $this->project->getProjectStartDate());

        // Test target date
        $date = new \DateTime('+1 month');
        $this->project->setProjectTargetDate($date);
        $this->assertEquals($date, $this->project->getProjectTargetDate());

        // Test end date
        $date = new \DateTime('+2 months');
        $this->project->setProjectEndDate($date);
        $this->assertEquals($date, $this->project->getProjectEndDate());

        // Test created at
        $this->assertInstanceOf(\DateTimeInterface::class, $this->project->getProjectCreatedAt());

        // Test updated at
        $date = new \DateTime();
        $this->project->setProjectUpdatedAt($date);
        $this->assertEquals($date, $this->project->getProjectUpdatedAt());
    }

    public function testDefaultValues(): void
    {
        // Test default status
        $this->assertEquals(ProjectStatus::NEW, $this->project->getProjectStatus());

        // Test default created at
        $this->assertInstanceOf(\DateTimeInterface::class, $this->project->getProjectCreatedAt());

        // Test default tasks collection
        $this->assertCount(0, $this->project->getTasks());
    }

    public function testTasksCollection(): void
    {
        // Test adding a task
        $task = new Tasks();
        $this->project->addTask($task);
        $this->assertCount(1, $this->project->getTasks());
        $this->assertSame($this->project, $task->getTaskProject());

        // Test removing a task
        $this->project->removeTask($task);
        $this->assertCount(0, $this->project->getTasks());
    }

    public function testToString(): void
    {
        $name = 'Test Project';
        $this->project->setProjectName($name);
        $this->assertEquals($name, (string) $this->project);
    }
} 