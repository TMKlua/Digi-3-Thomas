<?php

namespace App\Tests\Repository;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProjectRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ProjectRepository $projectRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->projectRepository = $this->entityManager->getRepository(Project::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
        $this->projectRepository = null;
    }

    private function createTestProject(User $user): Project
    {
        $project = new Project();
        $project->setProjectName('Test Project');
        $project->setProjectDescription('Test Project Description');
        $project->setProjectManager($user);
        $this->entityManager->persist($project);
        return $project;
    }

    private function createTestUser(): User
    {
        $user = new User();
        $user->setUserEmail(uniqid() . '@example.com');
        $user->setPassword('password123');
        $user->setUserRole('ROLE_USER');
        $this->entityManager->persist($user);
        return $user;
    }

    public function testFindByUser(): void
    {
        // Create test user
        $user = $this->createTestUser();

        // Create test projects
        $project1 = new Project();
        $project1->setProjectName('Test Project 1');
        $project1->setProjectDescription('Description 1');
        $project1->setProjectManager($user);
        $this->entityManager->persist($project1);

        $project2 = new Project();
        $project2->setProjectName('Test Project 2');
        $project2->setProjectDescription('Description 2');
        $project2->setProjectManager($user);
        $this->entityManager->persist($project2);

        $this->entityManager->flush();

        // Test findByUser method
        $projects = $this->projectRepository->findByManager($user);

        $this->assertCount(2, $projects);
        $this->assertEquals('Test Project 1', $projects[0]->getProjectName());
        $this->assertEquals('Test Project 2', $projects[1]->getProjectName());
    }

    public function testFindActive(): void
    {
        // Create test projects with different statuses
        $user = $this->createTestUser();

        $activeProject = new Project();
        $activeProject->setProjectName('Active Project');
        $activeProject->setProjectDescription('Active Description');
        $activeProject->setProjectStatus('active');
        $activeProject->setProjectManager($user);
        $this->entityManager->persist($activeProject);

        $completedProject = new Project();
        $completedProject->setProjectName('Completed Project');
        $completedProject->setProjectDescription('Completed Description');
        $completedProject->setProjectStatus('completed');
        $completedProject->setProjectManager($user);
        $this->entityManager->persist($completedProject);

        $this->entityManager->flush();

        // Test findActive method
        $activeProjects = $this->projectRepository->findByStatus('active');

        $this->assertCount(1, $activeProjects);
        $this->assertEquals('Active Project', $activeProjects[0]->getProjectName());
    }

    public function testFindByDateRange(): void
    {
        // Create test projects with different dates
        $user = $this->createTestUser();

        $oldProject = new Project();
        $oldProject->setProjectName('Old Project');
        $oldProject->setProjectDescription('Old Description');
        $oldProject->setProjectStartDate(new \DateTime('-2 months'));
        $oldProject->setProjectManager($user);
        $this->entityManager->persist($oldProject);

        $newProject = new Project();
        $newProject->setProjectName('New Project');
        $newProject->setProjectDescription('New Description');
        $newProject->setProjectStartDate(new \DateTime('-1 week'));
        $newProject->setProjectManager($user);
        $this->entityManager->persist($newProject);

        $this->entityManager->flush();

        // Test findByDateRange method
        $startDate = new \DateTime('-1 month');
        $endDate = new \DateTime();
        $recentProjects = $this->projectRepository->findByDateRange($startDate, $endDate);

        $this->assertCount(1, $recentProjects);
        $this->assertEquals('New Project', $recentProjects[0]->getProjectName());
    }

    public function testSearch(): void
    {
        // Create test projects with searchable content
        $user = $this->createTestUser();

        $project1 = new Project();
        $project1->setProjectName('Development Project');
        $project1->setProjectDescription('A project about software development');
        $project1->setProjectManager($user);
        $this->entityManager->persist($project1);

        $project2 = new Project();
        $project2->setProjectName('Marketing Campaign');
        $project2->setProjectDescription('A project about marketing');
        $project2->setProjectManager($user);
        $this->entityManager->persist($project2);

        $this->entityManager->flush();

        // Test search method
        $results = $this->projectRepository->search('development');

        $this->assertCount(1, $results);
        $this->assertEquals('Development Project', $results[0]->getProjectName());
    }
} 