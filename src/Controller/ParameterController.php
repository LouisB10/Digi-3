<?php

namespace App\Controller;

use App\Entity\GeneralSettings;
use App\Form\SearchFormType;
use App\Form\AppFormParameterType;
use App\Entity\Parameter;
use App\Form\GeneralSettingsType;
use App\Form\UpdateAccountType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

class ParameterController extends AbstractController
{
    #[Route('/parameter/app_configuration', name: 'app_parameter_app_configuration', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $createForm = $this->createForm(AppFormParameterType::class);
        $searchForm = $this->createForm(SearchFormType::class);

        // $showAll = $searchForm->get('showAll')->getData();
        // $currentDateTime = new \DateTime();
        // // Construire la requête QueryBuilder
        // $parameters = $entityManager->getRepository(Parameter::class)->createQueryBuilder('p');

        // // Filtrer les enregistrements actifs si "showAll" n'est pas coché
        // $parameters->andWhere('p.paramDateFrom <= :currentDate')
        //     ->andWhere('p.paramDateTo >= :currentDate')
        //     ->setParameter('currentDate', $currentDateTime)
        //     ->getQuery()
        //     ->getResult();

        // Récupérer la date actuelle
        $currentDateTime = new \DateTime();
        $parameters = $entityManager->getRepository(Parameter::class)
            ->createQueryBuilder('p')
            ->where('p.paramDateFrom <= :currentDate')
            ->andWhere('p.paramDateTo >= :currentDate')
            ->setParameter('currentDate', $currentDateTime)
            ->getQuery()
            ->getResult();

