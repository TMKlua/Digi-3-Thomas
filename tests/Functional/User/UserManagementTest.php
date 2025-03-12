<?php

namespace App\Tests\Functional\User;

use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests fonctionnels pour la gestion des utilisateurs
 * Scénarios couverts : U1, U2, U3, U4, U5
 */
class UserManagementTest extends WebTestCase
{
    private $client;
    private $userRepository;
    private $adminUser;
    private $developerUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        
        // Récupération d'un utilisateur admin pour les tests
        $this->adminUser = $this->userRepository->findOneBy(['userRole' => UserRole::ADMIN]);
        
        // Si aucun admin n'existe, on en crée un
        if (!$this->adminUser) {
            $this->adminUser = new User();
            $this->adminUser->setUserEmail('admin@test.com');
            $this->adminUser->setPassword('$2y$13$holeQTxBeWN/1WNzrNQYXOZx0hV2VCMmj0BMJEgEzwVvnQjtoKsLe'); // password = 'password123'
            $this->adminUser->setUserFirstName('Admin');
            $this->adminUser->setUserLastName('Test');
            $this->adminUser->setUserRole(UserRole::ADMIN);
            
            $entityManager = static::getContainer()->get('doctrine')->getManager();
            $entityManager->persist($this->adminUser);
            $entityManager->flush();
        }
        
        // Récupération d'un utilisateur développeur pour les tests
        $this->developerUser = $this->userRepository->findOneBy(['userRole' => UserRole::DEVELOPER]);
        
