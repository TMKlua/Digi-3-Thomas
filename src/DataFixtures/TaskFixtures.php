<?php

namespace App\DataFixtures;

use App\Entity\Tasks;
use App\Entity\User;
use App\Entity\Project;
use App\Enum\TaskStatus;
use App\Enum\TaskPriority;
use App\Enum\TaskComplexity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class TaskFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer les utilisateurs via les références
        $admin = $this->getReference(AppFixtures::ADMIN_USER_REFERENCE, User::class);
        $leadDev = $this->getReference(AppFixtures::LEAD_DEV_USER_REFERENCE, User::class);
        $developer = $this->getReference(AppFixtures::DEV_USER_REFERENCE, User::class);
        $developer2 = $this->getReference('dev-user-2', User::class);
        $developer3 = $this->getReference('dev-user-3', User::class);
        
        // Récupérer les projets via les références
        $websiteProject = $this->getReference(ProjectFixtures::PROJECT_WEBSITE_REFERENCE, Project::class);
        $mobileProject = $this->getReference(ProjectFixtures::PROJECT_MOBILE_REFERENCE, Project::class);
        $erpProject = $this->getReference(ProjectFixtures::PROJECT_ERP_REFERENCE, Project::class);
        $lmsProject = $this->getReference(ProjectFixtures::PROJECT_LMS_REFERENCE, Project::class);
        $crmProject = $this->getReference(ProjectFixtures::PROJECT_CRM_REFERENCE, Project::class);
        $ecommerceProject = $this->getReference(ProjectFixtures::PROJECT_ECOMMERCE_REFERENCE, Project::class);
        
        // Tâches pour le projet "Refonte du site web"
        $this->createTasks($manager, $websiteProject, [
            [
                'name' => 'Analyse des besoins',
                'description' => 'Recueillir et analyser les besoins du client pour la refonte du site web.',
                'status' => TaskStatus::COMPLETED,
                'priority' => TaskPriority::HIGH,
                'complexity' => TaskComplexity::MODERATE,
                'assignedTo' => $admin,
                'startDate' => new \DateTime('-2 months'),
                'targetDate' => new \DateTime('-1 month 15 days'),
                'endDate' => new \DateTime('-1 month 20 days')
            ],
            [
                'name' => 'Maquettes graphiques',
                'description' => 'Création des maquettes graphiques pour les principales pages du site.',
                'status' => TaskStatus::COMPLETED,
                'priority' => TaskPriority::MEDIUM,
                'complexity' => TaskComplexity::MODERATE,
                'assignedTo' => $leadDev,
                'startDate' => new \DateTime('-1 month 15 days'),
                'targetDate' => new \DateTime('-1 month'),
                'endDate' => new \DateTime('-1 month 2 days')
            ],
            [
                'name' => 'Développement frontend',
                'description' => 'Intégration HTML/CSS des maquettes validées.',
                'status' => TaskStatus::IN_PROGRESS,
                'priority' => TaskPriority::MEDIUM,
                'complexity' => TaskComplexity::MODERATE,
                'assignedTo' => $developer,
                'startDate' => new \DateTime('-3 weeks'),
                'targetDate' => new \DateTime('+1 week'),
                'endDate' => null
            ],
            [
                'name' => 'Développement backend',
                'description' => 'Mise en place du CMS et développement des fonctionnalités spécifiques.',
                'status' => TaskStatus::NEW,
                'priority' => TaskPriority::HIGH,
                'complexity' => TaskComplexity::COMPLEX,
                'assignedTo' => $leadDev,
                'startDate' => null,
                'targetDate' => new \DateTime('+2 months'),
                'endDate' => null
            ],
            [
                'name' => 'Optimisation SEO',
                'description' => 'Mise en place des bonnes pratiques SEO et optimisation du contenu.',
                'status' => TaskStatus::NEW,
                'priority' => TaskPriority::MEDIUM,
                'complexity' => TaskComplexity::MODERATE,
                'assignedTo' => $developer2,
                'startDate' => null,
                'targetDate' => new \DateTime('+2 months 15 days'),
                'endDate' => null
            ]
        ]);
        
        // Tâches pour le projet "Application mobile e-commerce"
        $this->createTasks($manager, $mobileProject, [
            [
                'name' => 'Spécifications fonctionnelles',
                'description' => 'Rédaction des spécifications fonctionnelles détaillées de l\'application.',
                'status' => TaskStatus::IN_PROGRESS,
                'priority' => TaskPriority::HIGH,
                'complexity' => TaskComplexity::MODERATE,
                'assignedTo' => $admin,
                'startDate' => new \DateTime('-1 week'),
                'targetDate' => new \DateTime('+2 weeks'),
                'endDate' => null
            ],
            [
                'name' => 'Architecture technique',
                'description' => 'Définition de l\'architecture technique de l\'application.',
                'status' => TaskStatus::NEW,
                'priority' => TaskPriority::HIGH,
                'complexity' => TaskComplexity::COMPLEX,
                'assignedTo' => $leadDev,
                'startDate' => null,
                'targetDate' => new \DateTime('+1 month'),
                'endDate' => null
            ],
            [
                'name' => 'Maquettes UI/UX',
                'description' => 'Création des maquettes d\'interface utilisateur pour l\'application mobile.',
                'status' => TaskStatus::NEW,
                'priority' => TaskPriority::MEDIUM,
                'complexity' => TaskComplexity::MODERATE,
                'assignedTo' => $developer2,
                'startDate' => null,
                'targetDate' => new \DateTime('+1 month 15 days'),
                'endDate' => null
            ]
        ]);
        
        // Tâches pour le projet "Système de gestion interne (ERP)"
        $this->createTasks($manager, $erpProject, [
            [
                'name' => 'Développement des modules RH',
                'description' => 'Développement des modules de gestion des ressources humaines.',
                'status' => TaskStatus::COMPLETED,
                'priority' => TaskPriority::MEDIUM,
                'complexity' => TaskComplexity::MODERATE,
                'assignedTo' => $developer,
                'startDate' => new \DateTime('-7 months'),
                'targetDate' => new \DateTime('-5 months'),
                'endDate' => new \DateTime('-5 months 10 days')
            ],
            [
                'name' => 'Développement des modules comptables',
                'description' => 'Développement des modules de comptabilité et facturation.',
                'status' => TaskStatus::COMPLETED,
                'priority' => TaskPriority::HIGH,
                'complexity' => TaskComplexity::COMPLEX,
                'assignedTo' => $leadDev,
                'startDate' => new \DateTime('-6 months'),
                'targetDate' => new \DateTime('-3 months'),
                'endDate' => new \DateTime('-3 months 15 days')
            ],
            [
                'name' => 'Tests et recette',
                'description' => 'Phase de tests et recette de l\'application.',
                'status' => TaskStatus::COMPLETED,
                'priority' => TaskPriority::HIGH,
                'complexity' => TaskComplexity::MODERATE,
                'assignedTo' => $admin,
                'startDate' => new \DateTime('-3 months'),
                'targetDate' => new \DateTime('-1 month 15 days'),
                'endDate' => new \DateTime('-1 month 5 days')
            ],
            [
                'name' => 'Formation des utilisateurs',
                'description' => 'Sessions de formation pour les utilisateurs finaux.',
                'status' => TaskStatus::COMPLETED,
                'priority' => TaskPriority::MEDIUM,
                'complexity' => TaskComplexity::SIMPLE,
                'assignedTo' => $developer3,
                'startDate' => new \DateTime('-2 months'),
                'targetDate' => new \DateTime('-1 month'),
                'endDate' => new \DateTime('-25 days')
            ]
        ]);
        
        // Tâches pour le projet "Plateforme de formation en ligne (LMS)"
        $this->createTasks($manager, $lmsProject, [
            [
                'name' => 'Conception pédagogique',
                'description' => 'Définition de l\'approche pédagogique et des parcours de formation.',
                'status' => TaskStatus::COMPLETED,
                'priority' => TaskPriority::HIGH,
                'complexity' => TaskComplexity::MODERATE,
                'assignedTo' => $admin,
                'startDate' => new \DateTime('-3 months'),
                'targetDate' => new \DateTime('-2 months'),
                'endDate' => new \DateTime('-2 months 5 days')
            ],
            [
                'name' => 'Développement de la plateforme',
                'description' => 'Mise en place de la plateforme LMS et personnalisation.',
                'status' => TaskStatus::IN_PROGRESS,
                'priority' => TaskPriority::HIGH,
                'complexity' => TaskComplexity::COMPLEX,
                'assignedTo' => $leadDev,
                'startDate' => new \DateTime('-2 months'),
                'targetDate' => new \DateTime('+1 month'),
                'endDate' => null
            ],
            [
                'name' => 'Création des contenus',
                'description' => 'Production des contenus pédagogiques (vidéos, quiz, etc.).',
                'status' => TaskStatus::NEW,
                'priority' => TaskPriority::MEDIUM,
                'complexity' => TaskComplexity::MODERATE,
                'assignedTo' => $developer,
                'startDate' => null,
                'targetDate' => new \DateTime('+2 months'),
                'endDate' => null
            ],
            [
                'name' => 'Intégration des médias',
                'description' => 'Intégration des vidéos, images et documents dans la plateforme.',
                'status' => TaskStatus::NEW,
                'priority' => TaskPriority::MEDIUM,
                'complexity' => TaskComplexity::SIMPLE,
                'assignedTo' => $developer2,
                'startDate' => null,
                'targetDate' => new \DateTime('+2 months 15 days'),
                'endDate' => null
            ]
        ]);
        
        // Tâches pour le projet "CRM personnalisé"
        $this->createTasks($manager, $crmProject, [
            [
                'name' => 'Analyse des processus métier',
                'description' => 'Analyse des processus de vente et de relation client existants.',
                'status' => TaskStatus::NEW,
                'priority' => TaskPriority::HIGH,
                'complexity' => TaskComplexity::MODERATE,
                'assignedTo' => $admin,
                'startDate' => null,
                'targetDate' => new \DateTime('+2 weeks'),
                'endDate' => null
            ],
            [
                'name' => 'Conception de la base de données',
                'description' => 'Conception du modèle de données pour le CRM.',
                'status' => TaskStatus::NEW,
                'priority' => TaskPriority::HIGH,
                'complexity' => TaskComplexity::COMPLEX,
                'assignedTo' => $leadDev,
                'startDate' => null,
                'targetDate' => new \DateTime('+1 month'),
                'endDate' => null
            ]
        ]);
        
        // Tâches pour le projet "Plateforme e-commerce"
        $this->createTasks($manager, $ecommerceProject, [
            [
                'name' => 'Configuration du catalogue',
                'description' => 'Mise en place de la structure du catalogue de produits.',
                'status' => TaskStatus::IN_PROGRESS,
                'priority' => TaskPriority::HIGH,
                'complexity' => TaskComplexity::MODERATE,
                'assignedTo' => $developer3,
                'startDate' => new \DateTime('-2 weeks'),
                'targetDate' => new \DateTime('+1 week'),
                'endDate' => null
            ],
            [
                'name' => 'Intégration des paiements',
                'description' => 'Intégration des différentes méthodes de paiement.',
                'status' => TaskStatus::NEW,
                'priority' => TaskPriority::HIGH,
                'complexity' => TaskComplexity::COMPLEX,
                'assignedTo' => $leadDev,
                'startDate' => null,
                'targetDate' => new \DateTime('+1 month'),
                'endDate' => null
            ],
            [
                'name' => 'Optimisation mobile',
                'description' => 'Optimisation de l\'interface pour les appareils mobiles.',
                'status' => TaskStatus::NEW,
                'priority' => TaskPriority::MEDIUM,
                'complexity' => TaskComplexity::MODERATE,
                'assignedTo' => $developer2,
                'startDate' => null,
                'targetDate' => new \DateTime('+2 months'),
                'endDate' => null
            ]
        ]);
        
        $manager->flush();
    }
    
    private function createTasks(ObjectManager $manager, Project $project, array $tasksData): void
    {
        foreach ($tasksData as $taskData) {
            $task = new Tasks();
            $task->setTaskName($taskData['name'])
                 ->setTaskDescription($taskData['description'])
                 ->setTaskStatus($taskData['status'])
                 ->setTaskPriority($taskData['priority'])
                 ->setTaskComplexity($taskData['complexity'])
                 ->setTaskProject($project)
                 ->setTaskAssignedTo($taskData['assignedTo'])
                 ->setTaskStartDate($taskData['startDate'])
                 ->setTaskTargetDate($taskData['targetDate'])
                 ->setTaskEndDate($taskData['endDate']);
            
            // Définir l'utilisateur qui a mis à jour la tâche (généralement celui qui est assigné)
            if ($taskData['assignedTo']) {
                $task->setTaskUpdatedBy($taskData['assignedTo']);
            }
            
            $manager->persist($task);
        }
    }
    
    public function getDependencies(): array
    {
        return [
            AppFixtures::class,
            ProjectFixtures::class
        ];
    }
} 