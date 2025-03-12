<?php

namespace App\Tests\Functional\Auth;

use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Transport\InMemoryTransport;

/**
 * Tests fonctionnels pour l'authentification et la sécurité
 * Scénarios couverts : A1, A2, A3, A4
 */
class AuthenticationTest extends WebTestCase
{
    private $client;
    private $userRepository;
    private $testUser;
    private $csrfTokenManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->csrfTokenManager = static::getContainer()->get('security.csrf.token_manager');
        
        // Création d'un utilisateur de test s'il n'existe pas
        $this->testUser = $this->userRepository->findOneBy(['userEmail' => 'test-auth@example.com']);
        
        if (!$this->testUser) {
            $this->testUser = new User();
            $this->testUser->setUserEmail('test-auth@example.com');
            $this->testUser->setPassword('$2y$13$holeQTxBeWN/1WNzrNQYXOZx0hV2VCMmj0BMJEgEzwVvnQjtoKsLe'); // password = 'password123'
            $this->testUser->setUserFirstName('Test');
            $this->testUser->setUserLastName('Auth');
            $this->testUser->setUserRole(UserRole::USER);
            
            $entityManager = static::getContainer()->get('doctrine')->getManager();
            $entityManager->persist($this->testUser);
            $entityManager->flush();
        }
    }

    /**
     * Test A1 : Connexion avec identifiants valides
     * 
     * Scénario :
     * 1. Saisir un email et mot de passe corrects
     * 2. Valider
     * 
     * Résultat attendu : Accès à l'espace utilisateur
     */
    public function testLoginWithValidCredentials(): void
    {
        // Accéder à la page d'authentification
        $crawler = $this->client->request('GET', '/auth');
        $this->assertResponseIsSuccessful();
        
        // Récupérer le token CSRF
        $csrfToken = $this->csrfTokenManager->getToken('authenticate')->getValue();
        
        // Soumettre le formulaire avec des identifiants valides
        $this->client->request('POST', '/auth', [
            'email' => 'test-auth@example.com',
            'password' => 'password123',
            '_csrf_token' => $csrfToken
        ]);
        
        // Vérifier la redirection vers le tableau de bord
        $this->assertResponseRedirects('/dashboard');
        $this->client->followRedirect();
        
        // Vérifier que l'utilisateur est bien connecté
        $this->assertSelectorTextContains('body', 'Tableau de bord');
    }

    /**
     * Test A2 : Connexion avec identifiants invalides
     * 
     * Scénario :
     * 1. Saisir un email/mot de passe erroné
     * 2. Valider
     * 
     * Résultat attendu : Message d'erreur affiché
     */
    public function testLoginWithInvalidCredentials(): void
    {
        // Accéder à la page d'authentification
        $crawler = $this->client->request('GET', '/auth');
        $this->assertResponseIsSuccessful();
        
        // Récupérer le token CSRF
        $csrfToken = $this->csrfTokenManager->getToken('authenticate')->getValue();
        
        // Cas 1 : Email correct, mot de passe incorrect
        $this->client->request('POST', '/auth', [
            'email' => 'test-auth@example.com',
            'password' => 'wrong_password',
            '_csrf_token' => $csrfToken
        ]);
        
        // Vérifier que l'authentification a échoué et qu'un message d'erreur est affiché
        $this->assertResponseRedirects('/auth');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'Identifiants invalides');
        
        // Cas 2 : Email incorrect
        $this->client->request('POST', '/auth', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
            '_csrf_token' => $csrfToken
        ]);
        
        // Vérifier que l'authentification a échoué et qu'un message d'erreur est affiché
        $this->assertResponseRedirects('/auth');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'Identifiants invalides');
    }

    /**
     * Test A3 : Déconnexion
     * 
     * Scénario :
     * 1. Cliquer sur "Déconnexion"
     * 2. Vérifier retour à la page d'authentification
     * 
     * Résultat attendu : L'utilisateur est déconnecté
     */
    public function testLogout(): void
    {
        // Se connecter d'abord
        $this->client->loginUser($this->testUser);
        
        // Vérifier que l'utilisateur est bien connecté
        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
        
        // Se déconnecter
        $this->client->request('GET', '/logout');
        
        // Vérifier la redirection vers la page d'authentification
        // Note: Symfony gère la déconnexion via le firewall, donc on ne peut pas suivre la redirection directement
        // On vérifie plutôt que l'accès au tableau de bord est maintenant refusé
        $this->client->request('GET', '/dashboard');
        $this->assertResponseRedirects('/auth');
    }

    /**
     * Test A4 : Mot de passe oublié
     * 
     * Scénario :
     * 1. Cliquer sur "Mot de passe oublié"
     * 2. Saisir son email
     * 3. Vérifier réception du mail de réinitialisation
     * 
     * Résultat attendu : Email reçu et lien fonctionnel
     */
    public function testForgotPassword(): void
    {
        // Accéder à la page d'authentification
        $crawler = $this->client->request('GET', '/auth');
        $this->assertResponseIsSuccessful();
        
        // Récupérer le token CSRF pour le formulaire de réinitialisation
        $csrfToken = $this->csrfTokenManager->getToken('reset_password')->getValue();
        
        // Soumettre le formulaire de réinitialisation de mot de passe
        $this->client->request('POST', '/auth/reset-password', [
            'email' => 'test-auth@example.com',
            '_csrf_token' => $csrfToken
        ]);
        
        // Vérifier la redirection avec message de succès
        $this->assertResponseRedirects('/auth');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Instructions envoyées');
        
        // Vérifier que l'email a bien été envoyé
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('mailer.transport_real');
        $this->assertCount(1, $transport->getSentMessages());
        
        $email = $transport->getSentMessages()[0];
        $this->assertEmailHeaderSame($email, 'To', 'test-auth@example.com');
        $this->assertEmailTextBodyContains($email, 'réinitialisation de mot de passe');
        
        // Extraire le token de réinitialisation de l'email
        $emailContent = $email->getHtmlBody();
        preg_match('/\/auth\/reset-password\/([a-zA-Z0-9]+)/', $emailContent, $matches);
        $resetToken = $matches[1] ?? null;
        $this->assertNotNull($resetToken, 'Le token de réinitialisation n\'a pas été trouvé dans l\'email');
        
        // Accéder à la page de réinitialisation avec le token
        $this->client->request('GET', "/auth/reset-password/{$resetToken}");
        $this->assertResponseIsSuccessful();
        
        // Soumettre le formulaire de nouveau mot de passe
        $csrfToken = $this->csrfTokenManager->getToken('reset_password_confirm')->getValue();
        $this->client->request('POST', "/auth/reset-password/{$resetToken}", [
            'password' => 'NewPassword123!',
            'password_confirm' => 'NewPassword123!',
            '_csrf_token' => $csrfToken
        ]);
        
        // Vérifier la redirection vers la page d'authentification avec message de succès
        $this->assertResponseRedirects('/auth');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Mot de passe réinitialisé');
        
        // Vérifier que le nouveau mot de passe fonctionne
        $csrfToken = $this->csrfTokenManager->getToken('authenticate')->getValue();
        $this->client->request('POST', '/auth', [
            'email' => 'test-auth@example.com',
            'password' => 'NewPassword123!',
            '_csrf_token' => $csrfToken
        ]);
        
        $this->assertResponseRedirects('/dashboard');
    }
} 