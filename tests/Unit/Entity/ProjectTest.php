<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Project;
use App\Entity\Tasks;
use App\Entity\User;
use App\Entity\Customers;
use App\Enum\ProjectStatus;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\Collection;

/**
 * Tests unitaires pour l'entité Project
 */
class ProjectTest extends TestCase
{
    private Project $project;
    private User $user;
    private Customers $customer;

    protected function setUp(): void
    {
        $this->project = new Project();
        $this->user = new User();
        $this->user->setUserEmail('project-manager@example.com');
        $this->user->setUserFirstName('Project');
        $this->user->setUserLastName('Manager');
        
        $this->customer = new Customers();
        $this->customer->setCustomerName('Client Test');
    }

    /**
     * Test des getters et setters de l'entité Project
     */
    public function testGettersAndSetters(): void
    {
        // Test name
        $name = 'Projet de test';
        $this->project->setProjectName($name);
        $this->assertEquals($name, $this->project->getProjectName());

        // Test description
        $description = 'Description du projet de test';
        $this->project->setProjectDescription($description);
        $this->assertEquals($description, $this->project->getProjectDescription());

        // Test status
        $status = ProjectStatus::IN_PROGRESS;
        $this->project->setProjectStatus($status);
        $this->assertEquals($status, $this->project->getProjectStatus());

        // Test customer
        $this->project->setProjectCustomer($this->customer);
        $this->assertSame($this->customer, $this->project->getProjectCustomer());

        // Test project manager
        $this->project->setProjectManager($this->user);
        $this->assertSame($this->user, $this->project->getProjectManager());
    }

    /**
     * Test des valeurs par défaut de l'entité Project
     */
    public function testDefaultValues(): void
    {
        // Test default status
        $this->assertEquals(ProjectStatus::NEW, $this->project->getProjectStatus());

        // Test default tasks collection
        $this->assertInstanceOf(Collection::class, $this->project->getTasks());
        $this->assertCount(0, $this->project->getTasks());
    }

    /**
     * Test de l'ajout et de la suppression de tâches
     */
    public function testTasksManagement(): void
    {
        // Créer une tâche de test
        $task = new Tasks();
        $task->setTaskName('Tâche de test');
        
        // Ajouter la tâche au projet
        $this->project->addTask($task);
        
        // Vérifier que la tâche a été ajoutée
        $this->assertCount(1, $this->project->getTasks());
        $this->assertTrue($this->project->getTasks()->contains($task));
        $this->assertSame($this->project, $task->getTaskProject());
        
        // Supprimer la tâche du projet
        $this->project->removeTask($task);
        
        // Vérifier que la tâche a été supprimée
        $this->assertCount(0, $this->project->getTasks());
        $this->assertFalse($this->project->getTasks()->contains($task));
    }

    /**
     * Test de la méthode __toString
     */
    public function testToString(): void
    {
        // Note: Cette méthode n'existe pas encore dans l'entité Project
        $this->markTestSkipped('La méthode __toString n\'est pas encore implémentée dans l\'entité Project');
        
        /*
        $name = 'Projet de test';
        $this->project->setProjectName($name);
        $this->assertEquals($name, (string) $this->project);
        */
    }

    /**
     * Test du calcul de la durée du projet
     */
    public function testProjectDuration(): void
    {
        // Note: Les méthodes setProjectStartDate et setProjectEndDate ne sont pas disponibles
        // dans l'entité Project actuelle. Ce test est donc ignoré.
        $this->markTestSkipped('Les méthodes setProjectStartDate et setProjectEndDate ne sont pas disponibles dans l\'entité Project actuelle');
        
        /*
        // Définir les dates du projet
        $startDate = new \DateTime('2023-01-01');
        $endDate = new \DateTime('2023-01-31');
        
        $this->project->setProjectStartDate($startDate);
        $this->project->setProjectEndDate($endDate);
        
        // Calculer la durée en jours
        // Note: Cette méthode n'existe pas encore dans l'entité Project, elle devrait être implémentée
        // $duration = $this->project->getDurationInDays();
        // $this->assertEquals(30, $duration);
        
        // Vérifier que la durée est correcte (30 jours) en calculant manuellement
        $interval = $startDate->diff($endDate);
        $this->assertEquals(30, $interval->days);
        */
    }

    /**
     * Test du calcul du statut du projet
     */
    public function testProjectStatus(): void
    {
        // Note: Les méthodes setProjectStartDate et setProjectEndDate ne sont pas disponibles
        // dans l'entité Project actuelle. Ce test est donc ignoré.
        $this->markTestSkipped('Les méthodes setProjectStartDate et setProjectEndDate ne sont pas disponibles dans l\'entité Project actuelle');
        
        /*
        // Projet à venir
        $this->project->setProjectStartDate(new \DateTime('+1 day'));
        $this->project->setProjectEndDate(new \DateTime('+30 days'));
        $this->assertEquals('À venir', $this->project->getStatus());
        
        // Projet en cours
        $this->project->setProjectStartDate(new \DateTime('-5 days'));
        $this->project->setProjectEndDate(new \DateTime('+25 days'));
        $this->assertEquals('En cours', $this->project->getStatus());
        
        // Projet terminé
        $this->project->setProjectStartDate(new \DateTime('-30 days'));
        $this->project->setProjectEndDate(new \DateTime('-1 day'));
        $this->assertEquals('Terminé', $this->project->getStatus());
        */
    }
} 