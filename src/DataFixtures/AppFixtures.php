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
        $admin = User::create(
            'John', 
            'Doe', 
            'admin@digiworks.fr', 
            $this->passwordHasher->hashPassword(new User(), 'Admin123!')
        );
        $admin->setUserRole(User::ROLE_ADMIN)
              ->setUserAvatar('/img/account/default-avatar.jpg');
        $manager->persist($admin);

        // Responsable
        $responsable = User::create(
            'Marie', 
            'Dupont', 
            'responsable@digiworks.fr', 
            $this->passwordHasher->hashPassword(new User(), 'Responsable123!')
        );
        $responsable->setUserRole(User::ROLE_RESPONSABLE)
                    ->setUserAvatar('/img/account/default-avatar.jpg');
        $manager->persist($responsable);

        // Chef de Projet
        $projectManager = User::create(
            'Pierre', 
            'Martin', 
            'pm@digiworks.fr', 
            $this->passwordHasher->hashPassword(new User(), 'ProjectManager123!')
        );
        $projectManager->setUserRole(User::ROLE_PROJECT_MANAGER)
                       ->setUserAvatar('/img/account/default-avatar.jpg');
        $manager->persist($projectManager);

        // Développeur Lead
        $leadDeveloper = User::create(
            'Sophie', 
            'Leroy', 
            'lead@digiworks.fr', 
            $this->passwordHasher->hashPassword(new User(), 'LeadDev123!')
        );
        $leadDeveloper->setUserRole(User::ROLE_LEAD_DEVELOPER)
                      ->setUserAvatar('/img/account/default-avatar.jpg');
        $manager->persist($leadDeveloper);

        // Développeur
        $developer = User::create(
            'Lucas', 
            'Blanc', 
            'dev@digiworks.fr', 
            $this->passwordHasher->hashPassword(new User(), 'Dev123!')
        );
        $developer->setUserRole(User::ROLE_DEVELOPER)
                  ->setUserAvatar('/img/account/default-avatar.jpg');
        $manager->persist($developer);

        // Utilisateur standard
        $user = User::create(
            'Emma', 
            'Durand', 
            'user@digiworks.fr', 
            $this->passwordHasher->hashPassword(new User(), 'User123!')
        );
        $user->setUserRole(User::ROLE_USER)
             ->setUserAvatar('/img/account/default-avatar.jpg');
        $manager->persist($user);

        $manager->flush();
    }
}
