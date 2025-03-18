<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Tasks;
use App\Enum\TaskStatus;
use App\Form\ProjectType;
use App\Form\TaskType;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\PermissionService;

class ProjectController extends AbstractController
{
    private PermissionService $permissionService;
    
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }
    
    #[Route('/management-project/{id<\d+>?}', name: 'app_management_project')]
    public function managementProject(
        ProjectRepository $projectRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        ?string $id = null
    ): Response {
        // Vérifier si l'utilisateur peut éditer des projets
        if (!$this->permissionService->canPerform('edit', 'project')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à gérer les projets.');
        }
        
        $id = $id !== null ? (int) $id : null;
        
        // Création d'un nouveau projet
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
    
        // Création d'une nouvelle tâche
        $task = new Tasks();
        $task->setTaskRank(1); // Définir la valeur par défaut du taskRank
        $task->setTaskColumnRank(1); // Définir la valeur par défaut du taskColumnRank
        
        // Initialiser l'utilisateur de mise à jour
        $currentUser = $this->getUser();
        if ($currentUser) {
            $task->setTaskUpdatedBy($currentUser);
        }
        
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
            try {
                if (!$project->getProjectStartDate()) {
                    $project->setProjectStartDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                }
                $project->setProjectManager($this->getUser());
        
                $entityManager->persist($project);
                $entityManager->flush();
        
                $this->addFlash('success', 'Projet créé avec succès !');
                return $this->redirectToRoute('app_management_project');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du projet: ' . $e->getMessage());
            }
        } else if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Le formulaire contient des erreurs.');
        }
    
        if ($taskForm->isSubmitted() && $taskForm->isValid()) {
            try {
                if (!$this->permissionService->canPerform('create', 'task')) {
                    throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à créer des tâches.');
                }
                
                if (!$task->getTaskStartDate()) {
                    $task->setTaskStartDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                }
        
                if ($currentProject) {
                    $task->setTaskProject($currentProject);
                    
                    // S'assurer que l'utilisateur de mise à jour est défini
                    if (!$task->getTaskUpdatedBy() && $currentUser) {
                        $task->setTaskUpdatedBy($currentUser);
                    }
                    
                    $entityManager->persist($task);
                    $entityManager->flush();
                    
                    $this->updateTaskRank($entityManager, $currentProject);
                } else {
                    $this->addFlash('error', 'Aucun projet sélectionné pour cette tâche.');
                    return $this->redirectToRoute('app_management_project');
                }
        
                $this->addFlash('success', 'Tâche ajoutée avec succès !');
                return $this->redirectToRoute('app_management_project', ['id' => $currentProject->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création de la tâche: ' . $e->getMessage());
            }
        } else if ($taskForm->isSubmitted() && !$taskForm->isValid()) {
            $this->addFlash('error', 'Le formulaire de tâche contient des erreurs.');
        }
    
        return $this->render('project/management_project.html.twig', [
            'projects' => $projects,
            'current_project' => $currentProject,
            'form' => $form->createView(),
            'taskForm' => $taskForm->createView(),
            'tasks' => $currentProject ? $currentProject->getTasks() : [],
            'permissions' => [
                'canCreateTask' => $this->permissionService->canPerform('create', 'task'),
                'canEditTask' => $this->permissionService->canPerform('edit', 'task'),
                'canDeleteTask' => $this->permissionService->canPerform('delete', 'task'),
                'canAssignTask' => $this->permissionService->canPerform('assign', 'task'),
            ],
        ]);
    }

    private function updateTaskRank(EntityManagerInterface $entityManager, Project $project): void
    {
        $tasks = $project->getTasks();
        $rank = 1;
        foreach ($tasks as $task) {
            $task->setTaskRank($rank);
            $rank++; // Incrémenter le rang pour la prochaine tâche
        }
        $entityManager->flush();
    }

    #[Route('/management-project/delete/{id}', name: 'app_project_delete', methods: ['POST'])]
    public function deleteProject(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        // Vérifier le jeton CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-project', $submittedToken)) {
            $this->addFlash('error', 'Jeton CSRF invalide lors de la suppression du projet.');
            return $this->redirectToRoute('app_management_project');
        }
        
        // Vérifier si l'utilisateur peut supprimer des projets
        if (!$this->permissionService->canPerform('delete', 'project')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer des projets.');
        }
        
        // Vérifier si le projet appartient à l'utilisateur connecté
        if ($project->getProjectManager() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
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
        // Vérifier si l'utilisateur peut modifier des tâches
        if (!$this->permissionService->canPerform('edit', 'task')) {
            return $this->json(['error' => 'Vous n\'êtes pas autorisé à modifier des tâches.'], Response::HTTP_FORBIDDEN);
        }
        
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
        $task->setTaskStatus(TaskStatus::from($content['newStatus']));
        $task->setTaskUpdatedBy($this->getUser()); // Mettre à jour l'utilisateur qui modifie la tâche
        $entityManager->persist($task);
        $entityManager->flush();

        return $this->json(['success' => 'Statut de la tâche mis à jour'], Response::HTTP_OK);
    }
    
    #[Route('/management-project/update-task-position', name: 'app_update_task_position', methods: ['POST'])]
    public function updateTaskPosition(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur peut modifier des tâches
        if (!$this->permissionService->canPerform('edit', 'task')) {
            return $this->json(['error' => 'Vous n\'êtes pas autorisé à modifier des tâches.'], Response::HTTP_FORBIDDEN);
        }
        
        $content = json_decode($request->getContent(), true);
    
        if (!isset($content['taskId'], $content['newColumn'], $content['taskOrder'])) {
            return $this->json(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }
    
        $columnRanks = [
            'a-faire' => 1,
            'bloque' => 2,
            'en-cours' => 3,
            'terminee' => 4,
        ];
    
        if (!isset($columnRanks[$content['newColumn']])) {
            return $this->json(['error' => 'Colonne invalide'], Response::HTTP_BAD_REQUEST);
        }
    
        $task = $entityManager->getRepository(Tasks::class)->find($content['taskId']);
        if (!$task) {
            return $this->json(['error' => 'Tâche introuvable'], Response::HTTP_NOT_FOUND);
        }
    
        $task->setTaskColumnRank($columnRanks[$content['newColumn']]);
        $task->setTaskUpdatedBy($this->getUser()); // Mettre à jour l'utilisateur qui modifie la tâche
        
        foreach ($content['taskOrder'] as $taskData) {
            $taskToUpdate = $entityManager->getRepository(Tasks::class)->find($taskData['id']);
            if ($taskToUpdate) {
                $taskToUpdate->setTaskRank($taskData['rank']);
                $taskToUpdate->setTaskUpdatedBy($this->getUser()); // Mettre à jour l'utilisateur
                $entityManager->persist($taskToUpdate);
            }
        }
    
        $entityManager->flush();
    
        return $this->json(['success' => 'Position et colonne des tâches mises à jour'], Response::HTTP_OK);
    }

    #[Route('/projects', name: 'app_projects_list')]
    public function projectsList(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier si l'utilisateur peut voir les projets
        if (!$this->permissionService->canPerform('view', 'project')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir les projets.');
        }
        
        // Récupérer les filtres de la requête
        $statusFilter = $request->query->get('status');
        $customerFilter = $request->query->get('customer');
        
        // Créer une requête de base pour les projets
        $queryBuilder = $entityManager->getRepository(Project::class)->createQueryBuilder('p')
            ->leftJoin('p.projectCustomer', 'c')
            ->leftJoin('p.projectManager', 'u');
            
        // Appliquer les filtres si nécessaire
        if ($statusFilter) {
            $queryBuilder->andWhere('p.projectStatus = :status')
                ->setParameter('status', $statusFilter);
        }
        
        if ($customerFilter) {
            $queryBuilder->andWhere('c.id = :customerId')
                ->setParameter('customerId', $customerFilter);
        }
        
        // Limiter aux projets que l'utilisateur peut voir selon son rôle
        $currentUser = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_RESPONSABLE')) {
            // Les chefs de projet ne voient que leurs projets
            if ($currentUser instanceof \App\Entity\User) {
                $queryBuilder->andWhere('p.projectManager = :userId')
                    ->setParameter('userId', $currentUser->getId());
            }
        }
        
        // Trier les projets par date de début (les plus récents d'abord)
        $queryBuilder->orderBy('p.projectStartDate', 'DESC');
        
        // Exécuter la requête
        $projects = $queryBuilder->getQuery()->getResult();
        
        // Récupérer la liste des clients pour le filtre
        $customers = $entityManager->getRepository(\App\Entity\Customers::class)->findAll();
        
        // Récupérer la liste des statuts pour le filtre
        $statuses = \App\Enum\ProjectStatus::cases();
        
        return $this->render('project/list.html.twig', [
            'projects' => $projects,
            'customers' => $customers,
            'statuses' => $statuses,
            'currentStatus' => $statusFilter,
            'currentCustomer' => $customerFilter,
            'permissions' => [
                'canCreateProject' => $this->permissionService->canPerform('create', 'project'),
                'canEditProject' => $this->permissionService->canPerform('edit', 'project'),
                'canDeleteProject' => $this->permissionService->canPerform('delete', 'project'),
            ],
        ]);
    }
}
