<?php

namespace App\Tests\Service;

use App\Entity\Parameters;
use App\Entity\User;
use App\Repository\ParametersRepository;
use App\Service\ParametersService;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class ParametersServiceTest extends TestCase
{
    private ParametersService $parametersService;
    private EntityManagerInterface|MockObject $entityManager;
    private ParametersRepository|MockObject $parametersRepository;
    private PermissionService|MockObject $permissionService;
    private Security|MockObject $security;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->parametersRepository = $this->createMock(ParametersRepository::class);
        $this->permissionService = $this->createMock(PermissionService::class);
        $this->security = $this->createMock(Security::class);

        $this->parametersService = new ParametersService(
            $this->entityManager,
            $this->parametersRepository,
            $this->permissionService,
            $this->security
        );
    }

    public function testCreateParameter(): void
    {
        // Arrange
        $data = [
            'key' => 'test_key',
            'value' => 'test_value',
            'description' => 'Test description'
        ];

        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('manage_configuration')
            ->willReturn(true);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Parameters::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $parameter = $this->parametersService->createParameter($data);

        // Assert
        $this->assertInstanceOf(Parameters::class, $parameter);
        $this->assertEquals($data['key'], $parameter->getParamKey());
        $this->assertEquals($data['value'], $parameter->getParamValue());
        $this->assertEquals($data['description'], $parameter->getParamDescription());
    }

    public function testUpdateParameter(): void
    {
        // Arrange
        $id = 1;
        $existingParameter = new Parameters();
        $user = new User();
        $data = [
            'key' => 'updated_key',
            'value' => 'updated_value',
            'description' => 'Updated description'
        ];

        $this->parametersRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($existingParameter);

        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('manage_configuration')
            ->willReturn(true);

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $parameter = $this->parametersService->updateParameter($id, $data);

        // Assert
        $this->assertInstanceOf(Parameters::class, $parameter);
        $this->assertEquals($data['key'], $parameter->getParamKey());
        $this->assertEquals($data['value'], $parameter->getParamValue());
        $this->assertEquals($data['description'], $parameter->getParamDescription());
        $this->assertEquals($user, $parameter->getParamUpdatedBy());
    }

    public function testDeleteParameter(): void
    {
        // Arrange
        $id = 1;
        $parameter = new Parameters();

        $this->parametersRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($parameter);

        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('manage_configuration')
            ->willReturn(true);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($parameter);

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $this->parametersService->deleteParameter($id);
    }

    public function testGetParameterByKey(): void
    {
        // Arrange
        $key = 'test_key';
        $parameter = new Parameters();

        $this->parametersRepository->expects($this->once())
            ->method('findOneByKey')
            ->with($key)
            ->willReturn($parameter);

        // Act
        $result = $this->parametersService->getParameterByKey($key);

        // Assert
        $this->assertSame($parameter, $result);
    }

    public function testGetRecentlyUpdatedParameters(): void
    {
        // Arrange
        $since = new \DateTime('-1 week');
        $parameters = [new Parameters(), new Parameters()];

        $this->parametersRepository->expects($this->once())
            ->method('findRecentlyUpdated')
            ->with($since)
            ->willReturn($parameters);

        // Act
        $result = $this->parametersService->getRecentlyUpdatedParameters($since);

        // Assert
        $this->assertEquals($parameters, $result);
        $this->assertCount(2, $result);
    }

    public function testSearchParameters(): void
    {
        // Arrange
        $filters = ['key' => 'test'];
        $parameters = [new Parameters()];

        $this->parametersRepository->expects($this->once())
            ->method('searchParameters')
            ->with($filters)
            ->willReturn($parameters);

        // Act
        $result = $this->parametersService->searchParameters($filters);

        // Assert
        $this->assertEquals($parameters, $result);
        $this->assertCount(1, $result);
    }
} 