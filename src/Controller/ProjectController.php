<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Tasks;
use App\Form\ProjectType;
use App\Form\TaskType;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProjectController extends AbstractController
{
    #[Route('/management-project/{id}', name: 'app_management_project')]
    public function managementProject(
        ProjectRepository $projectRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        ?int $id = null
    ): Response {
        // Création d'un nouveau projet
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
    
        // Création d'une nouvelle tâche
        $task = new Tasks();
        $taskForm = $this->createForm(TaskType::class, $task);
    
        // Traiter la soumission des formulaires
        $form->handleRequest($request);
        $taskForm->handleRequest($request);
    
        // Récupérer les projets de l'utilisateur connecté
        $projects = $projectRepository->findBy(['projectManager' => $this->getUser()]);
    
        // Identifier le projet courant (sélectionné)
        $currentProject = null;
        if ($id) {
            $currentProject = $projectRepository->find($id);
            if (!$currentProject || $currentProject->getProjectManager() !== $this->getUser()) {
                $this->addFlash('error', 'Projet introuvable ou non autorisé.');
                return $this->redirectToRoute('app_management_project');
            }
        }
    
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$project->getProjectStartDate()) {
                $project->setProjectStartDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            }
            $project->setProjectManager($this->getUser());
    
            $entityManager->persist($project);
            $entityManager->flush();
    
            $this->addFlash('success', 'Projet créé avec succès !');
            return $this->redirectToRoute('app_management_project');
        }
    
        if ($taskForm->isSubmitted() && $taskForm->isValid()) {
            if (!$task->getTaskStartDate()) {
                $task->setTaskStartDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            }
    
            if ($currentProject) {
                $task->setTaskProject($currentProject);
            } else {
                $this->addFlash('error', 'Aucun projet sélectionné pour cette tâche.');
                return $this->redirectToRoute('app_management_project');
            }
    
            $entityManager->persist($task);
            $entityManager->flush();
    
            $this->addFlash('success', 'Tâche ajoutée avec succès !');
            return $this->redirectToRoute('app_management_project', ['id' => $currentProject->getId()]);
        }
    
        return $this->render('project/management_project.html.twig', [
            'projects' => $projects,
            'current_project' => $currentProject,
            'form' => $form->createView(),
            'taskForm' => $taskForm->createView(),
            'tasks' => $currentProject ? $currentProject->getTasks() : [],
        ]);
    }

    #[Route('/management-project/delete/{id}', name: 'app_project_delete', methods: ['POST'])]
    public function deleteProject(Project $project, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si le projet appartient à l'utilisateur connecté
        if ($project->getProjectManager() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer ce projet.');
        }

        $entityManager->remove($project);
        $entityManager->flush();

        $this->addFlash('success', 'Projet supprimé avec succès !');
        return $this->redirectToRoute('app_management_project');
    }

    #[Route('/management-project/update-task-status', name: 'app_update_task_status', methods: ['POST'])]
    public function updateTaskStatus(Request $request, EntityManagerInterface $entityManager): Response
    {
        $content = json_decode($request->getContent(), true);

        // Vérifier que les données nécessaires sont fournies
        if (!isset($content['taskId'], $content['newStatus'])) {
            return $this->json(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer la tâche par son ID
        $task = $entityManager->getRepository(Tasks::class)->find($content['taskId']);
        if (!$task) {
            return $this->json(['error' => 'Tâche introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Mettre à jour le statut de la tâche
        $task->setTaskStatus($content['newStatus']);
        $entityManager->persist($task);
        $entityManager->flush();

        return $this->json(['success' => 'Statut de la tâche mis à jour'], Response::HTTP_OK);
    }
}
