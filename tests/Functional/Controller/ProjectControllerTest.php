<?php
// tests/Functional/Controller/ProjectControllerTest.php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use App\Repository\CustomersRepository;
use App\Entity\Project;
use App\Entity\Tasks;
use App\Enum\ProjectStatus;
use App\Enum\TaskStatus;
use App\Enum\TaskPriority;

class ProjectControllerTest extends WebTestCase
{
    private $client;
    private $projectManager;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        
        // Récupérer un chef de projet pour les tests
        $this->projectManager = $userRepository->findOneByEmail('project.manager@example.com');
        
        // Si le chef de projet n'existe pas, utiliser un admin
        if (!$this->projectManager) {
            $this->projectManager = $userRepository->findOneByEmail('admin@example.com');
        }
        
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * Test P1: Création d'un projet
     */
    public function testCreateProject(): void
    {
        // Se connecter en tant que chef de projet
        $this->client->loginUser($this->projectManager);
        
        // Accéder à la page de gestion des projets
        $this->client->request('GET', '/project/management');
        $this->assertResponseIsSuccessful();
        
        // Récupérer un client pour le projet
        $customersRepository = static::getContainer()->get(CustomersRepository::class);
        $customer = $customersRepository->findOneBy([]);
        
        if (!$customer) {
            $this->markTestSkipped('Aucun client disponible pour créer un projet');
        }
        
        // Créer un projet via une requête AJAX simulée
        $this->client->request(
            'POST',
            '/project/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'projectName' => 'Projet de test',
                'projectDescription' => 'Description du projet de test',
                'projectStatus' => ProjectStatus::IN_PROGRESS->value,
                'projectCustomer' => $customer->getId(),
            ])
        );
        
        // Vérifier la réponse JSON
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        
        // Vérifier que le projet a été créé en base de données
        $projectRepository = static::getContainer()->get(ProjectRepository::class);
        $project = $projectRepository->findOneBy(['projectName' => 'Projet de test']);
        $this->assertNotNull($project);
        $this->assertEquals('Description du projet de test', $project->getProjectDescription());
        
        return $project;
    }

    /**
     * Test P2: Ajout de tâches dans un projet
     * 
     * @depends testCreateProject
     */
    public function testAddTaskToProject(Project $project): void
    {
        // Se connecter en tant que chef de projet
        $this->client->loginUser($this->projectManager);
        
        // Créer une tâche via une requête AJAX simulée
        $this->client->request(
            'POST',
            '/task/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'taskName' => 'Tâche de test',
                'taskDescription' => 'Description de la tâche de test',
                'taskType' => Tasks::TASK_TYPE_FEATURE,
                'taskStatus' => TaskStatus::NEW->value,
                'taskPriority' => TaskPriority::HIGH->value,
                'taskProject' => $project->getId(),
            ])
        );
        
        // Vérifier la réponse JSON
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        
        // Vérifier que la tâche a été créée et associée au projet
        $tasks = $project->getTasks();
        $this->assertCount(1, $tasks);
        $task = $tasks->first();
        $this->assertEquals('Tâche de test', $task->getTaskName());
        
        return $task;
    }

    /**
     * Test P3: Assignation d'une tâche à un utilisateur
     * 
     * @depends testAddTaskToProject
     */
    public function testAssignTaskToUser(Tasks $task): void
    {
        // Se connecter en tant que chef de projet
        $this->client->loginUser($this->projectManager);
        
        // Récupérer un développeur pour l'assignation
        $userRepository = static::getContainer()->get(UserRepository::class);
        $developer = $userRepository->findOneByEmail('developer@example.com');
        
        if (!$developer) {
            $this->markTestSkipped('Aucun développeur disponible pour assigner la tâche');
        }
        
        // Assigner la tâche via une requête AJAX simulée
        $this->client->request(
            'POST',
            '/task/assign/' . $task->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'userId' => $developer->getId(),
            ])
        );
        
        // Vérifier la réponse JSON
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        
        // Vérifier que la tâche a été assignée
        $this->entityManager->refresh($task);
        $this->assertEquals($developer->getId(), $task->getTaskAssignedTo()->getId());
    }

    /**
     * Nettoyer les données de test
     */
    protected function tearDown(): void
    {
        // Supprimer les projets et tâches de test
        $projectRepository = static::getContainer()->get(ProjectRepository::class);
        $project = $projectRepository->findOneBy(['projectName' => 'Projet de test']);
        
        if ($project) {
            $this->entityManager->remove($project);
            $this->entityManager->flush();
        }
        
        parent::tearDown();
    }
} 