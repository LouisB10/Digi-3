<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use App\Service\PermissionService;

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
    public function index(): Response
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

        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
            'user' => $user,
            'permissions' => $userPermissions,
        ]);
    }
}
