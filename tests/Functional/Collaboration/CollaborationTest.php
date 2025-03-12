<?php

namespace App\Tests\Functional\Collaboration;

use App\Entity\Project;
use App\Entity\Tasks;
use App\Entity\TasksComments;
use App\Entity\TasksAttachments;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\ProjectRepository;
use App\Repository\TasksRepository;
use App\Repository\TasksCommentsRepository;
use App\Repository\TasksAttachmentsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests fonctionnels pour la collaboration et la communication
 * Scénarios couverts : C1, C2
 */
class CollaborationTest extends WebTestCase
{
    private $client;
    private $projectRepository;
    private $taskRepository;
    private $taskCommentRepository;
    private $taskAttachmentRepository;
    private $userRepository;
    private $developer;
    private $projectManager;
    private $csrfTokenManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->projectRepository = static::getContainer()->get(ProjectRepository::class);
        $this->taskRepository = static::getContainer()->get(TasksRepository::class);
        $this->taskCommentRepository = static::getContainer()->get(TasksCommentsRepository::class);
        $this->taskAttachmentRepository = static::getContainer()->get(TasksAttachmentsRepository::class);
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
        
        // Création d'un projet et d'une tâche pour les tests
        $project = $this->projectRepository->findOneBy(['projectName' => 'Projet de collaboration']);
        
        if (!$project) {
            $project = new Project();
            $project->setProjectName('Projet de collaboration');
            $project->setProjectDescription('Description du projet de collaboration');
            $project->setProjectManager($this->projectManager);
            
            $entityManager = static::getContainer()->get('doctrine')->getManager();
            $entityManager->persist($project);
            $entityManager->flush();
        }
        
        $task = $this->taskRepository->findOneBy(['taskName' => 'Tâche de collaboration']);
        
        if (!$task) {
            $task = new Tasks();
            $task->setTaskName('Tâche de collaboration');
            $task->setTaskDescription('Description de la tâche de collaboration');
            $task->setTaskPriority(\App\Enum\TaskPriority::MEDIUM);
            $task->setTaskStatus(\App\Enum\TaskStatus::IN_PROGRESS);
            $task->setTaskTargetDate(new \DateTime('+7 days'));
            $task->setTaskProject($project);
            $task->setTaskAssignedTo($this->developer);
            
            $entityManager = static::getContainer()->get('doctrine')->getManager();
            $entityManager->persist($task);
            $entityManager->flush();
        }
    }

    /**
     * Test C1 : Ajout d'un commentaire sur une tâche
     * 
     * Scénario :
     * 1. Accéder à une tâche
     * 2. Ajouter un commentaire
     * 3. Valider
     * 
     * Résultat attendu : Le commentaire est visible
     */
    public function testAddCommentToTask(): void
    {
        // Récupérer le projet et la tâche pour le test
        $project = $this->projectRepository->findOneBy(['projectName' => 'Projet de collaboration']);
        $task = $this->taskRepository->findOneBy(['taskName' => 'Tâche de collaboration', 'taskProject' => $project]);
        
        // 1. Se connecter en tant que développeur et accéder à la tâche
        $this->client->loginUser($this->developer);
        $projectId = $project->getId();
        $taskId = $task->getId();
        $crawler = $this->client->request('GET', "/projects/{$projectId}/tasks/{$taskId}");
        $this->assertResponseIsSuccessful();
        
        // 2. Ajouter un commentaire
        $commentContent = 'Ceci est un commentaire de test pour la tâche de collaboration.';
        $csrfToken = $this->csrfTokenManager->getToken('comment_form')->getValue();
        $this->client->request('POST', "/projects/{$projectId}/tasks/{$taskId}/comments/add", [
            'comment' => [
                'content' => $commentContent,
                '_token' => $csrfToken
            ]
        ]);
        
        // 3. Valider et vérifier la redirection
        $this->assertResponseRedirects("/projects/{$projectId}/tasks/{$taskId}");
        $this->client->followRedirect();
        
        // Vérifier que le commentaire est visible
        $this->assertSelectorTextContains('.alert-success', 'Commentaire ajouté avec succès');
        $this->assertSelectorTextContains('.comments-section', $commentContent);
        
        // Vérifier en base de données
        $comment = $this->taskCommentRepository->findOneBy(['content' => $commentContent]);
        $this->assertNotNull($comment);
        $this->assertEquals($task->getId(), $comment->getTask()->getId());
        $this->assertEquals($this->developer->getId(), $comment->getAuthor()->getId());
    }

    /**
     * Test C2 : Ajout d'une pièce jointe
     * 
     * Scénario :
     * 1. Accéder à une tâche
     * 2. Ajouter une pièce jointe
     * 3. Valider
     * 
     * Résultat attendu : La pièce jointe est attachée
     */
    public function testAddAttachmentToTask(): void
    {
        // Récupérer le projet et la tâche pour le test
        $project = $this->projectRepository->findOneBy(['projectName' => 'Projet de collaboration']);
        $task = $this->taskRepository->findOneBy(['taskName' => 'Tâche de collaboration', 'taskProject' => $project]);
        
        // 1. Se connecter en tant que développeur et accéder à la tâche
        $this->client->loginUser($this->developer);
        $projectId = $project->getId();
        $taskId = $task->getId();
        $crawler = $this->client->request('GET', "/projects/{$projectId}/tasks/{$taskId}");
        $this->assertResponseIsSuccessful();
        
        // 2. Créer un fichier temporaire pour le test
        $tempFile = tempnam(sys_get_temp_dir(), 'test_attachment');
        file_put_contents($tempFile, 'Contenu du fichier de test');
        $uploadedFile = new UploadedFile(
            $tempFile,
            'document_test.txt',
            'text/plain',
            null,
            true
        );
        
        // Ajouter une pièce jointe
        $csrfToken = $this->csrfTokenManager->getToken('attachment_form')->getValue();
        $this->client->request(
            'POST',
            "/projects/{$projectId}/tasks/{$taskId}/attachments/add",
            [
                'attachment' => [
                    'description' => 'Document de test pour la tâche',
                    '_token' => $csrfToken
                ]
            ],
            [
                'attachment' => [
                    'file' => $uploadedFile
                ]
            ]
        );
        
        // 3. Valider et vérifier la redirection
        $this->assertResponseRedirects("/projects/{$projectId}/tasks/{$taskId}");
        $this->client->followRedirect();
        
        // Vérifier que la pièce jointe est visible
        $this->assertSelectorTextContains('.alert-success', 'Pièce jointe ajoutée avec succès');
        $this->assertSelectorTextContains('.attachments-section', 'document_test.txt');
        
        // Vérifier en base de données
        $attachment = $this->taskAttachmentRepository->findOneBy(['description' => 'Document de test pour la tâche']);
        $this->assertNotNull($attachment);
        $this->assertEquals($task->getId(), $attachment->getTask()->getId());
        $this->assertEquals($this->developer->getId(), $attachment->getUploadedBy()->getId());
        $this->assertStringContainsString('document_test.txt', $attachment->getFilename());
        
        // Nettoyer le fichier temporaire
        @unlink($tempFile);
    }
} 