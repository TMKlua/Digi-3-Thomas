<?php

namespace App\Service;

use App\Entity\Customers;
use App\Repository\CustomersRepository;
use Doctrine\ORM\EntityManagerInterface;

class CustomersService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CustomersRepository $customersRepository,
        private PermissionService $permissionService,
        private SecurityService $securityService
    ) {}

    public function createCustomer(array $data): Customers
    {
        if (!$this->permissionService->hasPermission('manage_customers')) {
            throw new \RuntimeException('Permission denied to create customer');
        }

        $customer = new Customers();
        $customer->setCustomerName($data['name']);
        $customer->setCustomerAddressStreet($data['addressStreet'] ?? '');
        $customer->setCustomerAddressZipcode($data['addressZipcode'] ?? '');
        $customer->setCustomerAddressCity($data['addressCity'] ?? '');
        $customer->setCustomerAddressCountry($data['addressCountry'] ?? '');
        $customer->setCustomerVat($data['vat'] ?? null);
        $customer->setCustomerSiren($data['siren'] ?? null);
        $customer->setCustomerReference($data['reference'] ?? null);

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $customer;
    }

    public function updateCustomer(int $id, array $data): Customers
    {
        $customer = $this->customersRepository->find($id);
        if (!$customer) {
            throw new \RuntimeException('Customer not found');
        }

        if (!$this->permissionService->hasPermission('manage_customers')) {
            throw new \RuntimeException('Permission denied to edit customer');
        }

        if (isset($data['name'])) {
            $customer->setCustomerName($data['name']);
        }
        if (isset($data['addressStreet'])) {
            $customer->setCustomerAddressStreet($data['addressStreet']);
        }
        if (isset($data['addressZipcode'])) {
            $customer->setCustomerAddressZipcode($data['addressZipcode']);
        }
        if (isset($data['addressCity'])) {
            $customer->setCustomerAddressCity($data['addressCity']);
        }
        if (isset($data['addressCountry'])) {
            $customer->setCustomerAddressCountry($data['addressCountry']);
        }
        if (isset($data['vat'])) {
            $customer->setCustomerVat($data['vat']);
        }
        if (isset($data['siren'])) {
            $customer->setCustomerSiren($data['siren']);
        }
        if (isset($data['reference'])) {
            $customer->setCustomerReference($data['reference']);
        }

        $customer->setCustomerUpdatedAt(new \DateTime());
        $customer->setCustomerUpdatedBy($this->securityService->getCurrentUser());

        $this->entityManager->flush();

        return $customer;
    }

    public function deleteCustomer(int $id): void
    {
        $customer = $this->customersRepository->find($id);
        if (!$customer) {
            throw new \RuntimeException('Customer not found');
        }

        if (!$this->permissionService->hasPermission('manage_customers')) {
            throw new \RuntimeException('Permission denied to delete customer');
        }

        $this->entityManager->remove($customer);
        $this->entityManager->flush();
    }

    public function getCustomersByCountry(string $country): array
    {
        return $this->customersRepository->findByCountry($country);
    }

    public function getActiveCustomers(): array
    {
        return $this->customersRepository->findActiveCustomers();
    }

    public function getCustomerWithFullData(int $id): ?Customers
    {
        return $this->customersRepository->findCustomerWithFullData($id);
    }

    public function searchCustomers(array $filters): array
    {
        return $this->customersRepository->searchCustomers($filters);
    }

    public function getCustomersWithProjects(): array
    {
        return $this->customersRepository->findCustomersWithProjects();
    }
} 