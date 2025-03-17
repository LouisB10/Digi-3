<?php

namespace App\Controller\Parameter;

use App\Entity\Customers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\PermissionService;

#[Route('/parameter/customers')]
#[IsGranted('ROLE_USER')]
class CustomersController extends AbstractController
{
    private PermissionService $permissionService;
    
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    #[Route('/', name: 'app_parameter_customers_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur peut voir les clients
        if (!$this->permissionService->canPerform('view', 'customer')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir les clients.');
        }
        
        // Récupérer l'utilisateur courant
        $currentUser = $this->getUser();
        
        // Récupérer tous les clients
        $customers = $entityManager->getRepository(Customers::class)->findAll();
        
        // Préparer les permissions pour chaque client
        $customerPermissions = [];
        foreach ($customers as $customer) {
            $customerPermissions[$customer->getId()] = [
                'canEdit' => $this->permissionService->canEditCustomer($customer),
                'canDelete' => $this->permissionService->canDeleteCustomer($customer)
            ];
        }
        
        // Récupérer les permissions de l'utilisateur
        $permissions = [
            'canViewUsers' => $this->permissionService->canPerform('view', 'user'),
            'canEditUsers' => $this->permissionService->canPerform('edit', 'user'),
            'canViewProjects' => $this->permissionService->canPerform('view', 'project'),
            'canEditProjects' => $this->permissionService->canPerform('edit', 'project'),
            'canViewCustomers' => $this->permissionService->canPerform('view', 'customer'),
            'canEditCustomers' => $this->permissionService->canPerform('edit', 'customer'),
            'canViewParameters' => $this->permissionService->canPerform('view', 'parameter'),
            'canEditParameters' => $this->permissionService->canPerform('edit', 'parameter'),
        ];

