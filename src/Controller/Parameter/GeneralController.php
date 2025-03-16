<?php

namespace App\Controller\Parameter;

use App\Entity\User;
use App\Enum\UserRole;
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
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

#[Route('/parameter/general')]
#[IsGranted('ROLE_USER')]
class GeneralController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/', name: 'app_parameter_general_index')]
    public function index(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Récupérer l'utilisateur courant
        $user = $this->getUser();
        
        // S'assurer que l'utilisateur est bien une instance de User
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }
        
        // Formulaire pour changer l'email
        $emailForm = $this->createFormBuilder(null, [
            'attr' => ['id' => 'email_form']
        ])
            ->add('email', EmailType::class, [
                'label' => 'Nouvelle adresse e-mail',
                'attr' => [
                    'autocomplete' => 'email',
                    'id' => 'email_form_email'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer une adresse e-mail']),
                    new Email(['message' => 'Veuillez entrer une adresse e-mail valide'])
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'attr' => [
                    'autocomplete' => 'current-password',
                    'id' => 'email_form_password'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre mot de passe actuel'])
                ]
            ])
            ->getForm();
            
        // Formulaire pour changer le mot de passe
        $passwordForm = $this->createFormBuilder(null, [
            'attr' => ['id' => 'password_form']
        ])
            ->add('actual_password', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'attr' => [
                    'autocomplete' => 'current-password',
                    'id' => 'password_form_actual_password'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre mot de passe actuel'])
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Nouveau mot de passe',
                'attr' => [
                    'autocomplete' => 'new-password',
                    'id' => 'password_form_password'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un nouveau mot de passe'])
                ]
            ])
            ->add('confirm_password', PasswordType::class, [
                'label' => 'Confirmer le mot de passe',
                'attr' => [
                    'autocomplete' => 'new-password',
                    'id' => 'password_form_confirm_password'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez confirmer votre nouveau mot de passe'])
                ]
            ])
            ->getForm();
            
        // Traitement du formulaire d'email
        $emailForm->handleRequest($request);
        if ($emailForm->isSubmitted() && $emailForm->isValid()) {
            $data = $emailForm->getData();
            
            // Vérifier le mot de passe
            if ($passwordHasher->isPasswordValid($user, $data['password'])) {
                // Vérifier si l'email est déjà utilisé
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['userEmail' => $data['email']]);
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    $this->addFlash('error', 'Cette adresse e-mail est déjà utilisée.');
                } else {
                    // Mettre à jour l'email
                    $user->setUserEmail($data['email']);
                    $entityManager->flush();
                    $this->addFlash('success', 'Votre adresse e-mail a été mise à jour avec succès.');
                }
            } else {
                $this->addFlash('error', 'Mot de passe incorrect.');
            }
        }
        
        // Traitement du formulaire de mot de passe
        $passwordForm->handleRequest($request);
        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $data = $passwordForm->getData();
            
            // Vérifier que les mots de passe correspondent
            if ($data['password'] !== $data['confirm_password']) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_parameter_general_index');
            }
            
            // Vérifier le mot de passe actuel
            if ($passwordHasher->isPasswordValid($user, $data['actual_password'])) {
                // Mettre à jour le mot de passe
                $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
                $entityManager->flush();
                
                // Ajouter un log pour le débogage
                error_log('Mot de passe mis à jour pour l\'utilisateur ' . $user->getUserEmail());
                
                $this->addFlash('success', 'Votre mot de passe a été mis à jour avec succès.');
            } else {
                $this->addFlash('error', 'Mot de passe actuel incorrect.');
            }
        }
        
        return $this->render('parameter/index.html.twig', [
            'user' => $user,
            'emailForm' => $emailForm->createView(),
            'passwordForm' => $passwordForm->createView(),
        ]);
    }

    #[Route('/update-email', name: 'app_parameter_general_update_email', methods: ['POST'])]
    public function updateEmail(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }
        
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        
        if (!$email) {
            return $this->json(['success' => false, 'message' => 'Email manquant'], Response::HTTP_BAD_REQUEST);
        }
        
        // Vérifier si l'email est déjà utilisé par un autre utilisateur
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['userEmail' => $email]);
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            return $this->json(['success' => false, 'message' => 'Cet email est déjà utilisé'], Response::HTTP_BAD_REQUEST);
        }
        
        $user->setUserEmail($email);
        $this->entityManager->flush();
        
        return $this->json(['success' => true, 'message' => 'Email mis à jour avec succès']);
    }

    #[Route('/update-password', name: 'app_parameter_general_update_password', methods: ['POST'])]
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }
        
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

    #[Route('/update-profile-picture', name: 'app_parameter_general_update_profile', methods: ['POST'])]
    public function updateProfilePicture(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): JsonResponse
    {
        $user = $this->getUser();
        
        // S'assurer que l'utilisateur est bien une instance de User
        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'error' => 'Utilisateur non authentifié.'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $profilePicture = $request->files->get('profile_picture');
        
        if (!$profilePicture) {
            return $this->json([
                'success' => false,
                'error' => 'Aucun fichier n\'a été téléchargé.'
            ]);
        }
        
        $originalFilename = pathinfo($profilePicture->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$profilePicture->guessExtension();
        
        try {
            // Déplacer le fichier dans le répertoire des avatars
            $profilePicture->move(
                $this->getParameter('kernel.project_dir').'/public/build/images/avatar',
                $newFilename
            );
            
            // Supprimer l'ancien avatar s'il ne s'agit pas de l'avatar par défaut
            $oldAvatar = $user->getUserAvatar();
            if ($oldAvatar && $oldAvatar !== 'build/images/avatar/default.png' && file_exists($this->getParameter('kernel.project_dir').'/public/'.$oldAvatar)) {
                unlink($this->getParameter('kernel.project_dir').'/public/'.$oldAvatar);
            }
            
            // Mettre à jour l'avatar de l'utilisateur
            $user->setUserAvatar('build/images/avatar/'.$newFilename);
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'newProfilePictureUrl' => $this->getParameter('app.base_url').'/build/images/avatar/'.$newFilename
            ]);
        } catch (FileException $e) {
            return $this->json([
                'success' => false,
                'error' => 'Une erreur est survenue lors du téléchargement du fichier: '.$e->getMessage()
            ]);
        }
    }
} 