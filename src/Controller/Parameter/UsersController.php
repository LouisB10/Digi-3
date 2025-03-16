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

#[Route('/parameter/users')]
#[IsGranted('ROLE_USER')]
class UsersController extends AbstractController
{
    #[Route('/', name: 'app_parameter_users_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'utilisateur courant
        $currentUser = $this->getUser();
        
        // Récupérer tous les utilisateurs
        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->render('parameter/users/index.html.twig', [
            'user' => $currentUser,  // Pour le template header
            'users' => $users        // Pour la liste des utilisateurs
        ]);
    }

    #[Route('/create', name: 'app_parameter_users_create', methods: ['POST'])]
    #[IsGranted('ROLE_RESPONSABLE')]
    public function create(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
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
        
        // Créer un nouvel utilisateur
        $user = new User();
        $user->setUserFirstName($data['firstName']);
        $user->setUserLastName($data['lastName']);
        $user->setUserEmail($data['email']);
        
        // Définir le mot de passe
        if (!empty($data['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        } else {
            return $this->json([
                'success' => false,
                'message' => 'Le mot de passe est requis pour un nouvel utilisateur.'
            ]);
        }
        
        // Définir le rôle
        $user->setUserRole(UserRole::from($data['role']));
        
        // Définir l'avatar par défaut
        $user->setUserAvatar('build/images/avatar/default.png');
        
        try {
            $entityManager->persist($user);
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès.'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création de l\'utilisateur: ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/{id}/edit', name: 'app_parameter_users_edit', methods: ['GET'])]
    #[IsGranted('ROLE_RESPONSABLE')]
    public function edit(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.'
            ], 404);
        }
        
        // Récupérer le rôle principal
        $role = $user->getUserRole() ? $user->getUserRole()->value : 'ROLE_USER';
        
        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'firstName' => $user->getUserFirstName(),
                'lastName' => $user->getUserLastName(),
                'email' => $user->getUserEmail(),
                'role' => $role
            ]
        ]);
    }

    #[Route('/{id}/update', name: 'app_parameter_users_update', methods: ['POST'])]
    #[IsGranted('ROLE_RESPONSABLE')]
    public function update(int $id, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.'
            ], 404);
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
        
        // Mettre à jour les informations de l'utilisateur
        $user->setUserFirstName($data['firstName']);
        $user->setUserLastName($data['lastName']);
        $user->setUserEmail($data['email']);
        
        // Mettre à jour le mot de passe si fourni
        if (!empty($data['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }
        
        // Mettre à jour le rôle si l'utilisateur courant est admin
        if ($this->isGranted('ROLE_ADMIN')) {
            $user->setUserRole(UserRole::from($data['role']));
        }
        
        try {
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès.'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour de l\'utilisateur: ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/{id}/delete', name: 'app_parameter_users_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.'
            ], 404);
        }
        
        // Vérifier que l'utilisateur n'est pas en train de se supprimer lui-même
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $user->getId() === $currentUser->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.'
            ], 403);
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
                'message' => 'Une erreur est survenue lors de la suppression de l\'utilisateur: ' . $e->getMessage()
            ]);
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