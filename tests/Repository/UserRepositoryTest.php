<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Entity\Project;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->userRepository = $this->entityManager->getRepository(User::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
        $this->userRepository = null;
        $this->passwordHasher = null;
    }

    private function createTestUser(string $email = null, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setUserEmail($email ?? uniqid() . '@example.com');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);
        $user->setUserRole($roles[0]);
        $user->setUserFirstName('Test');
        $user->setUserLastName('User');
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $user;
    }

    public function testFindByEmail(): void
    {
        // Create test user
        $email = 'test.find@example.com';
        $user = $this->createTestUser($email);

        // Test findByEmail method
        $foundUser = $this->userRepository->findByEmail($email);

        $this->assertNotNull($foundUser);
        $this->assertEquals($email, $foundUser->getUserEmail());
    }

    public function testFindByRole(): void
    {
        // Create test users with different roles
        $adminUser = $this->createTestUser(null, ['ROLE_ADMIN']);
        $managerUser = $this->createTestUser(null, ['ROLE_PROJECT_MANAGER']);
        $regularUser = $this->createTestUser(null, ['ROLE_USER']);

        // Test findByRole method for admins
        $admins = $this->userRepository->findByRole('ROLE_ADMIN');
        $this->assertCount(1, $admins);
        $this->assertEquals($adminUser->getUserEmail(), $admins[0]->getUserEmail());

        // Test findByRole method for project managers
        $managers = $this->userRepository->findByRole('ROLE_PROJECT_MANAGER');
        $this->assertCount(1, $managers);
        $this->assertEquals($managerUser->getUserEmail(), $managers[0]->getUserEmail());
    }

    public function testFindActive(): void
    {
        // Create active and inactive users
        $activeUser = $this->createTestUser();
        $activeUser->setUserUpdatedAt(new \DateTime());
        
        $inactiveUser = $this->createTestUser();
        $inactiveUser->setUserUpdatedAt(new \DateTime('-1 year'));

        $this->entityManager->flush();

        // Test findActive method
        $activeUsers = $this->userRepository->findActive();

        $this->assertGreaterThanOrEqual(1, count($activeUsers));
        $this->assertNotNull($activeUsers[0]->getUserUpdatedAt());
    }

    public function testSearch(): void
    {
        // Create test users with searchable names
        $user1 = $this->createTestUser();
        $user1->setUserFirstName('John');
        $user1->setUserLastName('Doe');

        $user2 = $this->createTestUser();
        $user2->setUserFirstName('Jane');
        $user2->setUserLastName('Smith');

        $this->entityManager->flush();

        // Test search method
        $results = $this->userRepository->search('John');

        $this->assertCount(1, $results);
        $this->assertEquals('John', $results[0]->getUserFirstName());
    }

    public function testFindByProject(): void
    {
        // Create test users
        $projectUser1 = $this->createTestUser();
        $projectUser2 = $this->createTestUser();
        $nonProjectUser = $this->createTestUser();

        // Create test project and assign users
        $project = new Project();
        $project->setProjectName('Test Project');
        $project->setProjectDescription('Test Description');
        $project->setProjectManager($projectUser1);
        $project->setProjectStartDate(new \DateTime());
        $project->setProjectTargetDate(new \DateTime('+1 month'));
        $this->entityManager->persist($project);
        $this->entityManager->flush();

        // Test findByProject method
        $projectUsers = $this->userRepository->findByProject($project);

        $this->assertCount(1, $projectUsers);
        $this->assertContains($projectUser1, $projectUsers);
        $this->assertNotContains($projectUser2, $projectUsers);
        $this->assertNotContains($nonProjectUser, $projectUsers);
    }

    public function testFindInactiveForPeriod(): void
    {
        // Create users with different last updated dates
        $recentUser = $this->createTestUser();
        $recentUser->setUserUpdatedAt(new \DateTime('-1 day'));

        $oldUser = $this->createTestUser();
        $oldUser->setUserUpdatedAt(new \DateTime('-3 months'));

        $veryOldUser = $this->createTestUser();
        $veryOldUser->setUserUpdatedAt(new \DateTime('-1 year'));

        $this->entityManager->flush();

        // Test findInactiveForPeriod method
        $inactiveUsers = $this->userRepository->findInactiveForPeriod(new \DateTime('-2 months'));

        $this->assertCount(2, $inactiveUsers);
        $this->assertContains($oldUser, $inactiveUsers);
        $this->assertContains($veryOldUser, $inactiveUsers);
        $this->assertNotContains($recentUser, $inactiveUsers);
    }
} 