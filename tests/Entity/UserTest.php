<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Enum\UserRole;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testGettersAndSetters(): void
    {
        // Test email
        $email = 'test@example.com';
        $this->user->setUserEmail($email);
        $this->assertEquals($email, $this->user->getUserEmail());
        $this->assertEquals($email, $this->user->getUserIdentifier());

        // Test password
        $password = 'password123';
        $this->user->setPassword($password);
        $this->assertEquals($password, $this->user->getPassword());

        // Test first name
        $firstName = 'John';
        $this->user->setUserFirstName($firstName);
        $this->assertEquals($firstName, $this->user->getUserFirstName());

        // Test last name
        $lastName = 'Doe';
        $this->user->setUserLastName($lastName);
        $this->assertEquals($lastName, $this->user->getUserLastName());

        // Test role
        $role = UserRole::ADMIN;
        $this->user->setUserRole($role);
        $this->assertEquals($role, $this->user->getUserRole());
        $this->assertContains('ROLE_ADMIN', $this->user->getRoles());

        // Test avatar
        $avatar = 'avatar.jpg';
        $this->user->setUserAvatar($avatar);
        $this->assertEquals($avatar, $this->user->getUserAvatar());

        // Test updated at
        $date = new \DateTime();
        $this->user->setUserUpdatedAt($date);
        $this->assertEquals($date, $this->user->getUserUpdatedAt());
    }

    public function testDefaultValues(): void
    {
        // Test default avatar
        $this->assertStringContainsString('default-avatar.jpg', $this->user->getUserAvatar());

        // Test default created at
        $this->assertInstanceOf(\DateTimeInterface::class, $this->user->getUserCreatedAt());

        // Test default role
        $this->assertEquals(UserRole::USER, $this->user->getUserRole());
    }

    public function testEraseCredentials(): void
    {
        // This method should do nothing, so we just test that it doesn't throw an exception
        $this->user->eraseCredentials();
        $this->assertTrue(true);
    }

    public function testRoles(): void
    {
        // Test ROLE_ADMIN
        $this->user->setUserRole(UserRole::ADMIN);
        $this->assertEquals(['ROLE_ADMIN'], $this->user->getRoles());

        // Test ROLE_PROJECT_MANAGER
        $this->user->setUserRole(UserRole::PROJECT_MANAGER);
        $this->assertEquals(['ROLE_PROJECT_MANAGER'], $this->user->getRoles());

        // Test ROLE_DEVELOPER
        $this->user->setUserRole(UserRole::DEVELOPER);
        $this->assertEquals(['ROLE_DEVELOPER'], $this->user->getRoles());

        // Test ROLE_USER
        $this->user->setUserRole(UserRole::USER);
        $this->assertEquals(['ROLE_USER'], $this->user->getRoles());
    }

    public function testToString(): void
    {
        $email = 'test@example.com';
        $this->user->setUserEmail($email);
        $this->assertEquals($email, (string) $this->user);
    }
} 