        return $this->render('parameter/customers/index.html.twig', [
            'user' => $currentUser,     // Pour le template header
            'customers' => $customers,  // Pour la liste des clients
            'permissions' => $permissions,
            'customerPermissions' => $customerPermissions // Permissions spécifiques à chaque client
        ]);
    }

    #[Route('/create', name: 'app_parameter_customers_create', methods: ['POST'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérifier si l'utilisateur peut créer des clients
        if (!$this->permissionService->canPerform('create', 'customer')) {
            return $this->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à créer des clients.'
            ], Response::HTTP_FORBIDDEN);
        }
        
        // Récupérer les données du formulaire
        $data = $request->request->all();
        
        // Vérifier si l'email existe déjà (en utilisant le champ approprié)
        $existingCustomer = $entityManager->getRepository(Customers::class)->findOneBy(['customerReference' => $data['reference']]);
        if ($existingCustomer) {
            return $this->json([
                'success' => false,
                'message' => 'Cette référence client est déjà utilisée.'
            ]);
        }
        
        // Créer un nouveau client
        $customer = new Customers();
        $customer->setCustomerName($data['name']);
        $customer->setCustomerAddressStreet($data['address'] ?? null);
        $customer->setCustomerAddressZipcode($data['zipcode'] ?? null);
        $customer->setCustomerAddressCity($data['city'] ?? null);
        $customer->setCustomerAddressCountry($data['country'] ?? null);
        $customer->setCustomerVAT($data['vat'] ?? null);
        $customer->setCustomerSIREN($data['siren'] ?? null);
        $customer->setCustomerReference($data['reference'] ?? null);
        
        // Définir l'utilisateur qui a fait la mise à jour
        $customer->setCustomerUpdatedBy($this->getUser());
        $customer->setCustomerUpdatedAt(new \DateTime());
        
        try {
            $entityManager->persist($customer);
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Client créé avec succès.'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création du client: ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/{id}/edit', name: 'app_parameter_customers_edit', methods: ['GET'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function edit(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $customer = $entityManager->getRepository(Customers::class)->find($id);
        
        if (!$customer) {
            return $this->json([
                'success' => false,
                'message' => 'Client non trouvé.'
            ], 404);
        }
        
        return $this->json([
            'success' => true,
            'customer' => [
                'id' => $customer->getId(),
                'name' => $customer->getCustomerName(),
                'address' => $customer->getCustomerAddressStreet(),
                'zipcode' => $customer->getCustomerAddressZipcode(),
                'city' => $customer->getCustomerAddressCity(),
                'country' => $customer->getCustomerAddressCountry(),
                'vat' => $customer->getCustomerVAT(),
                'siren' => $customer->getCustomerSIREN(),
                'reference' => $customer->getCustomerReference()
            ]
        ]);
    }

    #[Route('/{id}/update', name: 'app_parameter_customers_update', methods: ['POST'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function update(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $customer = $entityManager->getRepository(Customers::class)->find($id);
        
        if (!$customer) {
            return $this->json([
                'success' => false,
                'message' => 'Client non trouvé.'
            ], 404);
        }
        
        // Récupérer les données du formulaire
        $data = $request->request->all();
        
        // Vérifier si la référence existe déjà pour un autre client
        $existingCustomer = $entityManager->getRepository(Customers::class)->findOneBy(['customerReference' => $data['reference']]);
        if ($existingCustomer && $existingCustomer->getId() !== $customer->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Cette référence client est déjà utilisée.'
            ]);
        }
        
        // Mettre à jour les informations du client
        $customer->setCustomerName($data['name']);
        $customer->setCustomerAddressStreet($data['address'] ?? null);
        $customer->setCustomerAddressZipcode($data['zipcode'] ?? null);
        $customer->setCustomerAddressCity($data['city'] ?? null);
        $customer->setCustomerAddressCountry($data['country'] ?? null);
        $customer->setCustomerVAT($data['vat'] ?? null);
        $customer->setCustomerSIREN($data['siren'] ?? null);
        $customer->setCustomerReference($data['reference'] ?? null);
        
        // Mettre à jour les informations de mise à jour
        $customer->setCustomerUpdatedBy($this->getUser());
        $customer->setCustomerUpdatedAt(new \DateTime());
        
        try {
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Client mis à jour avec succès.'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour du client: ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/{id}/delete', name: 'app_parameter_customers_delete', methods: ['POST'])]
    #[IsGranted('ROLE_RESPONSABLE')]
    public function delete(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $customer = $entityManager->getRepository(Customers::class)->find($id);
        
        if (!$customer) {
            return $this->json([
                'success' => false,
                'message' => 'Client non trouvé.'
            ], 404);
        }
        
        try {
            $entityManager->remove($customer);
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Client supprimé avec succès.'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la suppression du client: ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/search', name: 'app_parameter_customers_search', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $searchType = $request->query->get('type');
        $searchQuery = $request->query->get('query');
        
        if (empty($searchQuery)) {
            $customers = $entityManager->getRepository(Customers::class)->findAll();
        } else {
            $qb = $entityManager->getRepository(Customers::class)->createQueryBuilder('c');
            
            switch ($searchType) {
                case 'name':
                    $qb->where('c.customerName LIKE :query')
                       ->setParameter('query', '%' . $searchQuery . '%');
                    break;
                case 'reference':
                    $qb->where('c.customerReference LIKE :query')
                       ->setParameter('query', '%' . $searchQuery . '%');
                    break;
                case 'siren':
                    $qb->where('c.customerSIREN LIKE :query')
                       ->setParameter('query', '%' . $searchQuery . '%');
                    break;
                default:
                    $qb->where('c.customerName LIKE :query OR c.customerReference LIKE :query OR c.customerSIREN LIKE :query')
                       ->setParameter('query', '%' . $searchQuery . '%');
            }
            
            $customers = $qb->getQuery()->getResult();
        }
        
        $formattedCustomers = [];
        foreach ($customers as $customer) {
            $formattedCustomers[] = [
                'id' => $customer->getId(),
                'name' => $customer->getCustomerName(),
                'reference' => $customer->getCustomerReference(),
                'siren' => $customer->getCustomerSIREN(),
                'vat' => $customer->getCustomerVAT(),
                'address' => sprintf(
                    '%s, %s %s, %s',
                    $customer->getCustomerAddressStreet() ?: '',
                    $customer->getCustomerAddressZipcode() ?: '',
                    $customer->getCustomerAddressCity() ?: '',
                    $customer->getCustomerAddressCountry() ?: ''
                )
            ];
        }
        
        return $this->json([
            'success' => true,
            'customers' => $formattedCustomers
        ]);
    }
} 