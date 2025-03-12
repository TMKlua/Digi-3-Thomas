<?php

namespace App\Tests\Functional\Performance;

use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Tests fonctionnels pour la performance et la compatibilité
 * Scénarios couverts : T1, T2, T3
 */
class PerformanceTest extends WebTestCase
{
    private $client;
    private $userRepository;
    private $testUser;
    private $stopwatch;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->stopwatch = new Stopwatch();
        
        // Création d'un utilisateur de test s'il n'existe pas
        $this->testUser = $this->userRepository->findOneBy(['userEmail' => 'test-perf@example.com']);
        
        if (!$this->testUser) {
            $this->testUser = new User();
            $this->testUser->setUserEmail('test-perf@example.com');
            $this->testUser->setPassword('$2y$13$holeQTxBeWN/1WNzrNQYXOZx0hV2VCMmj0BMJEgEzwVvnQjtoKsLe'); // password = 'password123'
            $this->testUser->setUserFirstName('Test');
            $this->testUser->setUserLastName('Performance');
            $this->testUser->setUserRole(UserRole::USER);
            
            $entityManager = static::getContainer()->get('doctrine')->getManager();
            $entityManager->persist($this->testUser);
            $entityManager->flush();
        }
    }

    /**
     * Test T1 : Chargement du tableau de bord
     * 
     * Scénario :
     * 1. Accéder au tableau de bord
     * 2. Mesurer le temps de chargement
     * 
     * Résultat attendu : Inférieur à 3 secondes
     */
    public function testDashboardLoadTime(): void
    {
        // Se connecter en tant qu'utilisateur
        $this->client->loginUser($this->testUser);
        
        // Mesurer le temps de chargement du tableau de bord
        $this->stopwatch->start('dashboard_load');
        
        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
        
        $event = $this->stopwatch->stop('dashboard_load');
        $loadTimeInSeconds = $event->getDuration() / 1000;
        
        // Vérifier que le temps de chargement est inférieur à 3 secondes
        $this->assertLessThan(
            3.0,
            $loadTimeInSeconds,
            sprintf('Le tableau de bord a mis %.2f secondes à charger, ce qui dépasse la limite de 3 secondes', $loadTimeInSeconds)
        );
        
        // Journaliser le temps de chargement pour référence
        echo sprintf('Temps de chargement du tableau de bord : %.2f secondes', $loadTimeInSeconds) . PHP_EOL;
    }

    /**
     * Test T2 : Compatibilité mobile
     * 
     * Scénario :
     * 1. Tester l'interface sur différents appareils
     * 
     * Résultat attendu : Affichage et navigation corrects
     * 
     * Note: Ce test simule différentes tailles d'écran en modifiant les en-têtes HTTP
     * Pour un test complet, il faudrait utiliser un outil comme BrowserStack ou Selenium
     */
    public function testMobileCompatibility(): void
    {
        // Se connecter en tant qu'utilisateur
        $this->client->loginUser($this->testUser);
        
        // Définir les appareils à tester
        $devices = [
            'iPhone' => [
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1',
                'width' => 375,
                'height' => 812
            ],
            'Android' => [
                'user_agent' => 'Mozilla/5.0 (Linux; Android 10; SM-G973F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Mobile Safari/537.36',
                'width' => 360,
                'height' => 740
            ],
            'iPad' => [
                'user_agent' => 'Mozilla/5.0 (iPad; CPU OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1',
                'width' => 768,
                'height' => 1024
            ]
        ];
        
        // Tester chaque appareil
        foreach ($devices as $deviceName => $deviceConfig) {
            // Configurer le client pour simuler l'appareil
            $this->client->setServerParameter('HTTP_USER_AGENT', $deviceConfig['user_agent']);
            
            // Tester les pages principales
            $pages = [
                '/dashboard' => 'Tableau de bord',
                '/projects' => 'Projets',
                '/auth' => 'Authentification'
            ];
            
            foreach ($pages as $url => $expectedContent) {
                $this->client->request('GET', $url);
                
                // Vérifier que la page se charge correctement
                $this->assertResponseIsSuccessful(
                    sprintf('La page %s ne se charge pas correctement sur %s', $url, $deviceName)
                );
                
                // Vérifier que le contenu attendu est présent
                $this->assertSelectorExists(
                    'body',
                    sprintf('La structure HTML de base est absente sur %s pour la page %s', $deviceName, $url)
                );
                
                // Journaliser le test
                echo sprintf('Test de compatibilité mobile réussi pour %s sur la page %s', $deviceName, $url) . PHP_EOL;
            }
        }
    }

    /**
     * Test T3 : Test multi-navigateurs
     * 
     * Scénario :
     * 1. Ouvrir l'application sur Chrome, Firefox, Edge, Safari
     * 
     * Résultat attendu : Aucune erreur d'affichage
     * 
     * Note: Ce test simule différents navigateurs en modifiant les en-têtes HTTP
     * Pour un test complet, il faudrait utiliser un outil comme BrowserStack ou Selenium
     */
    public function testMultiBrowserCompatibility(): void
    {
        // Se connecter en tant qu'utilisateur
        $this->client->loginUser($this->testUser);
        
        // Définir les navigateurs à tester
        $browsers = [
            'Chrome' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Firefox' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Edge' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 Edg/91.0.864.59',
            'Safari' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15'
        ];
        
        // Tester chaque navigateur
        foreach ($browsers as $browserName => $userAgent) {
            // Configurer le client pour simuler le navigateur
            $this->client->setServerParameter('HTTP_USER_AGENT', $userAgent);
            
            // Tester les pages principales
            $pages = [
                '/dashboard' => 'Tableau de bord',
                '/projects' => 'Projets',
                '/profile' => 'Profil'
            ];
            
            foreach ($pages as $url => $expectedContent) {
                $this->client->request('GET', $url);
                
                // Vérifier que la page se charge correctement
                $this->assertResponseIsSuccessful(
                    sprintf('La page %s ne se charge pas correctement sur %s', $url, $browserName)
                );
                
                // Vérifier que le contenu attendu est présent
                $this->assertSelectorExists(
                    'body',
                    sprintf('La structure HTML de base est absente sur %s pour la page %s', $browserName, $url)
                );
                
                // Vérifier l'absence d'erreurs JavaScript (ceci est une simulation, car PHPUnit ne peut pas réellement détecter les erreurs JS)
                $this->assertSelectorNotExists(
                    '.js-error',
                    sprintf('Des erreurs JavaScript ont été détectées sur %s pour la page %s', $browserName, $url)
                );
                
                // Journaliser le test
                echo sprintf('Test de compatibilité navigateur réussi pour %s sur la page %s', $browserName, $url) . PHP_EOL;
            }
        }
    }

    /**
     * Test de performance pour les requêtes AJAX
     * 
     * Ce test vérifie que les requêtes AJAX courantes sont rapides
     */
    public function testAjaxPerformance(): void
    {
        // Se connecter en tant qu'utilisateur
        $this->client->loginUser($this->testUser);
        
        // Définir les endpoints AJAX à tester
        $ajaxEndpoints = [
            '/api/projects' => 'GET',
            '/api/tasks/recent' => 'GET',
            '/api/notifications' => 'GET'
        ];
        
        foreach ($ajaxEndpoints as $endpoint => $method) {
            // Mesurer le temps de réponse
            $this->stopwatch->start('ajax_' . str_replace('/', '_', $endpoint));
            
            $this->client->xmlHttpRequest($method, $endpoint);
            
            $event = $this->stopwatch->stop('ajax_' . str_replace('/', '_', $endpoint));
            $responseTimeInSeconds = $event->getDuration() / 1000;
            
            // Vérifier que le temps de réponse est inférieur à 1 seconde
            $this->assertLessThan(
                1.0,
                $responseTimeInSeconds,
                sprintf('L\'endpoint AJAX %s a mis %.2f secondes à répondre, ce qui dépasse la limite de 1 seconde', $endpoint, $responseTimeInSeconds)
            );
            
            // Vérifier que la réponse est au format JSON
            $this->assertTrue(
                $this->client->getResponse()->headers->contains('Content-Type', 'application/json'),
                sprintf('L\'endpoint AJAX %s ne renvoie pas de JSON', $endpoint)
            );
            
            // Journaliser le temps de réponse
            echo sprintf('Temps de réponse pour l\'endpoint AJAX %s : %.2f secondes', $endpoint, $responseTimeInSeconds) . PHP_EOL;
        }
    }
} 