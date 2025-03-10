<?php

namespace App\Tests\Security\Voter;

use App\Entity\Project;
use App\Entity\Tasks;
use App\Entity\User;
use App\Security\Voter\ProjectVoter;
use App\Service\PermissionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProjectVoterTest extends TestCase
{
    private ProjectVoter $voter;
    /** @var PermissionService&MockObject */
    private $permissionService;

    protected function setUp(): void
    {
        $this->permissionService = $this->createMock(PermissionService::class);
        $this->voter = new ProjectVoter($this->permissionService);
    }

    public function testVoterConstants(): void
    {
        // Test that the voter constants are defined correctly
        $this->assertEquals('create', ProjectVoter::CREATE);
        $this->assertEquals('view', ProjectVoter::VIEW);
        $this->assertEquals('edit', ProjectVoter::EDIT);
        $this->assertEquals('delete', ProjectVoter::DELETE);
        $this->assertEquals('manage_tasks', ProjectVoter::MANAGE_TASKS);
    }

    /**
     * @dataProvider provideSupportsTestCases
     */
    public function testSupports(string $attribute, mixed $subject, bool $expected): void
    {
        $method = new \ReflectionMethod(ProjectVoter::class, 'supports');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, $attribute, $subject);
        $this->assertSame($expected, $result);
    }

    public function provideSupportsTestCases(): array
    {
        return [
            'valid attribute and subject' => [ProjectVoter::VIEW, new Project(), true],
            'valid attribute for create' => [ProjectVoter::CREATE, null, true],
            'invalid attribute' => ['invalid_attribute', new Project(), false],
            'invalid subject' => [ProjectVoter::VIEW, new \stdClass(), false],
        ];
    }

    /**
     * @dataProvider provideVoteOnAttributeTestCases
     */
    public function testVoteOnAttribute(string $attribute, mixed $subject, bool $isUser, bool $permissionResult, bool $expected): void
    {
        $user = $isUser ? new User() : null;
        
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        if ($attribute === ProjectVoter::CREATE) {
            $this->permissionService->method('canCreateProject')->willReturn($permissionResult);
        } elseif ($attribute === ProjectVoter::EDIT) {
            $this->permissionService->method('canEditProject')->willReturn($permissionResult);
        } elseif ($attribute === ProjectVoter::DELETE) {
            $this->permissionService->method('canDeleteProject')->willReturn($permissionResult);
            if ($subject instanceof Project) {
                // Simulate a project with no tasks for delete permission
                $project = $this->createMock(Project::class);
                $project->method('getTasks')->willReturn([]);
                $subject = $project;
            }
        } elseif ($attribute === ProjectVoter::VIEW) {
            $this->permissionService->method('hasPermission')
                ->with('view_all_projects')
                ->willReturn($permissionResult);
        } elseif ($attribute === ProjectVoter::MANAGE_TASKS) {
            $this->permissionService->method('hasPermission')
                ->willReturnMap([
                    ['manage_all_tasks', $permissionResult],
                    ['manage_team_tasks', false]
                ]);
        }

        $method = new \ReflectionMethod(ProjectVoter::class, 'voteOnAttribute');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, $attribute, $subject, $token);
        $this->assertSame($expected, $result);
    }

    public function provideVoteOnAttributeTestCases(): array
    {
        $project = $this->createMock(Project::class);
        
        return [
            'no user' => [ProjectVoter::VIEW, $project, false, false, false],
            'create permission granted' => [ProjectVoter::CREATE, null, true, true, true],
            'create permission denied' => [ProjectVoter::CREATE, null, true, false, false],
            'view permission granted' => [ProjectVoter::VIEW, $project, true, true, true],
            'view permission denied' => [ProjectVoter::VIEW, $project, true, false, false],
            'edit permission granted' => [ProjectVoter::EDIT, $project, true, true, true],
            'edit permission denied' => [ProjectVoter::EDIT, $project, true, false, false],
            'delete permission granted' => [ProjectVoter::DELETE, $project, true, true, true],
            'delete permission denied' => [ProjectVoter::DELETE, $project, true, false, false],
            'manage tasks permission granted' => [ProjectVoter::MANAGE_TASKS, $project, true, true, true],
            'manage tasks permission denied' => [ProjectVoter::MANAGE_TASKS, $project, true, false, false],
        ];
    }

    public function testCanViewAsProjectManager(): void
    {
        $user = new User();
        $project = new Project();
        $project->setProjectManager($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->permissionService->method('hasPermission')->willReturn(false);

        $method = new \ReflectionMethod(ProjectVoter::class, 'voteOnAttribute');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, ProjectVoter::VIEW, $project, $token);
        $this->assertTrue($result, 'Project manager should be able to view the project');
    }

    public function testCanViewAsTaskAssignee(): void
    {
        $user = new User();
        $project = new Project();
        $task = new Tasks();
        $task->setTaskAssignedTo($user);
        $project->addTask($task);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->permissionService->method('hasPermission')->willReturn(false);

        $method = new \ReflectionMethod(ProjectVoter::class, 'voteOnAttribute');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, ProjectVoter::VIEW, $project, $token);
        $this->assertTrue($result, 'Task assignee should be able to view the project');
    }
} 