<?php

namespace App\Controller\Parameter;

use App\Entity\User;
use App\Service\PermissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/parameter')]
class AboutController extends AbstractController
{
    public function __construct(
        private Security $security,
        private PermissionService $permissionService
    ) {}

    #[Route('/about', name: 'app_parameter_about')]
    public function index(): Response
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_auth');
        }

        if (!$this->permissionService->hasPermission('view_own_profile')) {
            throw $this->createAccessDeniedException('Accès non autorisé à cette page.');
        }

        return $this->render('parameter/about.html.twig', [
            'user' => $user
        ]);
    }
}