        // Si aucun développeur n'existe, on en crée un
        if (!$this->developerUser) {
            $this->developerUser = new User();
            $this->developerUser->setUserEmail('developer@test.com');
            $this->developerUser->setPassword('$2y$13$holeQTxBeWN/1WNzrNQYXOZx0hV2VCMmj0BMJEgEzwVvnQjtoKsLe'); // password = 'password123'
            $this->developerUser->setUserFirstName('Developer');
            $this->developerUser->setUserLastName('Test');
            $this->developerUser->setUserRole(UserRole::DEVELOPER);
            
            $entityManager = static::getContainer()->get('doctrine')->getManager();
            $entityManager->persist($this->developerUser);
            $entityManager->flush();
        }
    }

    /**
     * Test U1 : Création d'un utilisateur
     * 
     * Scénario :
     * 1. Se connecter en tant qu'administrateur
     * 2. Accéder à la gestion des utilisateurs
     * 3. Ajouter un nouvel utilisateur avec rôle défini
     * 4. Valider la création
     * 
     * Résultat attendu : L'utilisateur est ajouté et visible dans la liste
     */
    public function testCreateUser(): void
    {
        // 1. Se connecter en tant qu'administrateur
        $this->client->loginUser($this->adminUser);
        
        // 2. Accéder à la gestion des utilisateurs
        $crawler = $this->client->request('GET', '/admin/users');
        $this->assertResponseIsSuccessful();
        
        // 3. Ajouter un nouvel utilisateur avec rôle défini
        $this->client->request('POST', '/admin/users/create', [
            'user' => [
                'userEmail' => 'newuser@test.com',
                'plainPassword' => 'Password123!',
                'userFirstName' => 'New',
                'userLastName' => 'User',
                'userRole' => UserRole::USER->value,
                '_token' => $this->client->getContainer()->get('security.csrf.token_manager')->getToken('user_form')->getValue()
            ]
        ]);
        
        // 4. Valider la création et vérifier la redirection
        $this->assertResponseRedirects('/admin/users');
        $this->client->followRedirect();
        
        // Vérifier que l'utilisateur est ajouté et visible dans la liste
        $this->assertSelectorTextContains('.alert-success', 'Utilisateur créé avec succès');
        $this->assertSelectorTextContains('table', 'newuser@test.com');
    }

    /**
     * Test U2 : Modification d'un utilisateur
     * 
     * Scénario :
     * 1. Se connecter en tant qu'administrateur
     * 2. Modifier les informations d'un utilisateur
     * 3. Enregistrer les modifications
     * 
     * Résultat attendu : Les modifications sont bien prises en compte
     */
    public function testEditUser(): void
    {
        // Créer un utilisateur à modifier
        $userToEdit = new User();
        $userToEdit->setUserEmail('edit-user@test.com');
        $userToEdit->setPassword('$2y$13$holeQTxBeWN/1WNzrNQYXOZx0hV2VCMmj0BMJEgEzwVvnQjtoKsLe');
        $userToEdit->setUserFirstName('Before');
        $userToEdit->setUserLastName('Edit');
        $userToEdit->setUserRole(UserRole::USER);
        
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($userToEdit);
        $entityManager->flush();
        
        // 1. Se connecter en tant qu'administrateur
        $this->client->loginUser($this->adminUser);
        
        // 2. Modifier les informations d'un utilisateur
        $userId = $userToEdit->getId();
        $this->client->request('POST', "/admin/users/edit/{$userId}", [
            'user' => [
                'userEmail' => 'edit-user@test.com', // inchangé
                'userFirstName' => 'After',
                'userLastName' => 'Update',
                'userRole' => UserRole::PROJECT_MANAGER->value,
                '_token' => $this->client->getContainer()->get('security.csrf.token_manager')->getToken('user_form')->getValue()
            ]
        ]);
        
        // 3. Vérifier la redirection et les modifications
        $this->assertResponseRedirects('/admin/users');
        $this->client->followRedirect();
        
        // Vérifier que les modifications sont bien prises en compte
        $this->assertSelectorTextContains('.alert-success', 'Utilisateur modifié avec succès');
        
        // Vérifier en base de données
        $updatedUser = $this->userRepository->find($userId);
        $this->assertEquals('After', $updatedUser->getUserFirstName());
        $this->assertEquals('Update', $updatedUser->getUserLastName());
        $this->assertEquals(UserRole::PROJECT_MANAGER, $updatedUser->getUserRole());
    }

    /**
     * Test U3 : Suppression d'un utilisateur
     * 
     * Scénario :
     * 1. Se connecter en tant qu'administrateur
     * 2. Supprimer un utilisateur
     * 3. Vérifier qu'il ne figure plus dans la liste
     * 
     * Résultat attendu : L'utilisateur est supprimé
     */
    public function testDeleteUser(): void
    {
        // Créer un utilisateur à supprimer
        $userToDelete = new User();
        $userToDelete->setUserEmail('delete-user@test.com');
        $userToDelete->setPassword('$2y$13$holeQTxBeWN/1WNzrNQYXOZx0hV2VCMmj0BMJEgEzwVvnQjtoKsLe');
        $userToDelete->setUserFirstName('To');
        $userToDelete->setUserLastName('Delete');
        $userToDelete->setUserRole(UserRole::USER);
        
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($userToDelete);
        $entityManager->flush();
        
        $userId = $userToDelete->getId();
        
        // 1. Se connecter en tant qu'administrateur
        $this->client->loginUser($this->adminUser);
        
        // 2. Supprimer un utilisateur
        $this->client->request('DELETE', "/admin/users/delete/{$userId}", [
            '_token' => $this->client->getContainer()->get('security.csrf.token_manager')->getToken('delete_user_'.$userId)->getValue()
        ]);
        
        // 3. Vérifier la redirection et la suppression
        $this->assertResponseRedirects('/admin/users');
        $this->client->followRedirect();
        
        // Vérifier que l'utilisateur est supprimé
        $this->assertSelectorTextContains('.alert-success', 'Utilisateur supprimé avec succès');
        
        // Vérifier en base de données
        $deletedUser = $this->userRepository->find($userId);
        $this->assertNull($deletedUser);
    }

    /**
     * Test U4 : Vérification des droits administrateur
     * 
     * Scénario :
     * 1. Se connecter en tant qu'administrateur
     * 2. Accéder à toutes les fonctionnalités
     * 
     * Résultat attendu : L'administrateur a bien accès à toutes les options
     */
    public function testAdminRights(): void
    {
        // 1. Se connecter en tant qu'administrateur
        $this->client->loginUser($this->adminUser);
        
        // 2. Accéder à toutes les fonctionnalités et vérifier l'accès
        // Gestion des utilisateurs
        $this->client->request('GET', '/admin/users');
        $this->assertResponseIsSuccessful();
        
        // Gestion des projets
        $this->client->request('GET', '/admin/projects');
        $this->assertResponseIsSuccessful();
        
        // Gestion des paramètres
        $this->client->request('GET', '/admin/parameters');
        $this->assertResponseIsSuccessful();
        
        // Tableau de bord
        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
    }

    /**
     * Test U5 : Vérification des droits développeur
     * 
     * Scénario :
     * 1. Se connecter en tant que développeur
     * 2. Tenter d'accéder aux options d'administration
     * 
     * Résultat attendu : L'accès est refusé et un message d'erreur est affiché
     */
    public function testDeveloperRights(): void
    {
        // 1. Se connecter en tant que développeur
        $this->client->loginUser($this->developerUser);
        
        // 2. Tenter d'accéder aux options d'administration
        $this->client->request('GET', '/admin/users');
        
        // Vérifier que l'accès est refusé (code 403)
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        
        // Vérifier qu'un message d'erreur est affiché
        $this->assertSelectorTextContains('body', 'Accès refusé');
    }
} 