<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Administrateur principal
        $admin = $this->createUser(
            'John', 
            'Doe', 
            'admin@digiworks.fr', 
            'Admin123!',
            User::ROLE_ADMIN
        );
        $manager->persist($admin);

        // Responsable
        $responsable = $this->createUser(
            'Marie', 
            'Dupont', 
            'responsable@digiworks.fr', 
            'Responsable123!',
            User::ROLE_RESPONSABLE
        );
        $manager->persist($responsable);

        // Chef de Projet
        $projectManager = $this->createUser(
            'Pierre', 
            'Martin', 
            'pm@digiworks.fr', 
            'ProjectManager123!',
            User::ROLE_PROJECT_MANAGER
        );
        $manager->persist($projectManager);

        // Lead Développeur
        $leadDeveloper = $this->createUser(
            'Sophie', 
            'Leroy', 
            'lead@digiworks.fr', 
            'LeadDev123!',
            User::ROLE_LEAD_DEVELOPER
        );
        $manager->persist($leadDeveloper);

        // Développeur
        $developer = $this->createUser(
            'Lucas', 
            'Blanc', 
            'dev@digiworks.fr', 
            'Dev123!',
            User::ROLE_DEVELOPER
        );
        $manager->persist($developer);

        // Utilisateur standard
        $user = $this->createUser(
            'Emma', 
            'Durand', 
            'user@digiworks.fr', 
            'User123!',
            User::ROLE_USER
        );
        $manager->persist($user);

        $manager->flush();
    }

    private function createUser(
        string $firstName, 
        string $lastName, 
        string $email, 
        string $plainPassword,
        string $role
    ): User {
        $user = new User();
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);

        $user->setUserFirstName($firstName)
             ->setUserLastName($lastName)
             ->setUserEmail($email)
             ->setPassword($hashedPassword)
             ->setUserRole($role)
             ->setUserAvatar('/img/account/default-avatar.jpg')
             ->setUserDateFrom(new \DateTime())
             ->setResetToken(null)
             ->setResetTokenExpiresAt(null)
             ->setUserDateTo(null)
             ->setUserUserMaj(null);

        return $user;
    }
}
