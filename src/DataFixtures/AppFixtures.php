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
        // Création de l'administrateur
        $admin = User::create(
            'Admin',
            'System',
            'admin@digiworks.fr',
            $this->passwordHasher->hashPassword(new User(), 'Admin123!')
        );
        $admin->setUserRole('ROLE_ADMIN');
        $manager->persist($admin);

        // Création d'un responsable
        $responsable = User::create(
            'John',
            'Doe',
            'responsable@digiworks.fr',
            $this->passwordHasher->hashPassword(new User(), 'Project123!')
        );
        $responsable->setUserRole('ROLE_RESPONSABLE');
        $manager->persist($responsable);

        // Création d'un chef de projet
        $teamLeader = User::create(
            'Team',
            'Leader',
            'tl@digiworks.fr',
            $this->passwordHasher->hashPassword(new User(), 'Team123!')
        );
        $teamLeader->setUserRole('ROLE_TEAM_LEADER');
        $manager->persist($teamLeader);

        // Création d'un lead développeur 
        $leaddeveloper = User::create(
            'Dev',
            'lead',
            'leadev@digiworks.fr',
            $this->passwordHasher->hashPassword(new User(), 'Leadev123!')
        );
        $leaddeveloper->setUserRole('ROLE_LEAD_DEVELOPER');
        $manager->persist($leaddeveloper);
        // Création d'un développeur
        $developer = User::create(
            'Dev',
            'Junior',
            'dev@digiworks.fr',
            $this->passwordHasher->hashPassword(new User(), 'Dev123!')
        );
        $developer->setUserRole('ROLE_DEVELOPER');
        $manager->persist($developer);

        // Création d'un utilisateur standard
        $user = User::create(
            'User',
            'Standard',
            'user@digiworks.fr',
            $this->passwordHasher->hashPassword(new User(), 'User123!')
        );
        $manager->persist($user);

        $manager->flush();
    }
}
