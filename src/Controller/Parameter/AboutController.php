<?php

namespace App\Controller\Parameter;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/parameter/about')]
#[IsGranted('ROLE_USER')]
class AboutController extends AbstractController
{
    #[Route('/', name: 'app_parameter_about_index')]
    public function index(): Response
    {
        // Récupérer l'utilisateur courant
        $user = $this->getUser();
        
        // Informations sur l'application
        $appVersion = '1.0.0';
        $appUpdateDate = new \DateTime('2023-03-15');
        
        return $this->render('parameter/about/index.html.twig', [
            'user' => $user,
            'app_version' => $appVersion,
            'app_update_date' => $appUpdateDate,
        ]);
    }
} 