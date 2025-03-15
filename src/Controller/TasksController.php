<?php

namespace App\Controller;

use App\Entity\Tasks;
use App\Entity\TasksComments;
use App\Entity\TasksAttachments;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;

class TasksController extends AbstractController
{
    private $entityManager;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    #[Route('/task/{id}', name: 'app_details_tasks')]
    public function details(int $id): Response
    {
        // Utilisation de l'EntityManager pour récupérer la tâche
        $task = $this->entityManager->getRepository(Tasks::class)->find($id);

        if (!$task) {
            throw $this->createNotFoundException('La tâche n\'existe pas.');
        }

        return $this->render('project/details_task.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/task/{id}/comments', name: 'app_task_comments', methods: ['GET'])]
    public function getComments(int $id): JsonResponse
    {
        $task = $this->entityManager->getRepository(Tasks::class)->find($id);

        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        $comments = $this->entityManager->getRepository(TasksComments::class)->findBy(
            ['task' => $task],
            ['createdAt' => 'DESC']
        );

        $formattedComments = [];
        foreach ($comments as $comment) {
            $formattedComments[] = [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'createdAt' => $comment->getCreatedAt()->format('d/m/Y H:i'),
                'user' => [
                    'id' => $comment->getUser()->getId(),
                    'fullName' => $comment->getUser()->getFullName(),
                    'avatar' => $comment->getUser()->getUserAvatar()
                ]
            ];
        }

        return $this->json($formattedComments);
    }

