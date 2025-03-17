<?php

namespace App\Controller\Parameter;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\PermissionService;

#[Route('/parameter/users')]
#[IsGranted('ROLE_USER')]
class UsersController extends AbstractController
{
    private PermissionService $permissionService;
    
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    #[Route('/', name: 'app_parameter_users_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur peut voir les utilisateurs
        if (!$this->permissionService->canPerform('view', 'user')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir les utilisateurs.');
        }
        
        // Récupérer l'utilisateur courant
        $currentUser = $this->getUser();
        
        // Récupérer tous les utilisateurs
        $users = $entityManager->getRepository(User::class)->findAll();

        // Préparer les permissions pour chaque utilisateur
        $userPermissions = [];
        foreach ($users as $user) {
            $userPermissions[$user->getId()] = [
                'canEdit' => $this->permissionService->canEditUser($user),
                'canDelete' => $this->permissionService->canDeleteUser($user)
            ];
        }

        // Récupérer les rôles disponibles pour le formulaire
        $availableRoles = [];
        foreach (UserRole::cases() as $role) {
            $availableRoles[$role->getLabel()] = $role->value;
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

        return $this->render('parameter/users/index.html.twig', [
            'user' => $currentUser,       // Pour le template header
            'users' => $users,            // Pour la liste des utilisateurs
            'available_roles' => $availableRoles, // Pour le formulaire
            'permissions' => $permissions,
            'userPermissions' => $userPermissions // Permissions spécifiques à chaque utilisateur
        ]);
    }

