<?php

namespace App\Tests\Functional\Project;

use App\Entity\Project;
use App\Entity\Tasks;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\ProjectRepository;
use App\Repository\TasksRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests fonctionnels pour la gestion des projets et tâches
 * Scénarios couverts : P1, P2, P3
 */
class ProjectManagementTest extends WebTestCase
{
    private $client;
    private $projectRepository;
    private $taskRepository;
    private $userRepository;
    private $projectManager;
    private $developer;
    private $csrfTokenManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->projectRepository = static::getContainer()->get(ProjectRepository::class);
        $this->taskRepository = static::getContainer()->get(TasksRepository::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->csrfTokenManager = static::getContainer()->get('security.csrf.token_manager');
        
        // Création d'un chef de projet pour les tests
        $this->projectManager = $this->userRepository->findOneBy(['userRole' => UserRole::PROJECT_MANAGER]);
        
        if (!$this->projectManager) {
            $this->projectManager = new User();
            $this->projectManager->setUserEmail('project-manager@test.com');
            $this->projectManager->setPassword('$2y$13$holeQTxBeWN/1WNzrNQYXOZx0hV2VCMmj0BMJEgEzwVvnQjtoKsLe'); // password = 'password123'
            $this->projectManager->setUserFirstName('Project');
            $this->projectManager->setUserLastName('Manager');
            $this->projectManager->setUserRole(UserRole::PROJECT_MANAGER);
            
            $entityManager = static::getContainer()->get('doctrine')->getManager();
            $entityManager->persist($this->projectManager);
            $entityManager->flush();
        }
        
        // Création d'un développeur pour les tests
        $this->developer = $this->userRepository->findOneBy(['userRole' => UserRole::DEVELOPER]);
        
        if (!$this->developer) {
            $this->developer = new User();
            $this->developer->setUserEmail('developer@test.com');
            $this->developer->setPassword('$2y$13$holeQTxBeWN/1WNzrNQYXOZx0hV2VCMmj0BMJEgEzwVvnQjtoKsLe'); // password = 'password123'
            $this->developer->setUserFirstName('Developer');
            $this->developer->setUserLastName('Test');
            $this->developer->setUserRole(UserRole::DEVELOPER);
            
            $entityManager = static::getContainer()->get('doctrine')->getManager();
            $entityManager->persist($this->developer);
            $entityManager->flush();
        }
    }

    /**
     * Test P1 : Création d'un projet
     * 
     * Scénario :
     * 1. Se connecter en tant que Chef de projet
     * 2. Accéder à l'espace de gestion des projets
     * 3. Ajouter un projet avec titre et description
     * 4. Valider
     * 
     * Résultat attendu : Le projet est ajouté et visible
     */
    public function testCreateProject(): void
    {
        // 1. Se connecter en tant que Chef de projet
        $this->client->loginUser($this->projectManager);
        
        // 2. Accéder à l'espace de gestion des projets
        $crawler = $this->client->request('GET', '/projects');
        $this->assertResponseIsSuccessful();
        
        // 3. Ajouter un projet avec titre et description
        $csrfToken = $this->csrfTokenManager->getToken('project_form')->getValue();
        $this->client->request('POST', '/projects/create', [
            'project' => [
                'projectName' => 'Projet de test',
                'projectDescription' => 'Description du projet de test',
                'projectStartDate' => (new \DateTime())->format('Y-m-d'),
                'projectEndDate' => (new \DateTime('+30 days'))->format('Y-m-d'),
                '_token' => $csrfToken
            ]
        ]);
        
        // 4. Valider et vérifier la redirection
        $this->assertResponseRedirects('/projects');
        $this->client->followRedirect();
        
        // Vérifier que le projet est ajouté et visible
        $this->assertSelectorTextContains('.alert-success', 'Projet créé avec succès');
        $this->assertSelectorTextContains('table', 'Projet de test');
        
        // Vérifier en base de données
        $project = $this->projectRepository->findOneBy(['projectName' => 'Projet de test']);
        $this->assertNotNull($project);
        $this->assertEquals('Description du projet de test', $project->getProjectDescription());
    }

