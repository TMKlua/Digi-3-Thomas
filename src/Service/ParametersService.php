<?php

namespace App\Service;

use App\Entity\Parameters;
use App\Repository\ParametersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ParametersService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParametersRepository $parametersRepository,
        private PermissionService $permissionService,
        private SecurityService $securityService
    ) {}

    public function createParameter(array $data): Parameters
    {
        if (!$this->permissionService->hasPermission('manage_configuration')) {
            throw new \RuntimeException('Permission denied to create parameter');
        }

        $parameter = new Parameters();
        $parameter->setParamKey($data['key']);
        $parameter->setParamValue($data['value']);
        if (isset($data['description'])) {
            $parameter->setParamDescription($data['description']);
        }

        $this->entityManager->persist($parameter);
        $this->entityManager->flush();

        return $parameter;
    }

    public function updateParameter(int $id, array $data): Parameters
    {
        $parameter = $this->parametersRepository->find($id);
        if (!$parameter) {
            throw new \RuntimeException('Parameter not found');
        }

        if (!$this->permissionService->hasPermission('manage_configuration')) {
            throw new \RuntimeException('Permission denied to edit parameter');
        }

        if (isset($data['key'])) {
            $parameter->setParamKey($data['key']);
        }
        if (isset($data['value'])) {
            $parameter->setParamValue($data['value']);
        }
        if (isset($data['description'])) {
            $parameter->setParamDescription($data['description']);
        }

        $parameter->setParamUpdatedAt(new \DateTime());
        $parameter->setParamUpdatedBy($this->securityService->getCurrentUser());

        $this->entityManager->flush();

        return $parameter;
    }

    public function deleteParameter(int $id): void
    {
        $parameter = $this->parametersRepository->find($id);
        if (!$parameter) {
            throw new \RuntimeException('Parameter not found');
        }

        if (!$this->permissionService->hasPermission('manage_configuration')) {
            throw new \RuntimeException('Permission denied to delete parameter');
        }

        $this->entityManager->remove($parameter);
        $this->entityManager->flush();
    }

    public function getParameterByKey(string $key): ?Parameters
    {
        return $this->parametersRepository->findOneByKey($key);
    }

    public function getParametersByKeyPrefix(string $prefix): array
    {
        return $this->parametersRepository->findByKeyPrefix($prefix);
    }

    public function getRecentlyUpdatedParameters(\DateTime $since): array
    {
        return $this->parametersRepository->findRecentlyUpdated($since);
    }

    public function searchParameters(array $filters): array
    {
        return $this->parametersRepository->searchParameters($filters);
    }

    public function getParametersPaginated(int $page = 1, int $limit = 10): array
    {
        return $this->parametersRepository->findParametersPaginated($page, $limit);
    }
} 