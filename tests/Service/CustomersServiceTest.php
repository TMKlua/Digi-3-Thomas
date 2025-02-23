<?php

namespace App\Tests\Service;

use App\Entity\Customers;
use App\Entity\User;
use App\Repository\CustomersRepository;
use App\Service\CustomersService;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class CustomersServiceTest extends TestCase
{
    private CustomersService $customersService;
    private EntityManagerInterface $entityManager;
    private CustomersRepository $customersRepository;
    private PermissionService $permissionService;
    private Security $security;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->customersRepository = $this->createMock(CustomersRepository::class);
        $this->permissionService = $this->createMock(PermissionService::class);
        $this->security = $this->createMock(Security::class);

        $this->customersService = new CustomersService(
            $this->entityManager,
            $this->customersRepository,
            $this->permissionService,
            $this->security
        );
    }

    public function testCreateCustomer(): void
    {
        // Arrange
        $user = new User();
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('manage_customers')
            ->willReturn(true);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Customers::class));
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $customerData = [
            'name' => 'Test Customer',
            'addressStreet' => '123 Test St',
            'addressZipcode' => '12345',
            'addressCity' => 'Test City',
            'addressCountry' => 'Test Country',
            'vat' => 'TEST123',
            'siren' => '123456789',
            'reference' => 'REF001'
        ];

        $customer = $this->customersService->createCustomer($customerData);

        // Assert
        $this->assertEquals($customerData['name'], $customer->getCustomerName());
        $this->assertEquals($customerData['addressStreet'], $customer->getCustomerAddressStreet());
        $this->assertEquals($customerData['addressZipcode'], $customer->getCustomerAddressZipcode());
        $this->assertEquals($customerData['addressCity'], $customer->getCustomerAddressCity());
        $this->assertEquals($customerData['addressCountry'], $customer->getCustomerAddressCountry());
        $this->assertEquals($customerData['vat'], $customer->getCustomerVat());
        $this->assertEquals($customerData['siren'], $customer->getCustomerSiren());
        $this->assertEquals($customerData['reference'], $customer->getCustomerReference());
    }

    public function testUpdateCustomer(): void
    {
        // Arrange
        $customer = new Customers();
        $customer->setCustomerName('Original Name');

        $user = new User();
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->customersRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($customer);

        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('manage_customers')
            ->willReturn(true);

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $updateData = [
            'name' => 'Updated Name',
            'addressCity' => 'Updated City'
        ];

        $updatedCustomer = $this->customersService->updateCustomer(1, $updateData);

        // Assert
        $this->assertEquals($updateData['name'], $updatedCustomer->getCustomerName());
        $this->assertEquals($updateData['addressCity'], $updatedCustomer->getCustomerAddressCity());
    }

    public function testDeleteCustomer(): void
    {
        // Arrange
        $customer = new Customers();
        $customer->setCustomerName('Customer to Delete');

        $this->customersRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($customer);

        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('manage_customers')
            ->willReturn(true);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($customer);
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $this->customersService->deleteCustomer(1);
    }

    public function testGetActiveCustomers(): void
    {
        // Arrange
        $customers = [
            new Customers(),
            new Customers()
        ];

        $this->customersRepository->expects($this->once())
            ->method('findActiveCustomers')
            ->willReturn($customers);

        // Act
        $result = $this->customersService->getActiveCustomers();

        // Assert
        $this->assertEquals($customers, $result);
        $this->assertCount(2, $result);
    }
} 