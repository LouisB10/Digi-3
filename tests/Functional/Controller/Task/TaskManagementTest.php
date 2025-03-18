<?php
// tests/Functional/Controller/Task/TaskManagementTest.php

namespace App\Tests\Functional\Controller\Task;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;
use App\Enum\UserRole;
use App\Enum\TaskStatus;
use App\Enum\TaskPriority;

class TaskManagementTest extends WebTestCase
{
    private $client;
    private $projectManager;
    private $developer;
    private $userRepository;
    private $projectRepository;
    private $taskRepository;
    private $entityManager;
    private $testProject;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->projectRepository = static::getContainer()->get(ProjectRepository::class);
        $this->taskRepository = static::getContainer()->get(TaskRepository::class);
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $passwordHasher = static::getContainer()->get('security.user_password_hasher');
        
        // Récupérer ou créer un chef de projet
        $this->projectManager = $this->userRepository->findOneBy(['userRole' => UserRole::PROJECT_MANAGER]);
        if (!$this->projectManager) {
            $this->projectManager = User::create(
                'Chef',
                'Projet',
                'chef_projet_task_' . uniqid() . '@example.com',
                $passwordHasher->hashPassword(new User(), 'Test@123456')
            );
            $this->projectManager->setUserRole(UserRole::PROJECT_MANAGER);
            $this->entityManager->persist($this->projectManager);
            $this->entityManager->flush();
        }
        
        // Récupérer ou créer un développeur
        $this->developer = $this->userRepository->findOneBy(['userRole' => UserRole::DEVELOPER]);
        if (!$this->developer) {
            $this->developer = User::create(
                'Dev',
                'Test',
                'dev_test_' . uniqid() . '@example.com',
                $passwordHasher->hashPassword(new User(), 'Test@123456')
            );
            $this->developer->setUserRole(UserRole::DEVELOPER);
            $this->entityManager->persist($this->developer);
            $this->entityManager->flush();
        }
        
        // Créer un projet de test
        $this->testProject = $this->createTestProject();
        
