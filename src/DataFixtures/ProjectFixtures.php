<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\Customers;
use App\Enum\ProjectStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ProjectFixtures extends Fixture implements DependentFixtureInterface
{
    // Constantes pour les références
    public const PROJECT_WEBSITE_REFERENCE = 'project-website';
    public const PROJECT_MOBILE_REFERENCE = 'project-mobile';
    public const PROJECT_ERP_REFERENCE = 'project-erp';
    public const PROJECT_LMS_REFERENCE = 'project-lms';
    public const PROJECT_CRM_REFERENCE = 'project-crm';
    public const PROJECT_ECOMMERCE_REFERENCE = 'project-ecommerce';

    public function load(ObjectManager $manager): void
    {
        // Récupérer les utilisateurs via les références
        $admin = $this->getReference(AppFixtures::ADMIN_USER_REFERENCE, User::class);
        $projectManager = $this->getReference(AppFixtures::PROJECT_MANAGER_USER_REFERENCE, User::class);
        
        // Récupérer les clients via les références
        $acmeCustomer = $this->getReference(CustomerFixtures::CUSTOMER_ACME_REFERENCE, Customers::class);
        $techCustomer = $this->getReference(CustomerFixtures::CUSTOMER_TECH_REFERENCE, Customers::class);
        $globalCustomer = $this->getReference(CustomerFixtures::CUSTOMER_GLOBAL_REFERENCE, Customers::class);
        $digitalCustomer = $this->getReference(CustomerFixtures::CUSTOMER_DIGITAL_REFERENCE, Customers::class);
        $innovCustomer = $this->getReference(CustomerFixtures::CUSTOMER_INNOV_REFERENCE, Customers::class);
        
        // Projet 1: Refonte du site web
        $project1 = new Project();
        $project1->setProjectName('Refonte du site web')
               ->setProjectDescription('Refonte complète du site web corporate avec intégration d\'un CMS moderne.')
               ->setProjectStatus(ProjectStatus::IN_PROGRESS)
               ->setProjectCustomer($acmeCustomer)
               ->setProjectManager($projectManager)
               ->setProjectStartDate(new \DateTime('-2 months'))
               ->setProjectTargetDate(new \DateTime('+4 months'))
               ->setProjectEndDate(null)
               ->setProjectUpdatedBy($projectManager);
        
        $manager->persist($project1);
        $this->addReference(self::PROJECT_WEBSITE_REFERENCE, $project1);
        
        // Projet 2: Application mobile e-commerce
        $project2 = new Project();
        $project2->setProjectName('Application mobile e-commerce')
               ->setProjectDescription('Développement d\'une application mobile pour la vente en ligne de produits.')
               ->setProjectStatus(ProjectStatus::NEW)
               ->setProjectCustomer($techCustomer)
               ->setProjectManager($projectManager)
               ->setProjectStartDate(new \DateTime('-1 week'))
               ->setProjectTargetDate(new \DateTime('+6 months'))
               ->setProjectEndDate(null)
               ->setProjectUpdatedBy($projectManager);
        
        $manager->persist($project2);
        $this->addReference(self::PROJECT_MOBILE_REFERENCE, $project2);
        
        // Projet 3: Système de gestion interne
        $project3 = new Project();
        $project3->setProjectName('Système de gestion interne (ERP)')
               ->setProjectDescription('Mise en place d\'un ERP personnalisé pour la gestion des ressources internes.')
               ->setProjectStatus(ProjectStatus::COMPLETED)
               ->setProjectCustomer($globalCustomer)
               ->setProjectManager($admin)
               ->setProjectStartDate(new \DateTime('-8 months'))
               ->setProjectTargetDate(new \DateTime('-1 month'))
               ->setProjectEndDate(new \DateTime('-15 days'))
               ->setProjectUpdatedBy($admin);
        
        $manager->persist($project3);
        $this->addReference(self::PROJECT_ERP_REFERENCE, $project3);
        
        // Projet 4: Plateforme de formation en ligne
        $project4 = new Project();
        $project4->setProjectName('Plateforme de formation en ligne (LMS)')
               ->setProjectDescription('Création d\'une plateforme LMS pour proposer des formations en ligne.')
               ->setProjectStatus(ProjectStatus::IN_PROGRESS)
               ->setProjectCustomer($acmeCustomer)
               ->setProjectManager($admin)
               ->setProjectStartDate(new \DateTime('-3 months'))
               ->setProjectTargetDate(new \DateTime('+2 months'))
               ->setProjectEndDate(null)
               ->setProjectUpdatedBy($admin);
        
        $manager->persist($project4);
        $this->addReference(self::PROJECT_LMS_REFERENCE, $project4);
        
        // Projet 5: CRM personnalisé
        $project5 = new Project();
        $project5->setProjectName('CRM personnalisé')
               ->setProjectDescription('Développement d\'un CRM adapté aux besoins spécifiques du client.')
               ->setProjectStatus(ProjectStatus::NEW)
               ->setProjectCustomer($digitalCustomer)
               ->setProjectManager($projectManager)
               ->setProjectStartDate(new \DateTime('+1 week'))
               ->setProjectTargetDate(new \DateTime('+5 months'))
               ->setProjectEndDate(null)
               ->setProjectUpdatedBy($projectManager);
        
        $manager->persist($project5);
        $this->addReference(self::PROJECT_CRM_REFERENCE, $project5);
        
        // Projet 6: Plateforme e-commerce
        $project6 = new Project();
        $project6->setProjectName('Plateforme e-commerce')
               ->setProjectDescription('Création d\'une boutique en ligne complète avec gestion des stocks et paiements.')
               ->setProjectStatus(ProjectStatus::IN_PROGRESS)
               ->setProjectCustomer($innovCustomer)
               ->setProjectManager($projectManager)
               ->setProjectStartDate(new \DateTime('-1 month'))
               ->setProjectTargetDate(new \DateTime('+3 months'))
               ->setProjectEndDate(null)
               ->setProjectUpdatedBy($projectManager);
        
        $manager->persist($project6);
        $this->addReference(self::PROJECT_ECOMMERCE_REFERENCE, $project6);
        
        $manager->flush();
    }
    
    public function getDependencies(): array
    {
        return [
            AppFixtures::class,
            CustomerFixtures::class
        ];
    }
}