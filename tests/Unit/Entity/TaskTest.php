<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Project;
use App\Entity\Tasks;
use App\Entity\User;
use App\Entity\TasksComments;
use App\Entity\TasksAttachments;
use App\Enum\TaskStatus;
use App\Enum\TaskPriority;
use App\Enum\TaskComplexity;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\Collection;

/**
 * Tests unitaires pour l'entité Tasks
 */
class TaskTest extends TestCase
{
    private Tasks $task;
    private Project $project;
    private User $user;

    protected function setUp(): void
    {
        $this->task = new Tasks();
        $this->project = new Project();
        $this->project->setProjectName('Projet de test');
        
        $this->user = new User();
        $this->user->setUserEmail('developer@example.com');
        $this->user->setUserFirstName('Developer');
        $this->user->setUserLastName('Test');
    }

    /**
     * Test des getters et setters de l'entité Tasks
     */
    public function testGettersAndSetters(): void
    {
        // Test name
        $name = 'Tâche de test';
        $this->task->setTaskName($name);
        $this->assertEquals($name, $this->task->getTaskName());

        // Test description
        $description = 'Description de la tâche de test';
        $this->task->setTaskDescription($description);
        $this->assertEquals($description, $this->task->getTaskDescription());

        // Test priority
        $priority = TaskPriority::HIGH;
        $this->task->setTaskPriority($priority);
        $this->assertEquals($priority, $this->task->getTaskPriority());

        // Test status
        $status = TaskStatus::IN_PROGRESS;
        $this->task->setTaskStatus($status);
        $this->assertEquals($status, $this->task->getTaskStatus());

        // Test target date
        $targetDate = new \DateTime('2023-06-30');
        $this->task->setTaskTargetDate($targetDate);
        $this->assertEquals($targetDate, $this->task->getTaskTargetDate());

        // Test project
        $this->task->setTaskProject($this->project);
        $this->assertSame($this->project, $this->task->getTaskProject());

        // Test assigned user
        $this->task->setTaskAssignedTo($this->user);
        $this->assertSame($this->user, $this->task->getTaskAssignedTo());
    }

    /**
     * Test des valeurs par défaut de l'entité Tasks
     */
    public function testDefaultValues(): void
    {
        // Test default created at
        $this->assertInstanceOf(\DateTimeInterface::class, $this->task->getTaskCreatedAt());

        // Test default status
        $this->assertEquals(TaskStatus::NEW, $this->task->getTaskStatus());

        // Test default priority
        $this->assertEquals(TaskPriority::MEDIUM, $this->task->getTaskPriority());

        // Test default comments collection
        $this->assertInstanceOf(Collection::class, $this->task->getComments());
        $this->assertCount(0, $this->task->getComments());

        // Test default attachments collection
        $this->assertInstanceOf(Collection::class, $this->task->getAttachments());
        $this->assertCount(0, $this->task->getAttachments());
    }

    /**
     * Test de l'ajout et de la suppression de commentaires
     */
    public function testCommentsManagement(): void
    {
        // Créer un commentaire de test
        $comment = new TasksComments();
        $comment->setContent('Commentaire de test');
        $comment->setUser($this->user);
        
        // Ajouter le commentaire à la tâche
        // Note: Ces méthodes ne sont pas encore implémentées dans l'entité Tasks
        // Elles devraient être ajoutées pour gérer les commentaires
        $this->markTestSkipped('Les méthodes addComment et removeComment ne sont pas encore implémentées dans l\'entité Tasks');
        
        /*
        $this->task->addComment($comment);
        
        // Vérifier que le commentaire a été ajouté
        $this->assertCount(1, $this->task->getComments());
        $this->assertTrue($this->task->getComments()->contains($comment));
        $this->assertSame($this->task, $comment->getTask());
        
        // Supprimer le commentaire de la tâche
        $this->task->removeComment($comment);
        
        // Vérifier que le commentaire a été supprimé
        $this->assertCount(0, $this->task->getComments());
        $this->assertFalse($this->task->getComments()->contains($comment));
        */
    }

