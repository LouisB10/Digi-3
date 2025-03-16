<?php

namespace App\Controller\Parameter;

use App\Entity\User;
use App\Form\EmailUpdateType;
use App\Form\PasswordUpdateType;
use App\Service\PermissionService;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/parameter')]
class GeneralController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SecurityService $securityService,
        private PermissionService $permissionService,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/generaux', name: 'app_parameter_generaux')]
    public function index(Request $request): Response
    {
        $user = $this->securityService->getCurrentUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_auth');
        }

        // Utiliser la méthode spécifique pour vérifier la permission
        if (!$this->permissionService->canViewOwnProfile()) {
            throw $this->createAccessDeniedException('Accès non autorisé à cette page.');
        }

        // Créer les formulaires
        $emailForm = $this->createForm(EmailUpdateType::class, $user);
        $passwordForm = $this->createForm(PasswordUpdateType::class, $user);

        // Gérer le formulaire d'email
        $emailForm->handleRequest($request);
        if ($emailForm->isSubmitted() && $emailForm->isValid()) {
            $actualPassword = $emailForm->get('password')->getData();
            if ($this->passwordHasher->isPasswordValid($user, $actualPassword)) {
                $newEmail = $emailForm->get('email')->getData();
                
                if ($newEmail === $user->getUserEmail()) {
                    $this->addFlash('error', 'Le nouvel email doit être différent de l\'actuel');
                } else {
                    $user->setUserEmail($newEmail);
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                    
                    $this->addFlash('success', 'Email mis à jour avec succès');
                    return $this->redirectToRoute('app_parameter_generaux');
                }
            } else {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect');
            }
        }

        // Gérer le formulaire de password
        $passwordForm->handleRequest($request);
        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $actualPassword = $passwordForm->get('actual_password')->getData();
            $newPassword = $passwordForm->get('password')->getData();

            if ($this->passwordHasher->isPasswordValid($user, $actualPassword)) {
                if ($this->passwordHasher->isPasswordValid($user, $newPassword)) {
                    $this->addFlash('error', 'Le nouveau mot de passe doit être différent de l\'ancien.');
                } else {
                    $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
                    $user->setPassword($hashedPassword);
                    
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    $this->addFlash('success', 'Mot de passe mis à jour avec succès');
                    return $this->redirectToRoute('app_parameter_generaux');
                }
            } else {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect');
            }
        }

        // Gérer l'upload de photo de profil
        if ($request->isMethod('POST') && $request->files->has('profile_picture')) {
            return $this->handleProfilePictureUpload($request, $user);
        }

        return $this->render('parameter/index.html.twig', [
            'emailForm' => $emailForm->createView(),
            'passwordForm' => $passwordForm->createView(),
            'user' => $user,
        ]);
    }

    private function handleProfilePictureUpload(Request $request, User $user): JsonResponse
    {
        $file = $request->files->get('profile_picture');
        
        if (!$this->isCsrfTokenValid('upload_photo', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['success' => false, 'error' => 'Token CSRF invalide'], 400);
        }

        if ($file && in_array($file->getClientMimeType(), ['image/jpeg', 'image/png', 'image/gif'])) {
            if ($file->getSize() > 5 * 1024 * 1024) {
                return $this->json(['success' => false, 'error' => 'Le fichier est trop volumineux (max 5MB)']);
            }

            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $filePath = 'uploads/profile_pictures/' . $filename;

            try {
                $file->move($this->getParameter('kernel.project_dir') . '/public/uploads/profile_pictures', $filename);
                
                $user->setUserAvatar('/uploads/profile_pictures/' . $filename);
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $this->json([
                    'success' => true,
                    'newProfilePictureUrl' => $user->getUserAvatar()
                ]);
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'error' => 'Erreur lors de l\'upload du fichier']);
            }
        }

        return $this->json(['success' => false, 'error' => 'Format de fichier non valide. Veuillez télécharger une image.']);
    }
}
