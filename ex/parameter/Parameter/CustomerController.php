<?php

namespace App\Controller\Parameter;

use App\Entity\Customers;
use App\Repository\CustomersRepository;
use App\Service\PermissionService;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

#[Route('/parameter/customers')]
class CustomerController extends AbstractCrudController
{
    private CustomersRepository $customersRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        PermissionService $permissionService,
        SecurityService $securityService,
        CustomersRepository $customersRepository
    ) {
        parent::__construct($entityManager, $validator, $logger, $permissionService, $securityService);
        $this->customersRepository = $customersRepository;
    }

    protected function getEntityClass(): string
    {
        return Customers::class;
    }

    protected function getRepository()
    {
        return $this->customersRepository;
    }

    protected function getEntityName(): string
    {
        return 'Client';
    }

    protected function getTemplatePrefix(): string
    {
        return 'parameter/customer';
    }

    protected function canView(): bool
    {
        return $this->permissionService->canViewCustomerList();
    }

    protected function canEdit(): bool
    {
        return $this->permissionService->canEditCustomer();
    }

    protected function canDelete(): bool
    {
        return $this->permissionService->canDeleteCustomer();
    }

    #[Route('/', name: 'app_parameter_customers')]
    public function index(): Response
    {
        return parent::index();
    }

    protected function validateData(array $data): void
    {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Le nom du client est requis');
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }
        
        if (!empty($data['phone']) && !preg_match('/^\+?[0-9\s\-\(\)]{8,20}$/', $data['phone'])) {
            throw new \InvalidArgumentException('Numéro de téléphone invalide');
        }
    }

    protected function createEntity(array $data): object
    {
        $customer = new Customers();
        $this->updateEntity($customer, $data);
        return $customer;
    }

    protected function updateEntity(object $entity, array $data): void
    {
        if (!$entity instanceof Customers) {
            throw new \InvalidArgumentException('L\'entité doit être un client');
        }
        
        if (isset($data['name'])) {
            $entity->setCustomerName($data['name']);
        }
        
        if (isset($data['email'])) {
            $entity->setCustomerReference($data['email']);
        }
        
        if (isset($data['phone'])) {
            // Pas de champ téléphone dans l'entité, on peut l'ignorer ou l'ajouter dans une mise à jour future
        }
        
        if (isset($data['address'])) {
            $entity->setCustomerAddressStreet($data['address']);
        }
        
        if (isset($data['city'])) {
            $entity->setCustomerAddressCity($data['city']);
        }
        
        if (isset($data['postal_code'])) {
            $entity->setCustomerAddressZipcode($data['postal_code']);
        }
        
        if (isset($data['country'])) {
            $entity->setCustomerAddressCountry($data['country']);
        }
        
        if (isset($data['website'])) {
            // Pas de champ website dans l'entité, on peut l'ignorer ou l'ajouter dans une mise à jour future
        }
        
        if (isset($data['notes'])) {
            // Pas de champ notes dans l'entité, on peut l'ignorer ou l'ajouter dans une mise à jour future
        }
        
        if (isset($data['vat'])) {
            $entity->setCustomerVat($data['vat']);
        }
        
        if (isset($data['siren'])) {
            $entity->setCustomerSiren($data['siren']);
        }
        
        // Mettre à jour la date de modification
        $entity->setCustomerUpdatedAt(new \DateTime());
        
        // Mettre à jour l'utilisateur qui a fait la modification
        $currentUser = $this->securityService->getCurrentUser();
        if ($currentUser) {
            $entity->setCustomerUpdatedBy($currentUser);
        }
    }

    protected function getRequestData(Request $request): array
    {
        $data = parent::getRequestData($request);
        
        return [
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'postal_code' => $data['postal_code'] ?? '',
            'country' => $data['country'] ?? '',
            'website' => $data['website'] ?? '',
            'notes' => $data['notes'] ?? '',
        ];
    }
}