    #[Route('/task/{id}/comment/add', name: 'app_task_comment_add', methods: ['POST'])]
    public function addComment(Request $request, int $id): JsonResponse
    {
        $task = $this->entityManager->getRepository(Tasks::class)->find($id);

        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        $content = $request->request->get('content');
        if (empty($content)) {
            return $this->json(['error' => 'Le contenu du commentaire ne peut pas être vide'], 400);
        }

        /** @var User $user */
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $comment = new TasksComments();
        $comment->setTask($task)
                ->setUser($user)
                ->setContent($content);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'comment' => [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'createdAt' => $comment->getCreatedAt()->format('d/m/Y H:i'),
                'user' => [
                    'id' => $user->getId(),
                    'fullName' => $user->getFullName(),
                    'avatar' => $user->getUserAvatar()
                ]
            ]
        ]);
    }

    #[Route('/task/{id}/comment/{commentId}/delete', name: 'app_task_comment_delete', methods: ['POST'])]
    public function deleteComment(int $id, int $commentId): JsonResponse
    {
        $comment = $this->entityManager->getRepository(TasksComments::class)->find($commentId);

        if (!$comment) {
            return $this->json(['error' => 'Commentaire non trouvé'], 404);
        }

        if ($comment->getTask()->getId() !== $id) {
            return $this->json(['error' => 'Le commentaire n\'appartient pas à cette tâche'], 400);
        }

        /** @var User $user */
        $user = $this->security->getUser();
        if (!$user || ($comment->getUser()->getId() !== $user->getId() && !in_array('ROLE_ADMIN', $user->getRoles()))) {
            return $this->json(['error' => 'Vous n\'avez pas les droits pour supprimer ce commentaire'], 403);
        }

        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/task/{id}/attachments', name: 'app_task_attachments', methods: ['GET'])]
    public function getAttachments(int $id): JsonResponse
    {
        $task = $this->entityManager->getRepository(Tasks::class)->find($id);

        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        $attachments = $this->entityManager->getRepository(TasksAttachments::class)->findBy(
            ['task' => $task],
            ['createdAt' => 'DESC']
        );

        $formattedAttachments = [];
        foreach ($attachments as $attachment) {
            $formattedAttachments[] = [
                'id' => $attachment->getId(),
                'originalName' => $attachment->getOriginalName(),
                'fileName' => $attachment->getFileName(),
                'mimeType' => $attachment->getMimeType(),
                'fileSize' => $attachment->getFileSize(),
                'description' => $attachment->getDescription(),
                'createdAt' => $attachment->getCreatedAt()->format('d/m/Y H:i'),
                'user' => [
                    'id' => $attachment->getUploadedBy()->getId(),
                    'fullName' => $attachment->getUploadedBy()->getFullName(),
                    'avatar' => $attachment->getUploadedBy()->getUserAvatar()
                ]
            ];
        }

        return $this->json($formattedAttachments);
    }

    #[Route('/task/{id}/attachment/add', name: 'app_task_attachment_add', methods: ['POST'])]
    public function addAttachment(Request $request, int $id, SluggerInterface $slugger): JsonResponse
    {
        $task = $this->entityManager->getRepository(Tasks::class)->find($id);

        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        /** @var User $user */
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'Aucun fichier n\'a été téléchargé'], 400);
        }

        $description = $request->request->get('description');
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move(
                $this->getParameter('task_attachments_directory'),
                $newFilename
            );
        } catch (FileException $e) {
            return $this->json(['error' => 'Une erreur s\'est produite lors du téléchargement du fichier'], 500);
        }

        $attachment = new TasksAttachments();
        $attachment->setTask($task)
                  ->setUploadedBy($user)
                  ->setName($newFilename)
                  ->setOriginalName($file->getClientOriginalName())
                  ->setMimeType($file->getMimeType())
                  ->setFileSize($file->getSize())
                  ->setDescription($description);

        $this->entityManager->persist($attachment);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'attachment' => [
                'id' => $attachment->getId(),
                'originalName' => $attachment->getOriginalName(),
                'fileName' => $attachment->getFileName(),
                'mimeType' => $attachment->getMimeType(),
                'fileSize' => $attachment->getFileSize(),
                'description' => $attachment->getDescription(),
                'createdAt' => $attachment->getCreatedAt()->format('d/m/Y H:i'),
                'user' => [
                    'id' => $user->getId(),
                    'fullName' => $user->getFullName(),
                    'avatar' => $user->getUserAvatar()
                ]
            ]
        ]);
    }

    #[Route('/task/{id}/attachment/{attachmentId}/delete', name: 'app_task_attachment_delete', methods: ['POST'])]
    public function deleteAttachment(int $id, int $attachmentId): JsonResponse
    {
        $attachment = $this->entityManager->getRepository(TasksAttachments::class)->find($attachmentId);

        if (!$attachment) {
            return $this->json(['error' => 'Pièce jointe non trouvée'], 404);
        }

        if ($attachment->getTask()->getId() !== $id) {
            return $this->json(['error' => 'La pièce jointe n\'appartient pas à cette tâche'], 400);
        }

        /** @var User $user */
        $user = $this->security->getUser();
        if (!$user || ($attachment->getUploadedBy()->getId() !== $user->getId() && !in_array('ROLE_ADMIN', $user->getRoles()))) {
            return $this->json(['error' => 'Vous n\'avez pas les droits pour supprimer cette pièce jointe'], 403);
        }

        // Supprimer le fichier physique
        $filePath = $this->getParameter('task_attachments_directory') . '/' . $attachment->getFileName();
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->entityManager->remove($attachment);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/task/{id}/attachment/{attachmentId}/download', name: 'app_task_attachment_download', methods: ['GET'])]
    public function downloadAttachment(int $id, int $attachmentId): Response
    {
        $attachment = $this->entityManager->getRepository(TasksAttachments::class)->find($attachmentId);

        if (!$attachment) {
            throw $this->createNotFoundException('Pièce jointe non trouvée');
        }

        if ($attachment->getTask()->getId() !== $id) {
            throw $this->createNotFoundException('La pièce jointe n\'appartient pas à cette tâche');
        }

        $filePath = $this->getParameter('task_attachments_directory') . '/' . $attachment->getFileName();
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Le fichier n\'existe pas');
        }

        return $this->file($filePath, $attachment->getOriginalName());
    }
}