    /**
     * Test P2 : Ajout de tâches dans un projet
     * 
     * Scénario :
     * 1. Accéder à un projet existant
     * 2. Ajouter une nouvelle tâche avec priorité et échéance
     * 3. Valider
     * 
     * Résultat attendu : La tâche est ajoutée au projet
     */
    public function testAddTaskToProject(): void
    {
        // Créer un projet pour le test si nécessaire
        $project = $this->projectRepository->findOneBy(['projectName' => 'Projet de test']);
        
        if (!$project) {
            $project = new Project();
            $project->setProjectName('Projet de test');
            $project->setProjectDescription('Description du projet de test');
            $project->setProjectManager($this->projectManager);
            
            $entityManager = static::getContainer()->get('doctrine')->getManager();
            $entityManager->persist($project);
            $entityManager->flush();
        }
        
        // 1. Se connecter en tant que Chef de projet et accéder au projet
        $this->client->loginUser($this->projectManager);
        $projectId = $project->getId();
        $crawler = $this->client->request('GET', "/projects/{$projectId}");
        $this->assertResponseIsSuccessful();
        
        // 2. Ajouter une nouvelle tâche avec priorité et échéance
        $csrfToken = $this->csrfTokenManager->getToken('task_form')->getValue();
        $this->client->request('POST', "/projects/{$projectId}/tasks/create", [
            'task' => [
                'taskName' => 'Tâche de test',
                'taskDescription' => 'Description de la tâche de test',
                'taskPriority' => 'HIGH',
                'taskStatus' => 'TODO',
                'taskDueDate' => (new \DateTime('+7 days'))->format('Y-m-d'),
                '_token' => $csrfToken
            ]
        ]);
        
        // 3. Valider et vérifier la redirection
        $this->assertResponseRedirects("/projects/{$projectId}");
        $this->client->followRedirect();
        
        // Vérifier que la tâche est ajoutée au projet
        $this->assertSelectorTextContains('.alert-success', 'Tâche créée avec succès');
        $this->assertSelectorTextContains('.tasks-list', 'Tâche de test');
        
        // Vérifier en base de données
        $task = $this->taskRepository->findOneBy(['taskName' => 'Tâche de test']);
        $this->assertNotNull($task);
        $this->assertEquals('Description de la tâche de test', $task->getTaskDescription());
        $this->assertEquals($project->getId(), $task->getProject()->getId());
    }

    /**
     * Test P3 : Assignation d'une tâche à un utilisateur
     * 
     * Scénario :
     * 1. Sélectionner une tâche
     * 2. Assigner un utilisateur
     * 3. Valider
     * 
     * Résultat attendu : L'utilisateur est notifié de la tâche
     */
    public function testAssignTaskToUser(): void
    {
        // Récupérer ou créer un projet et une tâche pour le test
        $project = $this->projectRepository->findOneBy(['projectName' => 'Projet de test']);
        
        if (!$project) {
            $project = new Project();
            $project->setProjectName('Projet de test');
            $project->setProjectDescription('Description du projet de test');
            $project->setProjectManager($this->projectManager);
            
            $entityManager = static::getContainer()->get('doctrine')->getManager();
            $entityManager->persist($project);
            $entityManager->flush();
        }
        
        // Créer une tâche
        $task = new Tasks();
        $task->setTaskName('Tâche de test');
        $task->setTaskDescription('Description de la tâche de test');
        $task->setTaskPriority(\App\Enum\TaskPriority::HIGH);
        $task->setTaskStatus(\App\Enum\TaskStatus::NEW);
        $task->setTaskTargetDate(new \DateTime('+7 days'));
        $task->setTaskProject($project);
        
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($task);
        $entityManager->flush();
        
        // 1. Se connecter en tant que Chef de projet et sélectionner une tâche
        $this->client->loginUser($this->projectManager);
        $projectId = $project->getId();
        $taskId = $task->getId();
        $crawler = $this->client->request('GET', "/projects/{$projectId}/tasks/{$taskId}");
        $this->assertResponseIsSuccessful();
        
        // 2. Assigner un utilisateur à la tâche
        $csrfToken = $this->csrfTokenManager->getToken('task_assign_form')->getValue();
        $this->client->request('POST', "/projects/{$projectId}/tasks/{$taskId}/assign", [
            'assignTask' => [
                'userId' => $this->developer->getId(),
                '_token' => $csrfToken
            ]
        ]);
        
        // 3. Valider et vérifier la redirection
        $this->assertResponseRedirects("/projects/{$projectId}/tasks/{$taskId}");
        $this->client->followRedirect();
        
        // Vérifier que l'utilisateur est assigné à la tâche
        $this->assertSelectorTextContains('.alert-success', 'Tâche assignée avec succès');
        $this->assertSelectorTextContains('.task-details', $this->developer->getUserFirstName());
        
        // Vérifier en base de données
        $updatedTask = $this->taskRepository->find($taskId);
        $this->assertNotNull($updatedTask->getAssignedTo());
        $this->assertEquals($this->developer->getId(), $updatedTask->getAssignedTo()->getId());
        
        // Vérifier que l'utilisateur a reçu une notification (si applicable)
        // Note: Cette partie dépend de l'implémentation des notifications dans votre application
        // $notificationRepository = static::getContainer()->get(NotificationRepository::class);
        // $notification = $notificationRepository->findOneBy(['user' => $this->developer, 'task' => $task]);
        // $this->assertNotNull($notification);
    }
} 