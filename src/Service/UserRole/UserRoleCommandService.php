<?php

namespace App\Service\UserRole;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRoleCommandService extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure()
    {
        $this->setName('app:create-default-users')
            ->setDescription('Create default users with predefined roles');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = [
            ['admin@digiworks.fr', 'Admin123!', 'ROLE_ADMIN', 'Jean', 'Dupont'],
            ['manager@digiworks.fr', 'Manager123!', 'ROLE_MANAGER', 'Marc', 'Lanvin'],
            ['lead@digiworks.fr', 'LeadDev123!', 'ROLE_LEAD_DEV', 'Sophie', 'Durand'],
            ['dev@digiworks.fr', 'Dev123!', 'ROLE_DEV', 'Alexandre', 'Lemoine'],
            ['user@digiworks.fr', 'User123!', 'ROLE_USER', 'Claire', 'Dufresne'],
        ];        

        foreach ($users as [$email, $password, $role, $firstName, $lastName]) {
            $user = new User();
            $user->setUserEmail($email);
            $user->setUserFirstName($firstName);  // Ajout du prénom
            $user->setUserLastName($lastName);    // Ajout du nom de famille

            // Hachage du mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            // Affectation du rôle à l'utilisateur
            $user->setUserRole($role);

            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();

        $output->writeln('Default users created successfully!');
        return Command::SUCCESS;
    }
}
