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
                'type' => Tasks::TASK_TYPE_FEATURE,
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
                'type' => Tasks::TASK_TYPE_FEATURE,
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
                'type' => Tasks::TASK_TYPE_FEATURE,
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
                'type' => Tasks::TASK_TYPE_FEATURE,
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
                'type' => Tasks::TASK_TYPE_FEATURE,
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
                'type' => Tasks::TASK_TYPE_FEATURE,
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
                'type' => Tasks::TASK_TYPE_FEATURE,
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
                'type' => Tasks::TASK_TYPE_FEATURE,
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
                'type' => Tasks::TASK_TYPE_FEATURE,
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
                'type' => Tasks::TASK_TYPE_FEATURE,
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
                'type' => Tasks::TASK_TYPE_BUG,
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
                'type' => Tasks::TASK_TYPE_FEATURE,
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
                'type' => Tasks::TASK_TYPE_FEATURE,
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
                'type' => Tasks::TASK_TYPE_FEATURE,
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
                'type' => Tasks::TASK_TYPE_FEATURE,
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
                'type' => Tasks::TASK_TYPE_FEATURE,
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
                'type' => Tasks::TASK_TYPE_FEATURE,
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
                'type' => Tasks::TASK_TYPE_FEATURE,
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
                'name' => 'Analyse des besoins e-commerce',
                'description' => 'Analyse des besoins spécifiques pour la plateforme e-commerce.',
                'type' => Tasks::TASK_TYPE_FEATURE,
                'status' => TaskStatus::COMPLETED,
                'priority' => TaskPriority::HIGH,
                'complexity' => TaskComplexity::MODERATE,
                'assignedTo' => $admin,
                'startDate' => new \DateTime('-1 month'),
                'targetDate' => new \DateTime('-3 weeks'),
                'endDate' => new \DateTime('-3 weeks 2 days')
            ],
            [
                'name' => 'Développement du catalogue produits',
                'description' => 'Mise en place du système de gestion de catalogue produits.',
                'type' => Tasks::TASK_TYPE_FEATURE,
                'status' => TaskStatus::IN_PROGRESS,
                'priority' => TaskPriority::HIGH,
                'complexity' => TaskComplexity::COMPLEX,
                'assignedTo' => $leadDev,
                'startDate' => new \DateTime('-2 weeks'),
                'targetDate' => new \DateTime('+2 weeks'),
                'endDate' => null
            ],
            [
                'name' => 'Intégration des moyens de paiement',
                'description' => 'Intégration des différentes passerelles de paiement.',
                'type' => Tasks::TASK_TYPE_FEATURE,
                'status' => TaskStatus::NEW,
                'priority' => TaskPriority::HIGH,
                'complexity' => TaskComplexity::COMPLEX,
                'assignedTo' => $developer,
                'startDate' => null,
                'targetDate' => new \DateTime('+1 month'),
                'endDate' => null
            ],
            [
                'name' => 'Développement du système de livraison',
                'description' => 'Mise en place du système de gestion des livraisons et suivi de colis.',
                'type' => Tasks::TASK_TYPE_FEATURE,
                'status' => TaskStatus::NEW,
                'priority' => TaskPriority::MEDIUM,
                'complexity' => TaskComplexity::MODERATE,
                'assignedTo' => $developer2,
                'startDate' => null,
                'targetDate' => new \DateTime('+1 month 15 days'),
                'endDate' => null
            ],
            [
                'name' => 'Optimisation des performances',
                'description' => 'Optimisation des temps de chargement et de la réactivité de la plateforme.',
                'type' => Tasks::TASK_TYPE_HIGHTEST,
                'status' => TaskStatus::NEW,
                'priority' => TaskPriority::MEDIUM,
                'complexity' => TaskComplexity::COMPLEX,
                'assignedTo' => $developer3,
                'startDate' => null,
                'targetDate' => new \DateTime('+2 months'),
                'endDate' => null
            ]
        ]);
    }
    
    private function createTasks(ObjectManager $manager, Project $project, array $tasksData): void
    {
        foreach ($tasksData as $taskData) {
            $task = new Tasks();
            $task->setTaskName($taskData['name'])
                 ->setTaskDescription($taskData['description'])
                 ->setTaskType($taskData['type'] ?? 'feature')
                 ->setTaskStatus($taskData['status'])
                 ->setTaskPriority($taskData['priority'])
                 ->setTaskComplexity($taskData['complexity'])
                 ->setTaskProject($project)
                 ->setTaskAssignedTo($taskData['assignedTo'])
                 ->setTaskStartDate($taskData['startDate'])
                 ->setTaskTargetDate($taskData['targetDate'])
                 ->setTaskEndDate($taskData['endDate'])
                 ->setTaskUpdatedBy($taskData['assignedTo']);
            
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