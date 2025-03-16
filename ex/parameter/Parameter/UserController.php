<?php

namespace App\Controller\Parameter;

use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use App\Service\PermissionService;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

#[Route('/parameter/users')]
class UserController extends AbstractCrudController
{
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        PermissionService $permissionService,
        SecurityService $securityService,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct($entityManager, $validator, $logger, $permissionService, $securityService);
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    protected function getEntityClass(): string
    {
        return User::class;
    }

    protected function getRepository()
    {
        return $this->userRepository;
    }

    protected function getEntityName(): string
    {
        return 'Utilisateur';
    }

    protected function getTemplatePrefix(): string
    {
        return 'parameter/user';
    }

    protected function canView(): bool
    {
        return $this->permissionService->canViewUserList();
    }

    protected function canEdit(): bool
    {
        return $this->permissionService->canEditUser();
    }

    protected function canDelete(): bool
    {
        return $this->permissionService->canManageUsers();
    }

    #[Route('/', name: 'app_parameter_users')]
    public function index(): Response
    {
        $currentUser = $this->securityService->getCurrentUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_auth');
        }

        if (!$this->canView()) {
            throw $this->createAccessDeniedException('Accès non autorisé');
        }

        $users = $this->userRepository->findAll();
        $roles = UserRole::cases();

        return $this->render('parameter/user/index.html.twig', [
            'users' => $users,
            'roles' => $roles,
            'canEdit' => $this->canEdit(),
            'canDelete' => $this->canDelete(),
        ]);
    }

    protected function validateData(array $data): void
    {
        if (empty($data['first_name'])) {
            throw new \InvalidArgumentException('Le prénom est requis');
        }
        
        if (empty($data['last_name'])) {
            throw new \InvalidArgumentException('Le nom est requis');
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }
    }

    protected function createEntity(array $data): object
    {
        $user = new User();
        $user->setUserFirstName($data['first_name']);
        $user->setUserLastName($data['last_name']);
        $user->setUserEmail($data['email']);
        
        // Générer un mot de passe temporaire
        $temporaryPassword = $this->generateTemporaryPassword($user);
        $user->setPassword($this->passwordHasher->hashPassword($user, $temporaryPassword));
        
        // Définir le rôle
        $user->setUserRole(UserRole::from($data['role'] ?? UserRole::USER->value));
        
        return $user;
    }

    protected function updateEntity(object $entity, array $data): void
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException('L\'entité doit être un utilisateur');
        }
        
        if (isset($data['first_name'])) {
            $entity->setUserFirstName($data['first_name']);
        }
        
        if (isset($data['last_name'])) {
            $entity->setUserLastName($data['last_name']);
        }
        
        if (isset($data['email'])) {
            $entity->setUserEmail($data['email']);
        }
        
        if (isset($data['role'])) {
            $entity->setUserRole(UserRole::from($data['role']));
        }
    }

    private function generateTemporaryPassword(User $user): string
    {
        $prefix = substr($user->getUserFirstName(), 0, 1) . substr($user->getUserLastName(), 0, 1);
        $randomPart = bin2hex(random_bytes(4));
        
        return strtoupper($prefix) . '@' . $randomPart;
    }

    protected function getRequestData(Request $request): array
    {
        $data = parent::getRequestData($request);
        
        // Convertir les données du formulaire en tableau associatif
        return [
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'email' => $data['email'] ?? '',
            'role' => $data['role'] ?? UserRole::USER->value,
        ];
    }
}
