<?php
// tests/Functional/Controller/Communication/AttachmentTest.php

namespace App\Tests\Functional\Controller\Communication;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Repository\AttachmentRepository;
use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\Attachment;
use App\Enum\UserRole;
use App\Enum\TaskStatus;
use App\Enum\TaskPriority;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AttachmentTest extends WebTestCase
{
    private $client;
    private $developer;
    private $userRepository;
    private $projectRepository;
    private $taskRepository;
    private $attachmentRepository;
    private $entityManager;
    private $testProject;
    private $testTask;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->projectRepository = static::getContainer()->get(ProjectRepository::class);
        $this->taskRepository = static::getContainer()->get(TaskRepository::class);
        $this->attachmentRepository = static::getContainer()->get(AttachmentRepository::class);
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $passwordHasher = static::getContainer()->get('security.user_password_hasher');
        
        // Récupérer ou créer un développeur
        $this->developer = $this->userRepository->findOneBy(['userRole' => UserRole::DEVELOPER]);
        if (!$this->developer) {
            $this->developer = User::create(
                'Dev',
                'Attachment',
                'dev_attachment_' . uniqid() . '@example.com',
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
     * Test C2: Ajout d'une pièce jointe
     */
    public function testAddAttachment(): void
    {
        // Accéder à la page de détail de la tâche
        $this->client->request('GET', '/tasks/view/' . $this->testTask->getId());
        $this->assertResponseIsSuccessful();
        
        // Créer un fichier temporaire pour le test
        $projectDir = static::getContainer()->getParameter('kernel.project_dir');
        $tempFile = tempnam(sys_get_temp_dir(), 'test_attachment_');
        file_put_contents($tempFile, 'Contenu du fichier de test');
        
        // Créer un objet UploadedFile
        $uploadedFile = new UploadedFile(
            $tempFile,
            'test_document.txt',
            'text/plain',
            null,
            true
        );
        
        // Soumettre le formulaire d'ajout de pièce jointe
        $this->client->request(
            'POST',
            '/tasks/' . $this->testTask->getId() . '/attachments/add',
            ['attachment_form' => ['attachmentName' => 'Document de test']],
            ['attachment_form' => ['attachmentFile' => $uploadedFile]]
        );
        
        // Vérifier la redirection vers la page de détail de la tâche
        $this->assertResponseRedirects('/tasks/view/' . $this->testTask->getId());
        $this->client->followRedirect();
        
        // Vérifier la présence d'un message de succès
        $this->assertSelectorExists('.alert-success');
        
        // Vérifier que la pièce jointe a été créée en base de données
        $attachment = $this->attachmentRepository->findOneBy(['attachmentName' => 'Document de test']);
        $this->assertNotNull($attachment);
        $this->assertEquals($this->developer->getId(), $attachment->getAttachmentUploader()->getId());
        $this->assertEquals($this->testTask->getId(), $attachment->getAttachmentTask()->getId());
        
        // Nettoyer le fichier temporaire
        @unlink($tempFile);
    }

    /**
     * Test: Téléchargement d'une pièce jointe
     */
    public function testDownloadAttachment(): void
    {
        // Créer une pièce jointe de test
        $attachment = $this->createTestAttachment();
        
        // Accéder à l'URL de téléchargement
        $this->client->request('GET', '/attachments/download/' . $attachment->getId());
        
        // Vérifier que la réponse est un téléchargement
        $this->assertResponseIsSuccessful();
        $this->assertEquals(
            'application/octet-stream',
            $this->client->getResponse()->headers->get('Content-Type')
        );
        $this->assertEquals(
            'attachment; filename="test_document.txt"',
            $this->client->getResponse()->headers->get('Content-Disposition')
        );
    }

    /**
     * Test: Suppression d'une pièce jointe
     */
    public function testDeleteAttachment(): void
    {
        // Créer une pièce jointe de test
        $attachment = $this->createTestAttachment();
        $attachmentId = $attachment->getId();
        
        // Soumettre la requête de suppression
        $this->client->request('DELETE', '/attachments/delete/' . $attachmentId);
        
        // Vérifier la réponse JSON
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        
        // Vérifier que la pièce jointe a été supprimée de la base de données
        $deletedAttachment = $this->attachmentRepository->find($attachmentId);
        $this->assertNull($deletedAttachment);
    }

    /**
     * Méthode utilitaire pour créer un projet de test
     */
    private function createTestProject(): Project
    {
        // Vérifier si un projet de test existe déjà
        $project = $this->projectRepository->findOneBy(['projectName' => 'Projet Test Pièces Jointes']);
        
        if (!$project) {
            $project = new Project();
            $project->setProjectName('Projet Test Pièces Jointes');
            $project->setProjectDescription('Projet pour tester les pièces jointes');
            $project->setProjectStartDate(new \DateTime());
            $project->setProjectEndDate(new \DateTime('+1 month'));
            $project->setProjectOwner($this->developer);
            
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
        $task = $this->taskRepository->findOneBy(['taskName' => 'Tâche Test Pièces Jointes']);
        
        if (!$task) {
            $task = new Task();
            $task->setTaskName('Tâche Test Pièces Jointes');
            $task->setTaskDescription('Tâche pour tester les pièces jointes');
            $task->setTaskPriority(TaskPriority::MEDIUM);
            $task->setTaskStatus(TaskStatus::TODO);
            $task->setTaskDueDate(new \DateTime('+1 week'));
            $task->setTaskProject($this->testProject);
            $task->setTaskCreator($this->developer);
            
            $this->entityManager->persist($task);
            $this->entityManager->flush();
        }
        
        return $task;
    }

    /**
     * Méthode utilitaire pour créer une pièce jointe de test
     */
    private function createTestAttachment(): Attachment
    {
        // Créer un fichier temporaire pour le test
        $tempFile = tempnam(sys_get_temp_dir(), 'test_attachment_');
        file_put_contents($tempFile, 'Contenu du fichier de test');
        
        // Créer un répertoire pour les pièces jointes si nécessaire
        $projectDir = static::getContainer()->getParameter('kernel.project_dir');
        $uploadDir = $projectDir . '/public/uploads/attachments';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Copier le fichier temporaire vers le répertoire des pièces jointes
        $filename = 'test_document_' . uniqid() . '.txt';
        $filePath = $uploadDir . '/' . $filename;
        copy($tempFile, $filePath);
        
        // Créer l'entité pièce jointe
        $attachment = new Attachment();
        $attachment->setAttachmentName('Document de test');
        $attachment->setAttachmentFilename($filename);
        $attachment->setAttachmentPath('uploads/attachments/' . $filename);
        $attachment->setAttachmentMimeType('text/plain');
        $attachment->setAttachmentSize(filesize($filePath));
        $attachment->setAttachmentUploader($this->developer);
        $attachment->setAttachmentTask($this->testTask);
        $attachment->setAttachmentUploadedAt(new \DateTime());
        
        $this->entityManager->persist($attachment);
        $this->entityManager->flush();
        
        // Nettoyer le fichier temporaire
        @unlink($tempFile);
        
        return $attachment;
    }
} 