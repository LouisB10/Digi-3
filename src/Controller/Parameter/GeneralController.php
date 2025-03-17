<?php

namespace App\Controller\Parameter;

use App\Entity\User;
use App\Enum\UserRole;
use App\Form\Parameter\EmailUpdateType;
use App\Form\Parameter\PasswordUpdateType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\PermissionService;

#[Route('/parameter/general')]
#[IsGranted('ROLE_USER')]
class GeneralController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private PermissionService $permissionService;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        PermissionService $permissionService
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->permissionService = $permissionService;
    }

    #[Route('/', name: 'app_parameter_general_index')]
    public function index(Request $request): Response
    {
        // Récupérer l'utilisateur courant
        $user = $this->getUser();
        
        // S'assurer que l'utilisateur est bien une instance de User
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }
        
        // Formulaire pour changer l'email
        $emailForm = $this->createForm(EmailUpdateType::class);
        
        // Formulaire pour changer le mot de passe
        $passwordForm = $this->createForm(PasswordUpdateType::class);
        
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
        
        return $this->render('parameter/index.html.twig', [
            'user' => $user,
            'emailForm' => $emailForm->createView(),
            'passwordForm' => $passwordForm->createView(),
            'permissions' => $permissions,
        ]);
    }

    #[Route('/update-email', name: 'app_parameter_general_update_email', methods: ['POST'])]
    public function updateEmail(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => false, 'message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
            }
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }
        
        // Traitement AJAX
        if ($request->isXmlHttpRequest() || $request->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($request->getContent(), true);
            $email = $data['email'] ?? null;
            $password = $data['password'] ?? null;
            
            if (!$email || !$password) {
                return $this->json(['success' => false, 'message' => 'Email ou mot de passe manquant'], Response::HTTP_BAD_REQUEST);
            }
            
            // Vérifier le mot de passe
            if (!$this->passwordHasher->isPasswordValid($user, $password)) {
                return $this->json(['success' => false, 'message' => 'Mot de passe incorrect'], Response::HTTP_BAD_REQUEST);
            }
            
            // Vérifier si l'email est déjà utilisé
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['userEmail' => $email]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                return $this->json(['success' => false, 'message' => 'Cette adresse e-mail est déjà utilisée'], Response::HTTP_BAD_REQUEST);
            }
            
            // Mettre à jour l'email
            $user->setUserEmail($email);
            $this->entityManager->flush();
            
            return $this->json(['success' => true, 'message' => 'Adresse e-mail mise à jour avec succès']);
        }
        
        // Traitement du formulaire classique
        $emailForm = $this->createForm(EmailUpdateType::class);
        $emailForm->handleRequest($request);
        
        if ($emailForm->isSubmitted() && $emailForm->isValid()) {
            $data = $emailForm->getData();
            
            // Vérifier le mot de passe
            if (isset($data['password']) && $this->passwordHasher->isPasswordValid($user, $data['password'])) {
                // Vérifier si l'email est déjà utilisé
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['userEmail' => $data['email']]);
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    $this->addFlash('error', 'Cette adresse e-mail est déjà utilisée.');
                } else {
                    // Mettre à jour l'email
                    $user->setUserEmail($data['email']);
                    $this->entityManager->flush();
                    $this->addFlash('success', 'Votre adresse e-mail a été mise à jour avec succès.');
                }
            } else {
                $this->addFlash('error', 'Mot de passe incorrect.');
            }
        } else {
            $this->addFlash('error', 'Formulaire invalide.');
        }
        
        return $this->redirectToRoute('app_parameter_general_index');
    }

    #[Route('/update-password', name: 'app_parameter_general_update_password', methods: ['POST'])]
    public function updatePassword(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => false, 'message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
            }
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }
        
        // Traitement AJAX
        if ($request->isXmlHttpRequest() || $request->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($request->getContent(), true);
            
            $currentPassword = $data['currentPassword'] ?? null;
            $newPassword = $data['newPassword'] ?? null;
            $confirmPassword = $data['confirmPassword'] ?? null;
            
            // Vérifier que tous les champs sont remplis
            if (!$currentPassword || !$newPassword || !$confirmPassword) {
                return $this->json(['success' => false, 'message' => 'Tous les champs sont requis'], Response::HTTP_BAD_REQUEST);
            }
            
            // Vérifier que le mot de passe actuel est correct
            if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                return $this->json(['success' => false, 'message' => 'Mot de passe actuel incorrect'], Response::HTTP_BAD_REQUEST);
            }
            
            // Vérifier que les nouveaux mots de passe correspondent
            if ($newPassword !== $confirmPassword) {
                return $this->json(['success' => false, 'message' => 'Les nouveaux mots de passe ne correspondent pas'], Response::HTTP_BAD_REQUEST);
            }
            
            // Vérifier que le nouveau mot de passe est suffisamment fort
            if (strlen($newPassword) < 8) {
                return $this->json(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères'], Response::HTTP_BAD_REQUEST);
            }
            
            // Mettre à jour le mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $this->entityManager->flush();
            
            return $this->json(['success' => true, 'message' => 'Mot de passe mis à jour avec succès']);
        }
        
        // Traitement du formulaire classique
        $passwordForm = $this->createForm(PasswordUpdateType::class);
        $passwordForm->handleRequest($request);
        
        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $data = $passwordForm->getData();
            
            // Vérifier que les mots de passe correspondent
            if (isset($data['password']) && isset($data['confirm_password']) && $data['password'] !== $data['confirm_password']) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_parameter_general_index');
            }
            
            // Vérifier le mot de passe actuel
            if (isset($data['actual_password']) && $this->passwordHasher->isPasswordValid($user, $data['actual_password'])) {
                // Mettre à jour le mot de passe
                $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Votre mot de passe a été mis à jour avec succès.');
            } else {
                $this->addFlash('error', 'Mot de passe actuel incorrect.');
            }
        } else {
            $this->addFlash('error', 'Formulaire invalide.');
        }
        
        return $this->redirectToRoute('app_parameter_general_index');
    }

    #[Route('/update-profile-picture', name: 'app_parameter_general_update_profile', methods: ['POST'])]
    public function updateProfilePicture(Request $request, SluggerInterface $slugger): JsonResponse
    {
        $user = $this->getUser();
        
        // S'assurer que l'utilisateur est bien une instance de User
        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'error' => 'Utilisateur non authentifié.'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        // Vérifier le token CSRF si présent
        $submittedToken = $request->request->get('_token');
        if ($submittedToken && !$this->isCsrfTokenValid('upload', $submittedToken)) {
            return $this->json([
                'success' => false,
                'error' => 'Token CSRF invalide.'
            ], Response::HTTP_FORBIDDEN);
        }
        
        $profilePicture = $request->files->get('profile_picture');
        
        if (!$profilePicture) {
            return $this->json([
                'success' => false,
                'error' => 'Aucun fichier n\'a été téléchargé.'
            ]);
        }
        
        // Vérifier le type MIME
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($profilePicture->getMimeType(), $allowedMimeTypes)) {
            return $this->json([
                'success' => false,
                'error' => 'Type de fichier non autorisé. Veuillez télécharger une image (JPG, PNG, GIF ou WEBP).'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // Vérifier la taille du fichier (max 5MB)
        if ($profilePicture->getSize() > 5 * 1024 * 1024) {
            return $this->json([
                'success' => false,
                'error' => 'Le fichier est trop volumineux. La taille maximale est de 5 Mo.'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $originalFilename = pathinfo($profilePicture->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$profilePicture->guessExtension();
        
        try {
            // Déplacer le fichier dans le répertoire des avatars
            $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/avatar';
            
            // Créer le répertoire s'il n'existe pas
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
                    throw new FileException(sprintf('Le répertoire "%s" n\'a pas pu être créé', $uploadDir));
                }
                // S'assurer que les permissions sont correctes
                chmod($uploadDir, 0777);
            }
            
            // Vérifier les permissions du dossier
            if (!is_writable($uploadDir)) {
                chmod($uploadDir, 0777); // Essayer de rendre le dossier accessible en écriture
                if (!is_writable($uploadDir)) {
                    throw new FileException(sprintf('Le répertoire "%s" n\'est pas accessible en écriture', $uploadDir));
                }
            }
            
            // Déplacer le fichier
            $profilePicture->move(
                $uploadDir,
                $newFilename
            );
            
            // Supprimer l'ancien avatar s'il ne s'agit pas de l'avatar par défaut
            $oldAvatar = $user->getUserAvatar();
            $defaultAvatars = ['uploads/avatar/default.png', 'build/images/account/default-avatar.jpg'];
            if ($oldAvatar && !in_array($oldAvatar, $defaultAvatars) && file_exists($this->getParameter('kernel.project_dir').'/public/'.$oldAvatar)) {
                unlink($this->getParameter('kernel.project_dir').'/public/'.$oldAvatar);
            }
            
            // Mettre à jour l'avatar de l'utilisateur
            $avatarPath = 'uploads/avatar/'.$newFilename;
            $user->setUserAvatar($avatarPath);
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'newProfilePictureUrl' => '/'.$avatarPath
            ]);
        } catch (FileException $e) {
            return $this->json([
                'success' => false,
                'error' => 'Une erreur est survenue lors du téléchargement du fichier: '.$e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Une erreur inattendue est survenue: '.$e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 