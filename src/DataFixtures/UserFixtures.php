<?php
namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager)
    {
        // Créer un utilisateur admin
        $admin = new User();
        $admin->setUserEmail('admin@example.com');
        $admin->setUserFirstName('Admin');
        $admin->setUserLastName('User');
        $admin->setRoles(['ROLE_ADMIN']);
        
        // Hachage du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'your_password_here');
        $admin->setPassword($hashedPassword);

        // Persist et flush
        $manager->persist($admin);
        $manager->flush();
    }
} 