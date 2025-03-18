<?php
// tests/Functional/Controller/Project/ProjectManagementTest.php

namespace App\Tests\Functional\Controller\Project;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use App\Entity\User;
use App\Entity\Project;
use App\Enum\UserRole;

class ProjectManagementTest extends WebTestCase
{
    private $client;
    private $projectManager;
    private $userRepository;
    private $projectRepository;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->projectRepository = static::getContainer()->get(ProjectRepository::class);
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        
        // Récupérer ou créer un chef de projet
        $this->projectManager = $this->userRepository->findOneBy(['userRole' => UserRole::PROJECT_MANAGER]);
        
        if (!$this->projectManager) {
            $passwordHasher = static::getContainer()->get('security.user_password_hasher');
            $this->projectManager = User::create(
                'Chef',
                'Projet',
                'chef_projet_' . uniqid() . '@example.com',
                $passwordHasher->hashPassword(new User(), 'Test@123456')
            );
            $this->projectManager->setUserRole(UserRole::PROJECT_MANAGER);
            $this->entityManager->persist($this->projectManager);
            $this->entityManager->flush();
        }
        
        // Connecter le chef de projet
        $this->client->loginUser($this->projectManager);
    }

    /**
     * Test P1: Création d'un projet
     */
    public function testCreateProject(): void
    {
        // Accéder à la page de gestion des projets
        $this->client->request('GET', '/projects');
        $this->assertResponseIsSuccessful();
        
        // Cliquer sur le bouton pour créer un nouveau projet
        $this->client->clickLink('Créer un projet');
        $this->assertResponseIsSuccessful();
        
        // Générer un nom unique pour éviter les conflits
        $projectName = 'Projet Test ' . uniqid();
        
        // Soumettre le formulaire de création de projet
        $this->client->submitForm('Créer', [
            'project_form[projectName]' => $projectName,
            'project_form[projectDescription]' => 'Description du projet de test',
            'project_form[projectDateStart]' => (new \DateTime())->format('Y-m-d'),
            'project_form[projectDateEnd]' => (new \DateTime('+1 month'))->format('Y-m-d'),
        ]);
        
        // Vérifier la redirection vers la liste des projets
        $this->assertResponseRedirects('/projects');
        $this->client->followRedirect();
        
        // Vérifier la présence d'un message de succès
        $this->assertSelectorExists('.alert-success');
        
        // Vérifier que le projet a été créé en base de données
        $project = $this->projectRepository->findOneBy(['projectName' => $projectName]);
        $this->assertNotNull($project);
        $this->assertEquals('Description du projet de test', $project->getProjectDescription());
    }

    /**
     * Test P1-Erreur: Tentative de création d'un projet avec des données invalides
     */
    public function testCreateProjectWithInvalidData(): void
    {
        // Accéder à la page de création de projet
        $this->client->request('GET', '/projects/create');
        $this->assertResponseIsSuccessful();
        
        // Soumettre le formulaire avec des données invalides (nom vide)
        $this->client->submitForm('Créer', [
            'project_form[projectName]' => '',
            'project_form[projectDescription]' => 'Description du projet de test',
            'project_form[projectDateStart]' => (new \DateTime())->format('Y-m-d'),
            'project_form[projectDateEnd]' => (new \DateTime('+1 month'))->format('Y-m-d'),
        ]);
        
        // Vérifier que nous restons sur la page de création avec des erreurs
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.invalid-feedback');
    }

    /**
     * Test: Modification d'un projet
     */
    public function testUpdateProject(): void
    {
        // Créer un projet de test
        $project = $this->createTestProject();
        
        // Accéder à la page de modification du projet
        $this->client->request('GET', '/projects/edit/' . $project->getId());
        $this->assertResponseIsSuccessful();
        
        // Soumettre le formulaire de modification
        $updatedName = 'Projet Modifié ' . uniqid();
        $this->client->submitForm('Modifier', [
            'project_form[projectName]' => $updatedName,
            'project_form[projectDescription]' => 'Description modifiée',
        ]);
        
        // Vérifier la redirection vers la liste des projets
        $this->assertResponseRedirects('/projects');
        $this->client->followRedirect();
        
        // Vérifier la présence d'un message de succès
        $this->assertSelectorExists('.alert-success');
        
        // Vérifier que le projet a été modifié en base de données
        $this->entityManager->refresh($project);
        $this->assertEquals($updatedName, $project->getProjectName());
        $this->assertEquals('Description modifiée', $project->getProjectDescription());
    }

    /**
     * Test: Suppression d'un projet
     */
    public function testDeleteProject(): void
    {
        // Créer un projet de test
        $project = $this->createTestProject();
        $projectId = $project->getId();
        
        // Soumettre la requête de suppression
        $this->client->request('DELETE', '/projects/delete/' . $projectId);
        
        // Vérifier la réponse JSON
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        
        // Vérifier que le projet a été supprimé de la base de données
        $deletedProject = $this->projectRepository->find($projectId);
        $this->assertNull($deletedProject);
    }

    /**
     * Méthode utilitaire pour créer un projet de test
     */
    private function createTestProject(): Project
    {
        $project = new Project();
        $project->setProjectName('Projet Test ' . uniqid());
        $project->setProjectDescription('Description du projet de test');
        $project->setProjectDateStart(new \DateTime());
        $project->setProjectDateEnd(new \DateTime('+1 month'));
        $project->setProjectOwner($this->projectManager);
        
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        
        return $project;
    }
} 