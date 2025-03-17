<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;

class DashboardController extends AbstractController
{
    private LoggerInterface $logger;
    private PermissionService $permissionService;

    public function __construct(LoggerInterface $logger, PermissionService $permissionService)
    {
        $this->logger = $logger;
        $this->permissionService = $permissionService;
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser(); // Récupérer l'utilisateur connecté
        
        if (!$user) {
            $this->logger->error('Accès au dashboard sans utilisateur authentifié');
            return $this->redirectToRoute('app_auth');
        }
        
        $this->logger->info('Accès au dashboard', [
            'user_email' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ]);

        // Récupérer les permissions de l'utilisateur pour les afficher dans le dashboard
        $userPermissions = [
            'canViewUsers' => $this->permissionService->canPerform('view', 'user'),
            'canEditUsers' => $this->permissionService->canPerform('edit', 'user'),
            'canViewProjects' => $this->permissionService->canPerform('view', 'project'),
            'canEditProjects' => $this->permissionService->canPerform('edit', 'project'),
            'canViewCustomers' => $this->permissionService->canPerform('view', 'customer'),
            'canEditCustomers' => $this->permissionService->canPerform('edit', 'customer'),
            'canViewParameters' => $this->permissionService->canPerform('view', 'parameter'),
            'canEditParameters' => $this->permissionService->canPerform('edit', 'parameter'),
        ];
        
        // Récupérer les statistiques des projets
        $projectStats = $this->getProjectStats($entityManager, $user);
        
        // Récupérer les tâches assignées à l'utilisateur
        $assignedTasks = $this->getAssignedTasks($entityManager, $user);

        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
            'user' => $user,
            'permissions' => $userPermissions,
            'projectStats' => $projectStats,
            'assignedTasks' => $assignedTasks
        ]);
    }
    
    /**
     * Récupère les statistiques des projets pour l'utilisateur
     */
    private function getProjectStats(EntityManagerInterface $entityManager, $user): array
    {
        $stats = [
            'total' => 0,
            'inProgress' => 0,
            'completed' => 0,
            'onHold' => 0,
            'recent' => []
        ];
        
        // Requête de base pour les projets
        $queryBuilder = $entityManager->getRepository(\App\Entity\Project::class)->createQueryBuilder('p');
        
        // Si l'utilisateur n'est pas admin ou responsable, limiter aux projets qu'il gère
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_RESPONSABLE')) {
            $queryBuilder->andWhere('p.projectManager = :userId')
                ->setParameter('userId', $user->getId());
        }
        
        // Récupérer tous les projets
        $projects = $queryBuilder->getQuery()->getResult();
        
        // Calculer les statistiques
        $stats['total'] = count($projects);
        
        foreach ($projects as $project) {
            $status = $project->getProjectStatus();
            
            if ($status === \App\Enum\ProjectStatus::IN_PROGRESS) {
                $stats['inProgress']++;
            } elseif ($status === \App\Enum\ProjectStatus::COMPLETED) {
                $stats['completed']++;
            } elseif ($status === \App\Enum\ProjectStatus::ON_HOLD) {
                $stats['onHold']++;
            }
        }
        
        // Récupérer les projets récents (limités à 5)
        $recentProjects = $entityManager->getRepository(\App\Entity\Project::class)
            ->createQueryBuilder('p')
            ->orderBy('p.projectStartDate', 'DESC')
            ->setMaxResults(5);
            
        // Si l'utilisateur n'est pas admin ou responsable, limiter aux projets qu'il gère
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_RESPONSABLE')) {
            $recentProjects->andWhere('p.projectManager = :userId')
                ->setParameter('userId', $user->getId());
        }
        
        $stats['recent'] = $recentProjects->getQuery()->getResult();
        
        return $stats;
    }
    
    /**
     * Récupère les tâches assignées à l'utilisateur
     */
    private function getAssignedTasks(EntityManagerInterface $entityManager, $user): array
    {
        // Récupérer les tâches assignées à l'utilisateur
        return $entityManager->getRepository(\App\Entity\Tasks::class)
            ->createQueryBuilder('t')
            ->leftJoin('t.taskProject', 'p')
            ->andWhere('t.taskAssignedTo = :userId')
            ->andWhere('t.taskStatus != :completedStatus')
            ->setParameter('userId', $user->getId())
            ->setParameter('completedStatus', \App\Enum\TaskStatus::COMPLETED->value)
            ->orderBy('t.taskTargetDate', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}
