<?php

namespace App\Tests\Security\Voter;

use App\Entity\Project;
use App\Entity\Tasks;
use App\Entity\User;
use App\Security\Voter\TaskVoter;
use App\Service\PermissionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TaskVoterTest extends TestCase
{
    private TaskVoter $voter;
    /** @var PermissionService&MockObject */
    private $permissionService;

    protected function setUp(): void
    {
        $this->permissionService = $this->createMock(PermissionService::class);
        $this->voter = new TaskVoter($this->permissionService);
    }

    public function testVoterConstants(): void
    {
        // Test that the voter constants are defined correctly
        $this->assertEquals('view', TaskVoter::VIEW);
        $this->assertEquals('edit', TaskVoter::EDIT);
        $this->assertEquals('delete', TaskVoter::DELETE);
        $this->assertEquals('create', TaskVoter::CREATE);
        $this->assertEquals('change_status', TaskVoter::CHANGE_STATUS);
        $this->assertEquals('assign', TaskVoter::ASSIGN);
    }

    /**
     * @dataProvider provideSupportsTestCases
     */
    public function testSupports(string $attribute, mixed $subject, bool $expected): void
    {
        $method = new \ReflectionMethod(TaskVoter::class, 'supports');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, $attribute, $subject);
        $this->assertSame($expected, $result);
    }

    public function provideSupportsTestCases(): array
    {
        return [
            'valid attribute and subject' => [TaskVoter::VIEW, new Tasks(), true],
            'valid attribute for create with null' => [TaskVoter::CREATE, null, true],
            'valid attribute for create with project' => [TaskVoter::CREATE, new Project(), true],
            'invalid attribute' => ['invalid_attribute', new Tasks(), false],
            'invalid subject' => [TaskVoter::VIEW, new \stdClass(), false],
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

        if ($attribute === TaskVoter::CREATE) {
            if ($subject instanceof Project) {
                $this->permissionService->method('canCreateTask')
                    ->with($subject)
                    ->willReturn($permissionResult);
            }
        } elseif ($attribute === TaskVoter::VIEW) {
            $this->permissionService->method('hasPermission')
                ->with('view_all_tasks')
                ->willReturn($permissionResult);
        } elseif ($attribute === TaskVoter::EDIT) {
            $this->permissionService->method('hasPermission')
                ->willReturnMap([
                    ['edit_all_tasks', $permissionResult],
                    ['manage_team_tasks', false]
                ]);
        } elseif ($attribute === TaskVoter::DELETE) {
            $this->permissionService->method('hasPermission')
                ->with('delete_tasks')
                ->willReturn($permissionResult);
        } elseif ($attribute === TaskVoter::CHANGE_STATUS) {
            $this->permissionService->method('hasPermission')
                ->with('change_task_status')
                ->willReturn($permissionResult);
        } elseif ($attribute === TaskVoter::ASSIGN) {
            $this->permissionService->method('hasPermission')
                ->willReturnMap([
                    ['assign_tasks', $permissionResult],
                    ['manage_team_tasks', false]
                ]);
        }

        $method = new \ReflectionMethod(TaskVoter::class, 'voteOnAttribute');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, $attribute, $subject, $token);
        $this->assertSame($expected, $result);
    }

    public function provideVoteOnAttributeTestCases(): array
    {
        $task = $this->createMock(Tasks::class);
        $project = $this->createMock(Project::class);
        
        return [
            'no user' => [TaskVoter::VIEW, $task, false, false, false],
            'create permission with project granted' => [TaskVoter::CREATE, $project, true, true, true],
            'create permission with project denied' => [TaskVoter::CREATE, $project, true, false, false],
            'create permission with null' => [TaskVoter::CREATE, null, true, true, false],
            'view permission granted' => [TaskVoter::VIEW, $task, true, true, true],
            'view permission denied' => [TaskVoter::VIEW, $task, true, false, false],
            'edit permission granted' => [TaskVoter::EDIT, $task, true, true, true],
            'edit permission denied' => [TaskVoter::EDIT, $task, true, false, false],
            'delete permission granted' => [TaskVoter::DELETE, $task, true, true, true],
            'delete permission denied' => [TaskVoter::DELETE, $task, true, false, false],
            'change status permission granted' => [TaskVoter::CHANGE_STATUS, $task, true, true, true],
            'change status permission denied' => [TaskVoter::CHANGE_STATUS, $task, true, false, false],
            'assign permission granted' => [TaskVoter::ASSIGN, $task, true, true, true],
            'assign permission denied' => [TaskVoter::ASSIGN, $task, true, false, false],
        ];
    }

    public function testCanViewAsProjectManager(): void
    {
        $user = new User();
        $project = $this->createMock(Project::class);
        $project->method('getProjectManager')->willReturn($user);
        
        $task = $this->createMock(Tasks::class);
        $task->method('getTaskProject')->willReturn($project);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->permissionService->method('hasPermission')->willReturn(false);

        $method = new \ReflectionMethod(TaskVoter::class, 'voteOnAttribute');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, TaskVoter::VIEW, $task, $token);
        $this->assertTrue($result, 'Project manager should be able to view the task');
    }

    public function testCanViewAsTaskAssignee(): void
    {
        $user = new User();
        $project = $this->createMock(Project::class);
        $project->method('getProjectManager')->willReturn(new User());
        
        $task = $this->createMock(Tasks::class);
        $task->method('getTaskProject')->willReturn($project);
        $task->method('getTaskAssignedTo')->willReturn($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->permissionService->method('hasPermission')->willReturn(false);

        $method = new \ReflectionMethod(TaskVoter::class, 'voteOnAttribute');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, TaskVoter::VIEW, $task, $token);
        $this->assertTrue($result, 'Task assignee should be able to view the task');
    }
} 