        // Connecter le chef de projet
        $this->client->loginUser($this->projectManager);
    }

    /**
     * Test P2: Ajout de tâches dans un projet
     */
    public function testAddTask(): void
    {
        // Accéder à la page du projet
        $this->client->request('GET', '/projects/view/' . $this->testProject->getId());
        $this->assertResponseIsSuccessful();
        
        // Cliquer sur le bouton pour ajouter une nouvelle tâche
        $this->client->clickLink('Ajouter une tâche');
        $this->assertResponseIsSuccessful();
        
        // Générer un nom unique pour éviter les conflits
        $taskName = 'Tâche Test ' . uniqid();
        
        // Soumettre le formulaire de création de tâche
        $this->client->submitForm('Créer', [
            'task_form[taskName]' => $taskName,
            'task_form[taskDescription]' => 'Description de la tâche de test',
            'task_form[taskPriority]' => TaskPriority::MEDIUM->value,
            'task_form[taskStatus]' => TaskStatus::TODO->value,
            'task_form[taskDueDate]' => (new \DateTime('+1 week'))->format('Y-m-d'),
        ]);
        
        // Vérifier la redirection vers la page du projet
        $this->assertResponseRedirects('/projects/view/' . $this->testProject->getId());
        $this->client->followRedirect();
        
        // Vérifier la présence d'un message de succès
        $this->assertSelectorExists('.alert-success');
        
        // Vérifier que la tâche a été créée en base de données
        $task = $this->taskRepository->findOneBy(['taskName' => $taskName]);
        $this->assertNotNull($task);
        $this->assertEquals('Description de la tâche de test', $task->getTaskDescription());
        $this->assertEquals(TaskPriority::MEDIUM, $task->getTaskPriority());
        $this->assertEquals(TaskStatus::TODO, $task->getTaskStatus());
    }

    /**
     * Test P3: Assignation d'une tâche à un utilisateur
     */
    public function testAssignTask(): void
    {
        // Créer une tâche de test
        $task = $this->createTestTask();
        
        // Accéder à la page de modification de la tâche
        $this->client->request('GET', '/tasks/edit/' . $task->getId());
        $this->assertResponseIsSuccessful();
        
        // Soumettre le formulaire pour assigner la tâche au développeur
        $this->client->submitForm('Modifier', [
            'task_form[taskAssignee]' => $this->developer->getId(),
        ]);
        
        // Vérifier la redirection vers la page du projet
        $this->assertResponseRedirects('/projects/view/' . $this->testProject->getId());
        $this->client->followRedirect();
        
        // Vérifier la présence d'un message de succès
        $this->assertSelectorExists('.alert-success');
        
        // Vérifier que la tâche a été assignée au développeur
        $this->entityManager->refresh($task);
        $this->assertEquals($this->developer->getId(), $task->getTaskAssignee()->getId());
    }

    /**
     * Test: Modification du statut d'une tâche
     */
    public function testUpdateTaskStatus(): void
    {
        // Créer une tâche de test
        $task = $this->createTestTask();
        
        // Accéder à la page de modification de la tâche
        $this->client->request('GET', '/tasks/edit/' . $task->getId());
        $this->assertResponseIsSuccessful();
        
        // Soumettre le formulaire pour modifier le statut de la tâche
        $this->client->submitForm('Modifier', [
            'task_form[taskStatus]' => TaskStatus::IN_PROGRESS->value,
        ]);
        
        // Vérifier la redirection vers la page du projet
        $this->assertResponseRedirects('/projects/view/' . $this->testProject->getId());
        $this->client->followRedirect();
        
        // Vérifier la présence d'un message de succès
        $this->assertSelectorExists('.alert-success');
        
        // Vérifier que le statut de la tâche a été modifié
        $this->entityManager->refresh($task);
        $this->assertEquals(TaskStatus::IN_PROGRESS, $task->getTaskStatus());
    }

    /**
     * Test: Suppression d'une tâche
     */
    public function testDeleteTask(): void
    {
        // Créer une tâche de test
        $task = $this->createTestTask();
        $taskId = $task->getId();
        
        // Soumettre la requête de suppression
        $this->client->request('DELETE', '/tasks/delete/' . $taskId);
        
        // Vérifier la réponse JSON
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        
        // Vérifier que la tâche a été supprimée de la base de données
        $deletedTask = $this->taskRepository->find($taskId);
        $this->assertNull($deletedTask);
    }

    /**
     * Méthode utilitaire pour créer un projet de test
     */
    private function createTestProject(): Project
    {
        // Vérifier si un projet de test existe déjà
        $project = $this->projectRepository->findOneBy(['projectName' => 'Projet Test Tâches']);
        
        if (!$project) {
            $project = new Project();
            $project->setProjectName('Projet Test Tâches');
            $project->setProjectDescription('Projet pour tester les tâches');
            $project->setProjectStartDate(new \DateTime());
            $project->setProjectEndDate(new \DateTime('+1 month'));
            $project->setProjectOwner($this->projectManager);
            
            $this->entityManager->persist($project);
            $this->entityManager->flush();
        }
        
        return $project;
    }

    /**
     * Méthode utilitaire pour créer une tâche de test
     */
    private function createTestTask(): Task
    {
        $task = new Task();
        $task->setTaskName('Tâche Test ' . uniqid());
        $task->setTaskDescription('Description de la tâche de test');
        $task->setTaskPriority(TaskPriority::MEDIUM);
        $task->setTaskStatus(TaskStatus::TODO);
        $task->setTaskDueDate(new \DateTime('+1 week'));
        $task->setTaskProject($this->testProject);
        $task->setTaskCreator($this->projectManager);
        
        $this->entityManager->persist($task);
        $this->entityManager->flush();
        
        return $task;
    }
} 