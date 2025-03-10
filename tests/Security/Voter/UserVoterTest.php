<?php

namespace App\Tests\Security\Voter;

use App\Entity\Project;
use App\Entity\Tasks;
use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Repository\TasksRepository;
use App\Security\Voter\UserVoter;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserVoterTest extends TestCase
{
    private UserVoter $voter;
    /** @var PermissionService&MockObject */
    private $permissionService;
    /** @var EntityManagerInterface&MockObject */
    private $entityManager;
    /** @var TasksRepository&MockObject */
    private $tasksRepository;
    /** @var ProjectRepository&MockObject */
    private $projectRepository;

    protected function setUp(): void
    {
        $this->permissionService = $this->createMock(PermissionService::class);
        $this->tasksRepository = $this->createMock(TasksRepository::class);
        $this->projectRepository = $this->createMock(ProjectRepository::class);
        
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->method('getRepository')
            ->willReturnMap([
                ['App\Entity\Tasks', $this->tasksRepository],
                ['App\Entity\Project', $this->projectRepository]
            ]);
        
        $this->voter = new UserVoter($this->permissionService, $this->entityManager);
    }

    public function testVoterConstants(): void
    {
        // Test that the voter constants are defined correctly
        $this->assertEquals('view', UserVoter::VIEW);
        $this->assertEquals('edit', UserVoter::EDIT);
        $this->assertEquals('delete', UserVoter::DELETE);
        $this->assertEquals('create', UserVoter::CREATE);
        $this->assertEquals('change_role', UserVoter::CHANGE_ROLE);
    }

    /**
     * @dataProvider provideSupportsTestCases
     */
    public function testSupports(string $attribute, mixed $subject, bool $expected): void
    {
        $method = new \ReflectionMethod(UserVoter::class, 'supports');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, $attribute, $subject);
        $this->assertSame($expected, $result);
    }

    public function provideSupportsTestCases(): array
    {
        return [
            'valid attribute and subject' => [UserVoter::VIEW, new User(), true],
            'valid attribute for create' => [UserVoter::CREATE, null, true],
            'invalid attribute' => ['invalid_attribute', new User(), false],
            'invalid subject' => [UserVoter::VIEW, new \stdClass(), false],
        ];
    }

    /**
     * @dataProvider provideVoteOnAttributeTestCases
     */
    public function testVoteOnAttribute(string $attribute, mixed $subject, bool $isUser, bool $isSameUser, bool $permissionResult, bool $expected): void
    {
        $currentUser = $isUser ? new User() : null;
        $targetUser = new User();
        
        if ($isSameUser && $isUser) {
            $targetUser = $currentUser;
        }
        
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($currentUser);

        if ($attribute === UserVoter::CREATE) {
            $this->permissionService->method('canManageUsers')
                ->willReturn($permissionResult);
        } elseif ($attribute === UserVoter::VIEW) {
            $this->permissionService->method('canViewUserList')
                ->willReturn($permissionResult);
            $this->permissionService->method('hasPermission')
                ->with('view_team_members')
                ->willReturn(false);
        } elseif ($attribute === UserVoter::EDIT) {
            $this->permissionService->method('canEditUser')
                ->willReturn($permissionResult);
        } elseif ($attribute === UserVoter::DELETE) {
            $this->permissionService->method('canManageUsers')
                ->willReturn($permissionResult);
            $this->tasksRepository->method('findBy')
                ->willReturn([]);
            $this->projectRepository->method('findByManager')
                ->willReturn([]);
        } elseif ($attribute === UserVoter::CHANGE_ROLE) {
            $this->permissionService->method('canManageRoles')
                ->willReturn($permissionResult);
        }

        $method = new \ReflectionMethod(UserVoter::class, 'voteOnAttribute');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, $attribute, $targetUser, $token);
        $this->assertSame($expected, $result);
    }

    public function provideVoteOnAttributeTestCases(): array
    {
        return [
            'no user' => [UserVoter::VIEW, new User(), false, false, false, false],
            'create permission granted' => [UserVoter::CREATE, null, true, false, true, true],
            'create permission denied' => [UserVoter::CREATE, null, true, false, false, false],
            'view permission granted' => [UserVoter::VIEW, new User(), true, false, true, true],
            'view permission denied' => [UserVoter::VIEW, new User(), true, false, false, false],
            'view self' => [UserVoter::VIEW, new User(), true, true, false, true],
            'edit permission granted' => [UserVoter::EDIT, new User(), true, false, true, true],
            'edit permission denied' => [UserVoter::EDIT, new User(), true, false, false, false],
            'edit self' => [UserVoter::EDIT, new User(), true, true, false, true],
            'delete permission granted' => [UserVoter::DELETE, new User(), true, false, true, true],
            'delete permission denied' => [UserVoter::DELETE, new User(), true, false, false, false],
            'delete self' => [UserVoter::DELETE, new User(), true, true, true, false],
            'change role permission granted' => [UserVoter::CHANGE_ROLE, new User(), true, false, true, true],
            'change role permission denied' => [UserVoter::CHANGE_ROLE, new User(), true, false, false, false],
            'change own role' => [UserVoter::CHANGE_ROLE, new User(), true, true, true, false],
        ];
    }

    public function testCanViewAsProjectManager(): void
    {
        $currentUser = new User();
        $targetUser = new User();
        
        $project = $this->createMock(Project::class);
        $project->method('getProjectManager')->willReturn($currentUser);
        
        $task = $this->createMock(Tasks::class);
        $task->method('getTaskProject')->willReturn($project);
        
        $this->tasksRepository->method('findBy')
            ->with(['taskAssignedTo' => $targetUser])
            ->willReturn([$task]);
        
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($currentUser);

        $this->permissionService->method('canViewUserList')->willReturn(false);
        $this->permissionService->method('hasPermission')
            ->with('view_team_members')
            ->willReturn(true);

        $method = new \ReflectionMethod(UserVoter::class, 'voteOnAttribute');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, UserVoter::VIEW, $targetUser, $token);
        $this->assertTrue($result, 'Project manager should be able to view team members');
    }

    public function testCannotDeleteUserWithTasks(): void
    {
        $currentUser = new User();
        $targetUser = new User();
        
        $task = $this->createMock(Tasks::class);
        
        $this->tasksRepository->method('findBy')
            ->with(['taskAssignedTo' => $targetUser])
            ->willReturn([$task]);
        
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($currentUser);

        $this->permissionService->method('canManageUsers')->willReturn(true);

        $method = new \ReflectionMethod(UserVoter::class, 'voteOnAttribute');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, UserVoter::DELETE, $targetUser, $token);
        $this->assertFalse($result, 'Cannot delete user with assigned tasks');
    }

    public function testCannotDeleteUserWithProjects(): void
    {
        $currentUser = new User();
        $targetUser = new User();
        
        $project = $this->createMock(Project::class);
        
        $this->tasksRepository->method('findBy')
            ->with(['taskAssignedTo' => $targetUser])
            ->willReturn([]);
            
        $this->projectRepository->method('findByManager')
            ->with($targetUser)
            ->willReturn([$project]);
        
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($currentUser);

        $this->permissionService->method('canManageUsers')->willReturn(true);

        $method = new \ReflectionMethod(UserVoter::class, 'voteOnAttribute');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, UserVoter::DELETE, $targetUser, $token);
        $this->assertFalse($result, 'Cannot delete user who is a project manager');
    }
} 