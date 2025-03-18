<?php
// tests/Functional/Controller/Communication/CommentTest.php

namespace App\Tests\Functional\Controller\Communication;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Repository\CommentRepository;
use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\Comment;
use App\Enum\UserRole;
use App\Enum\TaskStatus;
use App\Enum\TaskPriority;

class CommentTest extends WebTestCase
{
    private $client;
    private $developer;
    private $userRepository;
    private $projectRepository;
    private $taskRepository;
    private $commentRepository;
    private $entityManager;
    private $testProject;
    private $testTask;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->projectRepository = static::getContainer()->get(ProjectRepository::class);
        $this->taskRepository = static::getContainer()->get(TaskRepository::class);
        $this->commentRepository = static::getContainer()->get(CommentRepository::class);
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $passwordHasher = static::getContainer()->get('security.user_password_hasher');
        
        // Récupérer ou créer un développeur
        $this->developer = $this->userRepository->findOneBy(['userRole' => UserRole::DEVELOPER]);
        if (!$this->developer) {
            $this->developer = User::create(
                'Dev',
                'Comment',
                'dev_comment_' . uniqid() . '@example.com',
                $passwordHasher->hashPassword(new User(), 'Test@123456')
            );
            $this->developer->setUserRole(UserRole::DEVELOPER);
            $this->entityManager->persist($this->developer);
            $this->entityManager->flush();
        }
        
        // Créer un projet et une tâche de test
        $this->testProject = $this->createTestProject();
        $this->testTask = $this->createTestTask();
        
        // Connecter le développeur
        $this->client->loginUser($this->developer);
    }

    /**
     * Test C1: Ajout d'un commentaire sur une tâche
     */
    public function testAddComment(): void
    {
        // Accéder à la page de détail de la tâche
        $this->client->request('GET', '/tasks/view/' . $this->testTask->getId());
        $this->assertResponseIsSuccessful();
        
        // Générer un contenu unique pour éviter les conflits
        $commentContent = 'Commentaire de test ' . uniqid();
        
        // Soumettre le formulaire d'ajout de commentaire
        $this->client->submitForm('Commenter', [
            'comment_form[commentContent]' => $commentContent,
        ]);
        
        // Vérifier la redirection vers la page de détail de la tâche
        $this->assertResponseRedirects('/tasks/view/' . $this->testTask->getId());
        $this->client->followRedirect();
        
        // Vérifier la présence d'un message de succès
        $this->assertSelectorExists('.alert-success');
        
        // Vérifier que le commentaire a été créé en base de données
        $comment = $this->commentRepository->findOneBy(['commentContent' => $commentContent]);
        $this->assertNotNull($comment);
        $this->assertEquals($this->developer->getId(), $comment->getCommentAuthor()->getId());
        $this->assertEquals($this->testTask->getId(), $comment->getCommentTask()->getId());
    }

    /**
     * Test: Modification d'un commentaire
     */
    public function testUpdateComment(): void
    {
        // Créer un commentaire de test
        $comment = $this->createTestComment();
        
        // Accéder à la page de modification du commentaire
        $this->client->request('GET', '/comments/edit/' . $comment->getId());
        $this->assertResponseIsSuccessful();
        
        // Soumettre le formulaire de modification
        $updatedContent = 'Commentaire modifié ' . uniqid();
        $this->client->submitForm('Modifier', [
            'comment_form[commentContent]' => $updatedContent,
        ]);
        
        // Vérifier la redirection vers la page de détail de la tâche
        $this->assertResponseRedirects('/tasks/view/' . $this->testTask->getId());
        $this->client->followRedirect();
        
        // Vérifier la présence d'un message de succès
        $this->assertSelectorExists('.alert-success');
        
        // Vérifier que le commentaire a été modifié en base de données
        $this->entityManager->refresh($comment);
        $this->assertEquals($updatedContent, $comment->getCommentContent());
    }

    /**
     * Test: Suppression d'un commentaire
     */
    public function testDeleteComment(): void
    {
        // Créer un commentaire de test
        $comment = $this->createTestComment();
        $commentId = $comment->getId();
        
        // Soumettre la requête de suppression
        $this->client->request('DELETE', '/comments/delete/' . $commentId);
        
        // Vérifier la réponse JSON
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        
        // Vérifier que le commentaire a été supprimé de la base de données
        $deletedComment = $this->commentRepository->find($commentId);
        $this->assertNull($deletedComment);
    }

    /**
     * Méthode utilitaire pour créer un projet de test
     */
    private function createTestProject(): Project
    {
        // Vérifier si un projet de test existe déjà
        $project = $this->projectRepository->findOneBy(['projectName' => 'Projet Test Commentaires']);
        
        if (!$project) {
            $project = new Project();
            $project->setName('Projet Test Commentaires');
            $project->setDescription('Projet pour tester les commentaires');
            $project->setStartDate(new \DateTime());
            $project->setEndDate(new \DateTime('+1 month'));
            $project->setOwner($this->developer);
            
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
        // Vérifier si une tâche de test existe déjà
        $task = $this->taskRepository->findOneBy(['name' => 'Tâche Test Commentaires']);
        
        if (!$task) {
            $task = new Task();
            $task->setName('Tâche Test Commentaires');
            $task->setDescription('Tâche pour tester les commentaires');
            $task->setPriority(TaskPriority::MEDIUM);
            $task->setStatus(TaskStatus::TODO);
            $task->setDueDate(new \DateTime('+1 week'));
            $task->setProject($this->testProject);
            $task->setCreator($this->developer);
            
            $this->entityManager->persist($task);
            $this->entityManager->flush();
        }
        
        return $task;
    }

    /**
     * Méthode utilitaire pour créer un commentaire de test
     */
    private function createTestComment(): Comment
    {
        $comment = new Comment();
        $comment->setContent('Commentaire de test ' . uniqid());
        $comment->setAuthor($this->developer);
        $comment->setTask($this->testTask);
        $comment->setCreatedAt(new \DateTime());
        
        $this->entityManager->persist($comment);
        $this->entityManager->flush();
        
        return $comment;
    }
} 