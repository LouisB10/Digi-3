<?php

namespace App\Controller;

use App\Entity\AppEntityParameter;
use App\Form\AppFormParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ParameterController extends AbstractController
{
    #[Route('/parameter', name: 'app_parameter_index', methods: ['GET','POST'])]
    public function index(Request $request,EntityManagerInterface $entityManager): Response
    {
        $parameters = $entityManager
            ->getRepository(AppEntityParameter::class)
            ->findAll(
            // ->findBy(['dateFin' => null] // Ou une condition plus complexe pour gérer les dates
        );

         // Créer un nouveau paramètre
         $parameter = new AppEntityParameter();
         $form = $this->createForm(AppFormParameterType::class, $parameter);
       
        // Traiter le formulaire pour l'ajout d'un paramètre
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($parameter);
            $entityManager->flush();
            return $this->redirectToRoute('app_parameter_index');
        }

            // Traiter les formulaires d'édition et de suppression
            foreach ($parameters as $existingParameter) {
                // Formulaire pour la modification
                $editForm = $this->createForm(AppFormParameterType::class, $existingParameter);
                $editForm->handleRequest($request);
                
                if ($editForm->isSubmitted() && $editForm->isValid()) {
                    $entityManager->flush();
                    return $this->redirectToRoute('app_parameter_index');
                }
                // Formulaire pour la suppression
                $deleteForm = $this->createFormBuilder()
                    ->setAction($this->generateUrl('app_parameter_index', ['id' => $existingParameter->getId()]))
                    ->setMethod('POST')
                    ->getForm();
                
                // Handle delete request
                if ($request->isMethod('POST') && $request->request->has($deleteForm->getName())) {
                    $entityManager->remove($existingParameter);
                    $entityManager->flush();
                    return $this->redirectToRoute('app_parameter_index');
                }

                $existingParameter->editForm = $editForm->createView();
                $existingParameter->deleteForm = $deleteForm->createView();
            }



        return $this->render('parameter/index.html.twig', [
            'parameters' => $parameters,  // Transmets les paramètres ici
            'form' => $form->createView(),
            // 'controller_name' => 'ParameterController',
        ]);
    }
}

