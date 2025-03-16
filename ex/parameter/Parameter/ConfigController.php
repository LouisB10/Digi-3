<?php

namespace App\Controller\Parameter;

use App\Entity\Parameters;
use App\Form\Parameter\SearchFormType;
use App\Form\Parameter\AppFormParameterType;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/parameter/app_configuration')]
class ConfigController extends AbstractController
{
    private const PARAMETER_CATEGORIES = [
        'project' => [
            'PROJECT_STATUS_',
            'TASK_STATUS_',
            'TASK_PRIORITY_',
            'TASK_COMPLEXITY_'
        ],
        'resources' => [
            'RESOURCE_TYPE_',
            'RESOURCE_RATE_'
        ],
        'security' => [
            'SECURITY_',
            'PASSWORD_',
            'AUTH_'
        ],
        'performance' => [
            'CACHE_',
            'OPTIMIZATION_',
            'LIMIT_'
        ],
        'notifications' => [
            'NOTIFICATION_',
            'ALERT_',
            'EMAIL_'
        ]
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private PermissionService $permissionService
    ) {}

    #[Route('/', name: 'app_parameter_app_configuration', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {   
        if (!$this->permissionService->canAccessConfiguration()) {
            throw $this->createAccessDeniedException('Accès non autorisé à la configuration');
        }

        $user = $this->security->getUser();
        $createForm = $this->createForm(AppFormParameterType::class);
        $searchForm = $this->createForm(SearchFormType::class);

        $currentDateTime = new \DateTime();
        $allParameters = $this->entityManager->getRepository(Parameters::class)
            ->findActiveParameters($currentDateTime);

