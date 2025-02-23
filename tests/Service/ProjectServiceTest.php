<?php

namespace App\Tests\Service;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Service\ProjectService;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Security;

class ProjectServiceTest extends TestCase
{
    private ProjectService $projectService;
    private EntityManagerInterface $entityManager;
    private ProjectRepository $projectRepository;
    private PermissionService $permissionService;
    private Security $security;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->projectRepository = $this->createMock(ProjectRepository::class);
        $this->permissionService = $this->createMock(PermissionService::class);
        $this->security = $this->createMock(Security::class);

        $this->projectService = new ProjectService(
            $this->entityManager,
            $this->projectRepository,
            $this->permissionService,
            $this->security
        );
    }

    public function testCreateProject(): void
    {
        // Create a test user
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setRoles([User::ROLE_PROJECT_MANAGER]);

        // Set up security mock
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        // Set up permission check
        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('create_projects')
            ->willReturn(true);

        // Set up entity manager expectations
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Project::class));
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Create project data
        $projectData = [
            'name' => 'Test Project',
            'description' => 'Test Description',
            'startDate' => new \DateTime(),
            'endDate' => new \DateTime('+1 month')
        ];

        // Create the project
        $project = $this->projectService->createProject($projectData);

        // Assert project properties
        $this->assertEquals($projectData['name'], $project->getName());
        $this->assertEquals($projectData['description'], $project->getDescription());
        $this->assertEquals($user, $project->getCreatedBy());
    }

    public function testUpdateProject(): void
    {
        // Create existing project
        $project = new Project();
        $project->setName('Original Name');
        $project->setDescription('Original Description');

        // Set up repository mock
        $this->projectRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($project);

        // Set up permission check
        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('edit_project')
            ->willReturn(true);

        // Set up entity manager expectations
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Update data
        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description'
        ];

        // Update the project
        $updatedProject = $this->projectService->updateProject(1, $updateData);

        // Assert updates
        $this->assertEquals($updateData['name'], $updatedProject->getName());
        $this->assertEquals($updateData['description'], $updatedProject->getDescription());
    }

    public function testDeleteProject(): void
    {
        // Create project to delete
        $project = new Project();
        $project->setName('Project to Delete');

        // Set up repository mock
        $this->projectRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($project);

        // Set up permission check
        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('delete_project')
            ->willReturn(true);

        // Set up entity manager expectations
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($project);
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Delete the project
        $this->projectService->deleteProject(1);
    }

    public function testGetProjectsByUser(): void
    {
        // Create test user
        $user = new User();
        $user->setEmail('test@example.com');

        // Create test projects
        $projects = [
            new Project(),
            new Project()
        ];

        // Set up repository mock
        $this->projectRepository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn($projects);

        // Get projects
        $result = $this->projectService->getProjectsByUser($user);

        // Assert result
        $this->assertEquals($projects, $result);
        $this->assertCount(2, $result);
    }
} 