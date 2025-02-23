<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\SecurityService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SecurityServiceTest extends TestCase
{
    private SecurityService $securityService;
    private Security|MockObject $security;
    private UserPasswordHasherInterface|MockObject $passwordHasher;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->securityService = new SecurityService(
            $this->security,
            $this->passwordHasher
        );
    }

    public function testHashPassword(): void
    {
        // Arrange
        $user = new User();
        $plainPassword = 'password123';
        $hashedPassword = 'hashed_password';

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, $plainPassword)
            ->willReturn($hashedPassword);

        // Act
        $result = $this->securityService->hashPassword($user, $plainPassword);

        // Assert
        $this->assertEquals($hashedPassword, $result);
    }

    public function testIsPasswordValid(): void
    {
        // Arrange
        $user = new User();
        $plainPassword = 'password123';

        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, $plainPassword)
            ->willReturn(true);

        // Act
        $result = $this->securityService->isPasswordValid($user, $plainPassword);

        // Assert
        $this->assertTrue($result);
    }

    public function testGetCurrentUser(): void
    {
        // Arrange
        $user = new User();
        
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        // Act
        $result = $this->securityService->getCurrentUser();

        // Assert
        $this->assertSame($user, $result);
    }

    public function testGetCurrentUserReturnsNullWhenNoUser(): void
    {
        // Arrange
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        // Act
        $result = $this->securityService->getCurrentUser();

        // Assert
        $this->assertNull($result);
    }

    public function testGetCurrentUserOrThrowReturnsUser(): void
    {
        // Arrange
        $user = new User();
        
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        // Act
        $result = $this->securityService->getCurrentUserOrThrow();

        // Assert
        $this->assertSame($user, $result);
    }

    public function testGetCurrentUserOrThrowThrowsException(): void
    {
        // Arrange
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        // Assert & Act
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. User not authenticated.');
        
        $this->securityService->getCurrentUserOrThrow();
    }

    public function testIsGranted(): void
    {
        // Arrange
        $attribute = 'ROLE_ADMIN';
        $subject = null;

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with($attribute, $subject)
            ->willReturn(true);

        // Act
        $result = $this->securityService->isGranted($attribute, $subject);

        // Assert
        $this->assertTrue($result);
    }

    public function testIsGrantedWithSubject(): void
    {
        // Arrange
        $attribute = 'EDIT';
        $subject = new User();

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with($attribute, $subject)
            ->willReturn(true);

        // Act
        $result = $this->securityService->isGranted($attribute, $subject);

        // Assert
        $this->assertTrue($result);
    }
} 