        // Grouper les paramètres par catégorie
        $parameters = [];
        foreach (self::PARAMETER_CATEGORIES as $category => $prefixes) {
            $parameters[$category] = array_filter($allParameters, function($param) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    if (str_starts_with($param->getParamKey(), $prefix)) {
                        return true;
                    }
                }
                return false;
            });
        }

        return $this->render('parameter/config.html.twig', [
            'searchForm' => $searchForm->createView(),
            'createForm' => $createForm->createView(),
            'projectParameters' => $parameters['project'],
            'resourceParameters' => $parameters['resources'],
            'securityParameters' => $parameters['security'],
            'performanceParameters' => $parameters['performance'],
            'notificationsParameters' => $parameters['notifications'],
            'user' => $user,
            'canEdit' => $this->permissionService->canEditConfiguration()
        ]);
    }

    #[Route('/search', name: 'app_ajax_search', methods: ['POST'])]
    public function search(Request $request): Response
    {
        if (!$this->permissionService->canViewConfiguration()) {
            throw $this->createAccessDeniedException('Accès non autorisé à la recherche de configuration');
        }

        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);
        $parameters = [];
        $currentDateTime = new \DateTime();
        $category = $request->request->get('category', '');

        if ($form->isSubmitted() && $form->isValid()) {
            $searchTerm = $form->get('searchTerm')->getData();
            $showAll = $form->get('showAll')->getData();
            $dateSelect = $form->get('dateSelect')->getData();

            $qb = $this->entityManager->getRepository(Parameters::class)->createQueryBuilder('p');

            if ($searchTerm) {
                $qb->andWhere('p.paramKey LIKE :searchTerm')
                    ->setParameter('searchTerm', '%' . $searchTerm . '%');
            }

            if ($category && isset(self::PARAMETER_CATEGORIES[$category])) {
                $orX = $qb->expr()->orX();
                foreach (self::PARAMETER_CATEGORIES[$category] as $prefix) {
                    $orX->add($qb->expr()->like('p.paramKey', ':prefix_' . md5($prefix)));
                    $qb->setParameter('prefix_' . md5($prefix), $prefix . '%');
                }
                $qb->andWhere($orX);
            }

            if (!$showAll) {
                $qb->andWhere('p.paramDateFrom <= :currentDate')
                    ->andWhere('p.paramDateTo >= :currentDate')
                    ->setParameter('currentDate', $currentDateTime);
            }

            if ($dateSelect) {
                $qb->andWhere('p.paramDateFrom <= :dateSelect')
                    ->andWhere('p.paramDateTo >= :dateSelect')
                    ->setParameter('dateSelect', $dateSelect);
            }

            $parameters = $qb->getQuery()->getResult();
        }

        $html = $this->renderView('parameter/tableau_parameter.html.twig', [
            'parameters' => $parameters,
            'category' => $category
        ]);

        return $this->json([
            'parameters' => $parameters,
            'html' => $html,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_parameter_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        if (!$this->permissionService->canEditConfiguration()) {
            return $this->json(['success' => false, 'message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $parameter = $this->entityManager->getRepository(Parameters::class)->find($id);

        if (!$parameter) {
            return $this->json(['success' => false, 'message' => 'Paramètre non trouvé.'], 404);
        }

        $currentDateTime = new \DateTime();
        if ($parameter->getParamDateTo() < $currentDateTime) {
            return $this->json(['success' => false, 'message' => 'Impossible de supprimer un paramètre non-actif.'], 403);
        }

        $parameter->setParamDateTo($currentDateTime);

        try {
            $this->entityManager->persist($parameter);
            $this->entityManager->flush();

            // Récupérer la catégorie du paramètre
            $category = $this->getCategoryFromKey($parameter->getParamKey());
            $parameters = $this->getParametersByCategory($this->entityManager, $category);

            $html = $this->renderView('parameter/tableau_parameter.html.twig', [
                'parameters' => $parameters,
                'category' => $category
            ]);

            return $this->json([
                'success' => true,
                'html' => $html,
                'category' => $category
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Erreur lors de la mise à jour.'], 500);
        }
    }

    #[Route('/create', name: 'app_parameter_create', methods: ['POST'])]
    public function create(
        Request $request,
        ValidatorInterface $validator
    ): JsonResponse {
        if (!$this->permissionService->canEditConfiguration()) {
            return $this->json([
                'success' => false,
                'message' => 'Accès non autorisé à la création de paramètres'
            ], Response::HTTP_FORBIDDEN);
        }

        $category = $request->request->get('paramCategory');
        if (!isset(self::PARAMETER_CATEGORIES[$category])) {
            return $this->json([
                'success' => false,
                'message' => 'Catégorie invalide'
            ], 400);
        }

        $parameter = new Parameters();
        $form = $this->createForm(AppFormParameterType::class, $parameter);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Validation manuelle
            $errors = $validator->validate($parameter);
            
            if (count($errors) === 0) {
                try {
                    // Ajouter le préfixe approprié à la clé
                    $key = $parameter->getParamKey();
                    $prefixes = self::PARAMETER_CATEGORIES[$category];
                    $hasValidPrefix = false;
                    foreach ($prefixes as $prefix) {
                        if (str_starts_with($key, $prefix)) {
                            $hasValidPrefix = true;
                            break;
                        }
                    }
                    if (!$hasValidPrefix) {
                        $parameter->setParamKey($prefixes[0] . $key);
                    }

                    $this->entityManager->persist($parameter);
                    $this->entityManager->flush();

                    return $this->json([
                        'success' => true,
                        'message' => 'Paramètre créé avec succès',
                        'parameter' => [
                            'key' => $parameter->getParamKey(),
                            'value' => $parameter->getParamValue()
                        ]
                    ]);
                } catch (\Exception $e) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Erreur lors de la création du paramètre',
                        'error' => $e->getMessage()
                    ], 500);
                }
            } else {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                return $this->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $errorMessages
                ], 400);
            }
        }

        return $this->json([
            'success' => false,
            'message' => 'Formulaire non valide'
        ], 400);
    }

    private function getCategoryFromKey(string $key): string
    {
        foreach (self::PARAMETER_CATEGORIES as $category => $prefixes) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    return $category;
                }
            }
        }
        return 'general';
    }

    private function getParametersByCategory(EntityManagerInterface $entityManager, string $category): array
    {
        if (!isset(self::PARAMETER_CATEGORIES[$category])) {
            return [];
        }

        $qb = $entityManager->getRepository(Parameters::class)->createQueryBuilder('p');
        $orX = $qb->expr()->orX();
        
        foreach (self::PARAMETER_CATEGORIES[$category] as $prefix) {
            $orX->add($qb->expr()->like('p.paramKey', ':prefix_' . md5($prefix)));
            $qb->setParameter('prefix_' . md5($prefix), $prefix . '%');
        }
        
        return $qb->andWhere($orX)->getQuery()->getResult();
    }
}
