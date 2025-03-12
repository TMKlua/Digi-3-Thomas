<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Service\SecurityService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Tests unitaires pour le service de sécurité
 */
class SecurityServiceTest extends TestCase
{
    private SecurityService $securityService;
    
    /**
     * @var Security&MockObject
     */
    private MockObject $security;
    
    /**
     * @var UserPasswordHasherInterface&MockObject
     */
    private MockObject $passwordHasher;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->securityService = new SecurityService(
            $this->security,
            $this->passwordHasher
        );
    }

    /**
     * Test de la méthode hashPassword
     */
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

    /**
     * Test de la méthode isPasswordValid
     */
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

    /**
     * Test de la méthode getCurrentUser
     */
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

    /**
     * Test de la méthode getCurrentUser lorsqu'aucun utilisateur n'est connecté
     */
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

    /**
     * Test de la méthode getCurrentUserOrThrow lorsqu'un utilisateur est connecté
     */
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

    /**
     * Test de la méthode getCurrentUserOrThrow lorsqu'aucun utilisateur n'est connecté
     */
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

    /**
     * Test de la méthode isGranted
     */
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

    /**
     * Test de la méthode isGranted avec un sujet
     */
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

    /**
     * Test de la validation de mot de passe
     * Note: Cette méthode n'existe pas encore dans le service SecurityService, elle devrait être implémentée
     */
    /*
    public function testValidatePassword(): void
    {
        // Mot de passe valide
        $validPassword = 'Password123!';
        $errors = $this->securityService->validatePassword($validPassword);
        $this->assertEmpty($errors);
        
        // Mot de passe trop court
        $shortPassword = 'Pass1!';
        $errors = $this->securityService->validatePassword($shortPassword);
        $this->assertNotEmpty($errors);
        $this->assertContains('Le mot de passe doit contenir au moins 8 caractères', $errors);
        
        // Mot de passe sans majuscule
        $noUppercasePassword = 'password123!';
        $errors = $this->securityService->validatePassword($noUppercasePassword);
        $this->assertNotEmpty($errors);
        $this->assertContains('Le mot de passe doit contenir au moins une lettre majuscule', $errors);
        
        // Mot de passe sans minuscule
        $noLowercasePassword = 'PASSWORD123!';
        $errors = $this->securityService->validatePassword($noLowercasePassword);
        $this->assertNotEmpty($errors);
        $this->assertContains('Le mot de passe doit contenir au moins une lettre minuscule', $errors);
        
        // Mot de passe sans chiffre
        $noDigitPassword = 'Password!';
        $errors = $this->securityService->validatePassword($noDigitPassword);
        $this->assertNotEmpty($errors);
        $this->assertContains('Le mot de passe doit contenir au moins un chiffre', $errors);
        
        // Mot de passe sans caractère spécial
        $noSpecialCharPassword = 'Password123';
        $errors = $this->securityService->validatePassword($noSpecialCharPassword);
        $this->assertNotEmpty($errors);
        $this->assertContains('Le mot de passe doit contenir au moins un caractère spécial', $errors);
    }
    */
} 