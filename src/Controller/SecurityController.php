<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\UserRole;
use App\Form\Auth\LoginFormType;
use App\Form\Auth\RegisterFormType;
use App\Form\Auth\ResetPasswordRequestFormType;
use App\Form\Auth\ResetPasswordFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\AppCustomAuthenticator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Mime\Address;

class SecurityController extends AbstractController
{
    private const TOKEN_LENGTH = 40;
    private const TOKEN_EXPIRATION = '+1 hour';

    public function __construct(
        private readonly RateLimiterFactory $loginLimiter,
        private readonly LoggerInterface $logger,
        private readonly AppCustomAuthenticator $authenticator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    private function validatePassword(string $password): array
    {
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une majuscule';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une minuscule';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un chiffre';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial';
        }
        return $errors;
    }

    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }

    #[Route('/auth', name: 'app_auth', methods: ['GET', 'POST'])]
    public function auth(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // Vérifier si l'utilisateur est déjà connecté
        $user = $this->getUser();
        if ($user) {
            $this->logger->info('Utilisateur déjà connecté, redirection vers le dashboard', [
                'user_email' => $user->getUserIdentifier(),
                'roles' => $user->getRoles(),
            ]);
            return $this->redirectToRoute('app_dashboard');
        }

        // Limiter les tentatives de connexion uniquement pour les requêtes POST
        if ($request->isMethod('POST')) {
            $limiter = $this->loginLimiter->create($request->getClientIp());
            if (!$limiter->consume(1)->isAccepted()) {
                $this->addFlash('error', 'Trop de tentatives de connexion. Veuillez réessayer dans 5 minutes.');
                return $this->redirectToRoute('app_auth');
            }
        }

        // Créer les formulaires
        $loginForm = $this->createForm(LoginFormType::class);
        $registerForm = $this->createForm(RegisterFormType::class);

        // Récupérer l'erreur d'authentification s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        if ($error) {
            $this->logger->warning('Erreur d\'authentification détectée', [
                'message' => $error->getMessage(),
                'class' => get_class($error),
                'exception' => $error,
            ]);
        }
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/auth.html.twig', [
            'error' => $error ? $error->getMessage() : null,
            'last_username' => $lastUsername,
            'login_form' => $loginForm->createView(),
            'registration_form' => $registerForm->createView(),
        ]);
    }

    #[Route('/auth/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        UserAuthenticatorInterface $userAuthenticator
    ): Response {
        try {
            $form = $this->createForm(RegisterFormType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();

                // Vérifier si l'utilisateur existe déjà
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['userEmail' => $data['email']]);
                if ($existingUser) {
                    $this->addFlash('error', 'Cet email est déjà utilisé. Veuillez utiliser un autre email ou récupérer votre compte.');
                    return $this->redirectToRoute('app_auth');
                }

                // Créer le nouvel utilisateur
                $user = User::create(
                    $data['first_name'],
                    $data['last_name'],
                    $data['email'],
                    $passwordHasher->hashPassword(new User(), $data['password'])
                );

                $this->logger->debug('Données utilisateur avant persist:', [
                    'firstName' => $user->getUserFirstName(),
                    'lastName' => $user->getUserLastName(),
                    'email' => $user->getUserEmail(),
                    'hasPassword' => !empty($user->getPassword()),
                    'dateFrom' => $user->getUserDateFrom()->format('Y-m-d H:i:s'),
                    'avatar' => $user->getUserAvatar(),
                    'role' => $user->getUserRole()
                ]);

                try {
                    $entityManager->persist($user);
                    $entityManager->flush();
                    
                    $this->logger->info('Nouvel utilisateur inscrit avec succès', [
                        'user_id' => $user->getId(),
                        'email' => $user->getUserEmail()
                    ]);
                    
                    // Rediriger vers la page de connexion avec un message de succès
                    $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
                    return $this->redirectToRoute('app_auth');
                    
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors de la persistance : ' . $e->getMessage());
                    $this->addFlash('error', 'Une erreur est survenue lors de la création du compte. Veuillez réessayer.');
                    return $this->redirectToRoute('app_auth');
                }

                try {
                    return $userAuthenticator->authenticateUser(
                        $user,
                        $this->authenticator,
                        $request
                    );
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors de l\'authentification : ' . $e->getMessage());
                    $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
                    return $this->redirectToRoute('app_auth');
                }
            } else {
                // Gérer les erreurs de validation du formulaire
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->redirectToRoute('app_auth');
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'inscription : ' . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer ultérieurement.');
            return $this->redirectToRoute('app_auth');
        }
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // Cette méthode peut rester vide, la déconnexion est gérée par le firewall
        throw new \LogicException('Cette méthode ne devrait jamais être appelée.');
    }

    #[Route('/auth/reset-password-request', name: 'app_reset_password_request', methods: ['GET', 'POST'])]
    public function resetPasswordRequest(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $form->getData();
                $email = $data['email'];
                
                $user = $entityManager->getRepository(User::class)->findOneBy(['userEmail' => $email]);
                
                // Générer un token même si l'utilisateur n'existe pas (pour éviter l'énumération d'utilisateurs)
                if ($user) {
                    $token = $this->generateSecureToken();
                    $expiresAt = new \DateTimeImmutable('+1 hour');
                    
                    $user->setResetToken($token);
                    $user->setResetTokenExpiresAt($expiresAt);
                    
                    $entityManager->persist($user);
                    $entityManager->flush();
                    
                    // Créer l'URL de réinitialisation
                    $resetUrl = $this->generateUrl('app_reset_password_confirm', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                    
                    // Envoyer l'email
                    $email = (new TemplatedEmail())
                        ->from(new Address('no-reply@digi-3.com', 'Digi-3'))
                        ->to($user->getUserEmail())
                        ->subject('Réinitialisation de votre mot de passe')
                        ->htmlTemplate('emails/reset_password.html.twig')
                        ->context([
                            'firstName' => $user->getUserFirstName(),
                            'resetUrl' => $resetUrl
                        ]);
                    
                    $mailer->send($email);
                    
                    $this->logger->info('Email de réinitialisation envoyé à ' . $user->getUserEmail(), [
                        'user_id' => $user->getId(),
                        'reset_token' => $token,
                        'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                        'reset_url' => $resetUrl
                    ]);
                } else {
                    $this->logger->info('Tentative de réinitialisation pour un email inexistant: ' . $email);
                }
                
                // Toujours afficher le même message, que l'utilisateur existe ou non
                $this->addFlash('success', 'Si votre adresse email est enregistrée, vous recevrez un lien de réinitialisation de mot de passe dans les prochaines minutes.');
                return $this->redirectToRoute('app_auth');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la demande de réinitialisation: ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de l\'email. Veuillez réessayer ultérieurement.');
                return $this->redirectToRoute('app_auth');
            }
        }

        return $this->render('auth/reset_password_request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/auth/reset-password/{token}', name: 'app_reset_password_confirm', methods: ['GET', 'POST'])]
    public function resetPasswordConfirm(
        string $token,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        // Vérifier si le token est valide et non expiré
        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('error', 'Le lien de réinitialisation est invalide ou a expiré. Veuillez effectuer une nouvelle demande.');
            return $this->redirectToRoute('app_auth');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $form->getData();
                
                // Mettre à jour le mot de passe
                $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.');
                return $this->redirectToRoute('app_auth');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la réinitialisation du mot de passe: ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de la réinitialisation de votre mot de passe. Veuillez réessayer.');
            }
        }

        return $this->render('auth/reset_password.html.twig', [
            'form' => $form->createView(),
            'token' => $token,
        ]);
    }
}