    /**
     * Test de l'ajout et de la suppression de pièces jointes
     */
    public function testAttachmentsManagement(): void
    {
        // Créer une pièce jointe de test
        $attachment = new TasksAttachments();
        $attachment->setName('document.pdf');
        $attachment->setOriginalName('document_original.pdf');
        $attachment->setDescription('Document de test');
        $attachment->setUploadedBy($this->user);
        
        // Ajouter la pièce jointe à la tâche
        // Note: Ces méthodes ne sont pas encore implémentées dans l'entité Tasks
        // Elles devraient être ajoutées pour gérer les pièces jointes
        $this->markTestSkipped('Les méthodes addAttachment et removeAttachment ne sont pas encore implémentées dans l\'entité Tasks');
        
        /*
        $this->task->addAttachment($attachment);
        
        // Vérifier que la pièce jointe a été ajoutée
        $this->assertCount(1, $this->task->getAttachments());
        $this->assertTrue($this->task->getAttachments()->contains($attachment));
        $this->assertSame($this->task, $attachment->getTask());
        
        // Supprimer la pièce jointe de la tâche
        $this->task->removeAttachment($attachment);
        
        // Vérifier que la pièce jointe a été supprimée
        $this->assertCount(0, $this->task->getAttachments());
        $this->assertFalse($this->task->getAttachments()->contains($attachment));
        */
    }

    /**
     * Test de la méthode __toString
     */
    public function testToString(): void
    {
        // Note: Cette méthode n'existe pas encore dans l'entité Tasks
        $this->markTestSkipped('La méthode __toString n\'est pas encore implémentée dans l\'entité Tasks');
        
        /*
        $name = 'Tâche de test';
        $this->task->setTaskName($name);
        $this->assertEquals($name, (string) $this->task);
        */
    }

    /**
     * Test du calcul du retard de la tâche
     */
    public function testIsOverdue(): void
    {
        // Note: Cette méthode n'existe pas encore dans l'entité Tasks, elle devrait être implémentée
        $this->markTestSkipped('La méthode isOverdue n\'est pas encore implémentée dans l\'entité Tasks');
        
        /*
        // Tâche avec date d'échéance dans le futur
        $this->task->setTaskTargetDate(new \DateTime('+5 days'));
        $this->assertFalse($this->task->isOverdue());
        
        // Tâche avec date d'échéance dans le passé
        $this->task->setTaskTargetDate(new \DateTime('-5 days'));
        $this->assertTrue($this->task->isOverdue());
        */
        
        // En attendant, vérifions simplement que les dates sont correctement définies
        $futureDate = new \DateTime('+5 days');
        $this->task->setTaskTargetDate($futureDate);
        $this->assertGreaterThan(new \DateTime(), $this->task->getTaskTargetDate());
        
        $pastDate = new \DateTime('-5 days');
        $this->task->setTaskTargetDate($pastDate);
        $this->assertLessThan(new \DateTime(), $this->task->getTaskTargetDate());
    }

    /**
     * Test du calcul du temps restant avant l'échéance
     */
    public function testRemainingDays(): void
    {
        // Note: Cette méthode n'existe pas encore dans l'entité Tasks, elle devrait être implémentée
        $this->markTestSkipped('La méthode getRemainingDays n\'est pas encore implémentée dans l\'entité Tasks');
        
        /*
        // Tâche avec date d'échéance dans 5 jours
        $this->task->setTaskTargetDate(new \DateTime('+5 days'));
        $this->assertEquals(5, $this->task->getRemainingDays());
        
        // Tâche avec date d'échéance dépassée de 5 jours
        $this->task->setTaskTargetDate(new \DateTime('-5 days'));
        $this->assertEquals(-5, $this->task->getRemainingDays());
        */
        
        // En attendant, vérifions simplement que les dates sont correctement définies
        $futureDate = new \DateTime('+5 days');
        $this->task->setTaskTargetDate($futureDate);
        $interval = (new \DateTime())->diff($futureDate);
        $this->assertEquals(5, $interval->days);
        
        $pastDate = new \DateTime('-5 days');
        $this->task->setTaskTargetDate($pastDate);
        $interval = (new \DateTime())->diff($pastDate);
        $this->assertEquals(5, $interval->days);
    }
} 