    #[Route('/create', name: 'app_parameter_users_create', methods: ['POST'])]
    #[IsGranted('ROLE_RESPONSABLE')]
    public function create(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        // Vérifier si l'utilisateur peut créer des utilisateurs
        if (!$this->permissionService->canPerform('create', 'user')) {
            return $this->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à créer des utilisateurs.'
            ], Response::HTTP_FORBIDDEN);
        }
        
        // Récupérer les données du formulaire
        $data = $request->request->all();
        
        // Vérifier si l'email existe déjà
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['userEmail' => $data['email']]);
        if ($existingUser) {
            return $this->json([
                'success' => false,
                'message' => 'Cet email est déjà utilisé par un autre utilisateur.'
            ]);
        }
        
        try {
            // Créer le nouvel utilisateur
            $user = new User();
            $user->setUserFirstName($data['firstName']);
            $user->setUserLastName($data['lastName']);
            $user->setUserEmail($data['email']);
            
            // Hasher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
            
            // Définir le rôle
            try {
                $role = UserRole::from($data['role']);
                $user->setUserRole($role);
            } catch (\ValueError $e) {
                // Si le rôle n'est pas valide, utiliser le rôle utilisateur par défaut
                $user->setUserRole(UserRole::USER);
            }
            
            // Enregistrer l'utilisateur
            $entityManager->persist($user);
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès.',
                'user' => [
                    'id' => $user->getId(),
                    'firstName' => $user->getUserFirstName(),
                    'lastName' => $user->getUserLastName(),
                    'email' => $user->getUserEmail(),
                    'role' => $user->getUserRole() ? $user->getUserRole()->getLabel() : 'Utilisateur'
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création de l\'utilisateur.'
            ], 500);
        }
    }

    #[Route('/{id}/edit', name: 'app_parameter_users_edit', methods: ['GET'])]
    #[IsGranted('ROLE_RESPONSABLE')]
    public function edit(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        // Récupérer l'utilisateur à modifier
        $user = $entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.'
            ], 404);
        }
        
        // Vérifier les permissions basées sur les rôles
        if (!$this->isGranted('ROLE_ADMIN')) {
            // Seuls les administrateurs peuvent modifier tous les utilisateurs
            // Les autres utilisateurs ne peuvent modifier que les utilisateurs avec un rôle inférieur
            $currentUser = $this->getUser();
            if ($currentUser instanceof User && $currentUser->getId() !== $user->getId()) {
                if (!$this->isGranted('ROLE_RESPONSABLE')) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Vous n\'avez pas les droits pour modifier cet utilisateur.'
                    ], 403);
                }
            }
        }
        
        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'firstName' => $user->getUserFirstName(),
                'lastName' => $user->getUserLastName(),
                'email' => $user->getUserEmail(),
                'role' => $user->getUserRole() ? $user->getUserRole()->value : UserRole::USER->value
            ]
        ]);
    }

    #[Route('/{id}/update', name: 'app_parameter_users_update', methods: ['POST'])]
    #[IsGranted('ROLE_RESPONSABLE')]
    public function update(int $id, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        // Récupérer l'utilisateur à modifier
        $user = $entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.'
            ], 404);
        }
        
        // Vérifier si l'utilisateur a les droits pour modifier cet utilisateur
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $user->getUserRole() && $currentUser->getUserRole()) {
            // Si l'utilisateur à modifier est un admin et que l'utilisateur courant n'est pas admin
            if ($user->getUserRole() === UserRole::ADMIN && $currentUser->getUserRole() !== UserRole::ADMIN) {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas les droits nécessaires pour modifier un administrateur.'
                ], 403);
            }
            
            // Vérifier la hiérarchie des rôles
            if (UserRole::getRoleWeight($user->getUserRole()) > UserRole::getRoleWeight($currentUser->getUserRole())) {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas modifier un utilisateur avec un rôle supérieur au vôtre.'
                ], 403);
            }
        }
        
        // Récupérer les données du formulaire
        $data = $request->request->all();
        
        // Vérifier si l'email existe déjà pour un autre utilisateur
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['userEmail' => $data['email']]);
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Cet email est déjà utilisé par un autre utilisateur.'
            ]);
        }
        
        try {
            // Mettre à jour les données de l'utilisateur
            $user->setUserFirstName($data['firstName']);
            $user->setUserLastName($data['lastName']);
            $user->setUserEmail($data['email']);
            
            // Mettre à jour le mot de passe si fourni
            if (!empty($data['password'])) {
                $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
            }
            
            // Mettre à jour le rôle si l'utilisateur courant a les droits nécessaires
            if (isset($data['role'])) {
                try {
                    $newRole = UserRole::from($data['role']);
                    
                    // Vérifier que l'utilisateur courant peut attribuer ce rôle
                    if ($currentUser instanceof User && $currentUser->getUserRole()) {
                        // Un utilisateur ne peut pas attribuer un rôle supérieur au sien
                        if (UserRole::getRoleWeight($newRole) > UserRole::getRoleWeight($currentUser->getUserRole())) {
                            return $this->json([
                                'success' => false,
                                'message' => 'Vous ne pouvez pas attribuer un rôle supérieur au vôtre.'
                            ], 403);
                        }
                        
                        // Seul un admin peut modifier le rôle d'un autre admin
                        if ($user->getUserRole() === UserRole::ADMIN && $currentUser->getUserRole() !== UserRole::ADMIN) {
                            return $this->json([
                                'success' => false,
                                'message' => 'Seul un administrateur peut modifier le rôle d\'un autre administrateur.'
                            ], 403);
                        }
                        
                        $user->setUserRole($newRole);
                    }
                } catch (\ValueError $e) {
                    // Si le rôle n'est pas valide, ne pas le modifier
                    return $this->json([
                        'success' => false,
                        'message' => 'Le rôle spécifié n\'est pas valide.'
                    ], 400);
                }
            }
            
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès.',
                'user' => [
                    'id' => $user->getId(),
                    'firstName' => $user->getUserFirstName(),
                    'lastName' => $user->getUserLastName(),
                    'email' => $user->getUserEmail(),
                    'role' => $user->getUserRole() ? $user->getUserRole()->getLabel() : 'Utilisateur'
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour de l\'utilisateur: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/delete', name: 'app_parameter_users_delete', methods: ['POST'])]
    #[IsGranted('ROLE_RESPONSABLE')]
    public function delete(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        // Récupérer l'utilisateur à supprimer
        $user = $entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.'
            ], 404);
        }
        
        // Vérifier que l'utilisateur n'essaie pas de se supprimer lui-même
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.'
            ], 403);
        }
        
        // Vérifier que l'utilisateur courant a un rôle supérieur ou égal à celui de l'utilisateur à supprimer
        if ($currentUser instanceof User && $user->getUserRole() && $currentUser->getUserRole()) {
            // Si l'utilisateur à supprimer est un admin et que l'utilisateur courant n'est pas admin
            if ($user->getUserRole() === UserRole::ADMIN && $currentUser->getUserRole() !== UserRole::ADMIN) {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas les droits nécessaires pour supprimer un administrateur.'
                ], 403);
            }
            
            // Vérifier la hiérarchie des rôles
            if (UserRole::getRoleWeight($user->getUserRole()) > UserRole::getRoleWeight($currentUser->getUserRole())) {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas supprimer un utilisateur avec un rôle supérieur au vôtre.'
                ], 403);
            }
        }
        
        try {
            $entityManager->remove($user);
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès.'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la suppression de l\'utilisateur.'
            ], 500);
        }
    }

    #[Route('/search', name: 'app_parameter_users_search', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $searchType = $request->query->get('type');
        $searchQuery = $request->query->get('query');
        
        if (empty($searchQuery)) {
            $users = $entityManager->getRepository(User::class)->findAll();
        } else {
            $qb = $entityManager->getRepository(User::class)->createQueryBuilder('u');
            
            switch ($searchType) {
                case 'name':
                    $qb->where('u.userFirstName LIKE :query OR u.userLastName LIKE :query')
                       ->setParameter('query', '%' . $searchQuery . '%');
                    break;
                case 'email':
                    $qb->where('u.userEmail LIKE :query')
                       ->setParameter('query', '%' . $searchQuery . '%');
                    break;
                case 'role':
                    $qb->where('u.userRole LIKE :query')
                       ->setParameter('query', '%' . $searchQuery . '%');
                    break;
                default:
                    $qb->where('u.userFirstName LIKE :query OR u.userLastName LIKE :query OR u.userEmail LIKE :query')
                       ->setParameter('query', '%' . $searchQuery . '%');
            }
            
            $users = $qb->getQuery()->getResult();
        }
        
        $formattedUsers = [];
        foreach ($users as $user) {
            $role = $user->getUserRole() ? $user->getUserRole()->value : 'ROLE_USER';
            $roleLabel = $this->getRoleLabel($role);
            
            $formattedUsers[] = [
                'id' => $user->getId(),
                'firstName' => $user->getUserFirstName(),
                'lastName' => $user->getUserLastName(),
                'email' => $user->getUserEmail(),
                'avatar' => $user->getUserAvatar(),
                'role' => $role,
                'roleLabel' => $roleLabel
            ];
        }
        
        return $this->json([
            'success' => true,
            'users' => $formattedUsers
        ]);
    }
    
    /**
     * Convertit un rôle technique en libellé lisible
     */
    private function getRoleLabel(string $role): string
    {
        $roleLabels = [
            'ROLE_ADMIN' => 'Administrateur',
            'ROLE_RESPONSABLE' => 'Responsable',
            'ROLE_PROJECT_MANAGER' => 'Chef de projet',
            'ROLE_LEAD_DEVELOPER' => 'Lead développeur',
            'ROLE_DEVELOPER' => 'Développeur',
            'ROLE_USER' => 'Utilisateur'
        ];
        
        return $roleLabels[$role] ?? 'Utilisateur';
    }
} 