        return $this->render('parameter/config.html.twig', [
            'searchForm' => $searchForm->createView(), // Passer le formulaire à la vue
            'createForm' => $createForm->createView(),
            'parameters' => $parameters
        ]);
    }


    #[Route('/parameter/search', name: 'app_ajax_search', methods: ['POST'])]
    public function search(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Créer le formulaire
        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);

        // Initialiser la variable des paramètres
        $parameters = [];
        $currentDateTime = new \DateTime();

        // Vérifier si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les données du formulaire
            $searchTerm = $form->get('searchTerm')->getData();
            $showAll = $form->get('showAll')->getData();
            $dateSelect = $form->get('dateSelect')->getData();

            // Construire la requête QueryBuilder
            $qb = $entityManager->getRepository(Parameter::class)->createQueryBuilder('p');

            // Appliquer les filtres selon le terme de recherche
            if ($searchTerm) {
                $qb->andWhere('p.paramKey LIKE :searchTerm')
                    ->setParameter('searchTerm', '%' . $searchTerm . '%');
            }

            // Filtrer les enregistrements actifs si "showAll" n'est pas coché
            if (!$showAll) {
                $qb->andWhere('p.paramDateFrom <= :currentDate')
                    ->andWhere('p.paramDateTo >= :currentDate')
                    ->setParameter('currentDate', $currentDateTime);
            }

            // Filtrer par la date sélectionnée si une date est fournie
            if ($dateSelect) {
                $qb->andWhere('p.paramDateFrom <= :dateSelect')
                    ->andWhere('p.paramDateTo >= :dateSelect')
                    ->setParameter('dateSelect', $dateSelect);
            }
            // Exécuter la requête pour obtenir les résultats
            $parameters = $qb->getQuery()->getResult();
        }

        // Générer le HTML avec Twig, même si le tableau est vide
        $html = $this->renderView('parameter/tableau_parameter.html.twig', [
            'parameters' => $parameters,
        ]);

        // Retourner la réponse JSON contenant les résultats et le HTML généré
        return $this->json([
            'parameters' => $parameters, // Retourne les paramètres trouvés
            'html' => $html, // HTML à afficher dans le tableau
        ]);
    }

    #[Route('/parameter/delete/{id}', name: 'app_parameter_delete', methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        // Récupérer le paramètre à supprimer
        $parameter = $entityManager->getRepository(Parameter::class)->find($id);

        if (!$parameter) {
            return $this->json(['success' => false, 'message' => 'Paramètre non trouvé.'], 404);
        }
        $currentDateTime = new \DateTime();
        if ($parameter->getParamDateTo() < $currentDateTime) {
            return $this->json(['success' => false, 'message' => 'Impossible de supprimer un paramètre non-actif.'], 403);
        }
        $parameter->setParamDateTo($currentDateTime);

        try {
            $entityManager->persist($parameter);
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Erreur lors de la mise à jour.'], 500);
        }

        // Récupérer tous les paramètres après la mise à jour
        $allParameters = $entityManager->getRepository(Parameter::class)->findAll();

        // Générer le HTML pour le tableau avec les paramètres restants
        $html = $this->renderView('parameter/tableau_parameter.html.twig', [
            'parameters' => $allParameters,
        ]);

        return $this->json([
            'success' => true,
            'html' => $html, // Retourne le HTML mis à jour
            'parameters' => $allParameters, // Retourne tous les paramètres restants
        ]);
    }

    #[Route('/parameter/create', name: 'app_parameter_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Créer une nouvelle instance de Parameter
        $parameter = new Parameter();


        // Créer le formulaire et gérer la requête
        $form = $this->createForm(AppFormParameterType::class, $parameter);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Récupérer les valeurs des dates
                $paramDateFrom = $parameter->getParamDateFrom();
                $paramDateTo = $parameter->getParamDateTo();

                // Vérification de la cohérence des dates
                if ($paramDateFrom instanceof \DateTimeInterface && $paramDateTo instanceof \DateTimeInterface) {
                    // Vérifier que la date de début est avant la date de fin
                    if ($paramDateFrom <= $paramDateTo) {
                        // Persister le nouveau paramètre dans la base de données
                        $entityManager->persist($parameter);
                        $entityManager->flush();

                        // Recharger les paramètres mis à jour
                        $parameters = $entityManager->getRepository(Parameter::class)->findAll();

                        // Générer le HTML mis à jour pour le tableau des paramètres
                        $html = $this->renderView('parameter/tableau_parameter.html.twig', [
                            'parameters' => $parameters,
                        ]);

                        // Renvoyer la réponse avec le paramètre créé
                        return $this->json([
                            'success' => true,
                            'parameter' => [
                                'paramKey' => $parameter->getParamKey(),
                                'paramValue' => $parameter->getParamValue(),
                                'paramDateFrom' => $paramDateFrom->format('Y-m-d H:i'), // Formatage des dates
                                'paramDateTo' => $paramDateTo->format('Y-m-d H:i'),
                            ],
                            'html' => $html, // Renvoie le HTML mis à jour
                        ]);
                    } else {
                        // Si la date de début est postérieure à la date de fin
                        return $this->json([
                            'success' => false,
                            'message' => 'La date de début ne peut pas être après la date de fin.',
                        ]);
                    }
                } else {
                    // Si les dates ne sont pas valides
                    return $this->json([
                        'success' => false,
                        'message' => 'Les dates ne sont pas valides.',
                    ]);
                }
            } else {
                // Collecter et retourner les erreurs de validation
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }

                return $this->json([
                    'success' => false,
                    'message' => 'Formulaire invalide.',
                    'errors' => $errors, // Renvoie les détails des erreurs
                ]);
            }
        }

        // Si le formulaire n'est pas soumis correctement
        return $this->json([
            'success' => false,
            'message' => 'Formulaire non soumis correctement.',
        ]);
    }

    #[Route('/parameter/generaux', name: 'app_parameter_generaux')]
    public function generaux(): Response
    {
        $user = $this->getUser(); // Récupérer l'utilisateur connecté
        return $this->render('parameter/index.html.twig', [
            'user' => $user, // Passer l'utilisateur au template
        ]);
    }    

    #[Route('/parameter/about', name: 'app_parameter_about')]
    public function about(): Response
    {
        return $this->render('parameter/about.html.twig'); // Assurez-vous de créer ce fichier Twig
    }

    #[Route('/parameter/update_account', name: 'app_update_account', methods: ['GET', 'POST'])]
    public function updateAccount(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        // Si aucun utilisateur n'est connecté, redirigez vers la page de connexion ou montrez un message d'erreur
        if (!$user) {
            return $this->redirectToRoute('app_login'); // Remplacez 'app_login' par le nom de votre route de connexion
        }

        // Créer le formulaire de mise à jour
        $form = $this->createForm(UpdateAccountType::class, $user);
        $form->handleRequest($request);

        // Vérifier si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour l'utilisateur dans la base de données
            $entityManager->persist($user);
            $entityManager->flush();

            // Rediriger vers une page de succès ou la page d'accueil après la mise à jour
            return $this->redirectToRoute('app_parameter_generaux'); // Remplacez par votre route de redirection
        }

        return $this->render('parameter/update_account.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/parameter/update_general_settings', name: 'app_update_general_settings', methods: ['GET', 'POST'])]
    public function updateGeneralSettings(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer les paramètres généraux (assurez-vous d'avoir une méthode pour cela)
        $generalSettings = $entityManager->getRepository(GeneralSettings::class)->find(1); // Adaptez selon votre logique

        // Si aucun paramètre général n'est trouvé, vous pouvez créer une nouvelle instance
        if (!$generalSettings) {
            $generalSettings = new GeneralSettings();
        }

        // Créer le formulaire pour les paramètres généraux
        $form = $this->createForm(GeneralSettingsType::class, $generalSettings);
        $form->handleRequest($request);

        // Vérifiez si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Persistez les changements
            $entityManager->persist($generalSettings);
            $entityManager->flush();

            // Rediriger vers une page de succès ou une autre page après la mise à jour
            return $this->redirectToRoute('app_parameter_generaux'); // Remplacez par votre route de redirection
        }

        // Rendre la vue avec le formulaire
        return $this->render('parameter/update_general_settings.html.twig', [
            'form' => $form->createView(),
            'generalSettings' => $generalSettings,
        ]);
    }

}
