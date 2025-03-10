<?php

namespace App\Tests\Security\Voter;

use App\Entity\Customers;
use App\Entity\Project;
use App\Entity\User;
use App\Security\Voter\CustomerVoter;
use App\Service\PermissionService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CustomerVoterTest extends TestCase
{
    private CustomerVoter $voter;
    /** @var PermissionService&MockObject */
    private $permissionService;

    protected function setUp(): void
    {
        $this->permissionService = $this->createMock(PermissionService::class);
        $this->voter = new CustomerVoter($this->permissionService);
    }

    public function testVoterConstants(): void
    {
        // Test that the voter constants are defined correctly
        $this->assertEquals('view', CustomerVoter::VIEW);
        $this->assertEquals('edit', CustomerVoter::EDIT);
        $this->assertEquals('delete', CustomerVoter::DELETE);
        $this->assertEquals('create', CustomerVoter::CREATE);
    }

    /**
     * @dataProvider provideSupportsTestCases
     */
    public function testSupports(string $attribute, mixed $subject, bool $expected): void
    {
        $method = new \ReflectionMethod(CustomerVoter::class, 'supports');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, $attribute, $subject);
        $this->assertSame($expected, $result);
    }

    public function provideSupportsTestCases(): array
    {
        return [
            'valid attribute and subject' => [CustomerVoter::VIEW, new Customers(), true],
            'valid attribute for create' => [CustomerVoter::CREATE, null, true],
            'invalid attribute' => ['invalid_attribute', new Customers(), false],
            'invalid subject' => [CustomerVoter::VIEW, new \stdClass(), false],
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

        if ($attribute === CustomerVoter::CREATE) {
            $this->permissionService->method('hasPermission')
                ->with('manage_customers')
                ->willReturn($permissionResult);
        } elseif ($attribute === CustomerVoter::VIEW) {
            $this->permissionService->method('canViewCustomerList')
                ->willReturn($permissionResult);
        } elseif ($attribute === CustomerVoter::EDIT) {
            $this->permissionService->method('canEditCustomer')
                ->willReturn($permissionResult);
        } elseif ($attribute === CustomerVoter::DELETE) {
            $this->permissionService->method('canDeleteCustomer')
                ->willReturn($permissionResult);
            if ($subject instanceof Customers) {
                // Simulate a customer with no projects for delete permission
                $mockCustomer = $this->createMock(Customers::class);
                $mockCustomer->method('getProjects')->willReturn(new ArrayCollection());
                $subject = $mockCustomer;
            }
        }

        $method = new \ReflectionMethod(CustomerVoter::class, 'voteOnAttribute');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, $attribute, $subject, $token);
        $this->assertSame($expected, $result);
    }

    public function provideVoteOnAttributeTestCases(): array
    {
        $customer = $this->createMock(Customers::class);
        
        return [
            'no user' => [CustomerVoter::VIEW, $customer, false, false, false],
            'create permission granted' => [CustomerVoter::CREATE, null, true, true, true],
            'create permission denied' => [CustomerVoter::CREATE, null, true, false, false],
            'view permission granted' => [CustomerVoter::VIEW, $customer, true, true, true],
            'view permission denied' => [CustomerVoter::VIEW, $customer, true, false, false],
            'edit permission granted' => [CustomerVoter::EDIT, $customer, true, true, true],
            'edit permission denied' => [CustomerVoter::EDIT, $customer, true, false, false],
            'delete permission granted' => [CustomerVoter::DELETE, $customer, true, true, true],
            'delete permission denied' => [CustomerVoter::DELETE, $customer, true, false, false],
        ];
    }

    public function testCanViewAsProjectManager(): void
    {
        $user = new User();
        $project = $this->createMock(Project::class);
        $project->method('getProjectManager')->willReturn($user);
        
        $customer = $this->createMock(Customers::class);
        $customer->method('getProjects')->willReturn(new ArrayCollection([$project]));

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->permissionService->method('canViewCustomerList')->willReturn(false);

        $method = new \ReflectionMethod(CustomerVoter::class, 'voteOnAttribute');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, CustomerVoter::VIEW, $customer, $token);
        $this->assertTrue($result, 'Project manager should be able to view the customer');
    }

    public function testCannotDeleteCustomerWithProjects(): void
    {
        $user = new User();
        $project = $this->createMock(Project::class);
        
        $customer = $this->createMock(Customers::class);
        $customer->method('getProjects')->willReturn(new ArrayCollection([$project]));

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->permissionService->method('canDeleteCustomer')->willReturn(true);

        $method = new \ReflectionMethod(CustomerVoter::class, 'voteOnAttribute');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, CustomerVoter::DELETE, $customer, $token);
        $this->assertFalse($result, 'Cannot delete customer with projects');
    }
} 