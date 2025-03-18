<?php

namespace App\Security;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AppCustomAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_auth';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Cette méthode détermine si l'authenticator doit être utilisé pour la requête actuelle
     */
    public function supports(Request $request): bool
    {
        // Vérifier si c'est une requête POST vers la route de login
        $isLoginRoute = $request->attributes->get('_route') === self::LOGIN_ROUTE;
        $isPost = $request->isMethod('POST');
        
        // Journaliser pour le débogage
        if ($isLoginRoute && $isPost) {
            $this->logger->debug('AppCustomAuthenticator vérifie la requête', [
                'route' => $request->attributes->get('_route'),
                'method' => $request->getMethod(),
                'action' => $request->request->get('action'),
                'has_login_form' => $request->request->has('login_form'),
                'request_content' => $request->request->all(),
            ]);
        }
        
        // Vérifier si c'est une tentative de connexion (soit par action=login, soit par la présence du formulaire login_form)
        $isLoginAttempt = $request->request->get('action') === 'login' || $request->request->has('login_form');
        
        $supports = $isLoginRoute && $isPost && $isLoginAttempt;
        
        if ($supports) {
            $this->logger->debug('AppCustomAuthenticator supporte cette requête');
        } else {
            $this->logger->debug('AppCustomAuthenticator ne supporte pas cette requête', [
                'isLoginRoute' => $isLoginRoute,
                'isPost' => $isPost,
                'isLoginAttempt' => $isLoginAttempt,
            ]);
        }
        
        return $supports;
    }

    public function authenticate(Request $request): Passport
    {
        try {
            // Récupérer les données du formulaire
            $formData = $request->request->all('login_form');
            
            $this->logger->debug('Tentative d\'authentification', [
                'form_data' => $formData,
                'request_data' => $request->request->all(),
            ]);
            
            if (!is_array($formData) || empty($formData)) {
                $this->logger->error('Formulaire de connexion invalide ou vide', [
                    'formData' => $formData,
                    'request' => $request->request->all(),
                ]);
                throw new CustomUserMessageAuthenticationException('Problème lors de la soumission du formulaire. Veuillez réessayer.');
            }
            
            $email = $formData['email'] ?? '';
            $password = $formData['password'] ?? '';
            $csrfToken = $formData['_token'] ?? '';
            
            $this->logger->debug('Données d\'authentification', [
                'email' => $email,
                'has_password' => !empty($password),
                'has_csrf_token' => !empty($csrfToken),
            ]);
            
            // Vérifications des champs obligatoires
            if (empty($email)) {
                throw new CustomUserMessageAuthenticationException('Veuillez saisir votre adresse email pour vous connecter.');
            }
            
            if (empty($password)) {
                throw new CustomUserMessageAuthenticationException('Veuillez saisir votre mot de passe pour vous connecter.');
            }

            // Stocker l'email pour l'afficher en cas d'erreur
            if ($request->hasSession()) {
                $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);
            }

            $badges = [];
            
            // Ajouter le badge CSRF
            if (!empty($csrfToken)) {
                $badges[] = new CsrfTokenBadge('login_form', $csrfToken);
                $this->logger->debug('Badge CSRF ajouté', [
                    'token_id' => 'login_form',
                    'token' => substr($csrfToken, 0, 8) . '...',
                ]);
            } else {
                $this->logger->warning('Tentative de connexion sans token CSRF', [
                    'email' => $email,
                    'ip' => $request->getClientIp(),
                ]);
            }
            
            // Ajouter le badge RememberMe si la case est cochée
            if (isset($formData['remember_me']) && $formData['remember_me']) {
                $badges[] = new RememberMeBadge();
                $this->logger->debug('Badge RememberMe ajouté (case cochée)');
            } else {
                // Ajouter quand même le badge RememberMe pour que l'option soit disponible
                $badges[] = new RememberMeBadge();
                $this->logger->debug('Badge RememberMe ajouté (par défaut)');
            }

            $this->logger->debug('Création du passeport d\'authentification', [
                'email' => $email,
                'badges' => array_map(fn($badge) => get_class($badge), $badges),
            ]);

            return new Passport(
                new UserBadge($email),
                new PasswordCredentials($password),
                $badges
            );
        } catch (CustomUserMessageAuthenticationException $e) {
            // Rethrow custom exceptions
            $this->logger->warning('Exception d\'authentification personnalisée', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            // Log unexpected exceptions and convert to a user-friendly message
            $this->logger->error('Erreur inattendue lors de l\'authentification: ' . $e->getMessage(), [
                'exception' => $e,
                'ip' => $request->getClientIp(),
            ]);
            throw new CustomUserMessageAuthenticationException('Une erreur technique est survenue lors de la connexion. Veuillez réessayer ultérieurement ou contacter le support si le problème persiste.');
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            $this->logger->info('Redirection vers le chemin cible', [
                'target_path' => $targetPath,
            ]);
            return new RedirectResponse($targetPath);
        }

        $this->logger->info('Authentification réussie', [
            'user' => $token->getUserIdentifier(),
            'roles' => $token->getRoleNames(),
            'ip' => $request->getClientIp(),
        ]);

        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        try {
            // Récupérer l'email depuis le formulaire de manière sécurisée
            $formData = $request->request->all('login_form');
            $email = is_array($formData) && isset($formData['email']) ? $formData['email'] : 'unknown';
            
            $this->logger->warning('Échec d\'authentification', [
                'message' => $exception->getMessage(),
                'class' => get_class($exception),
                'email' => $email,
                'ip' => $request->getClientIp(),
            ]);
            
            // Stocker l'erreur dans la session pour l'afficher sur la page de connexion
            if ($request->hasSession()) {
                $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
            }

            // Rediriger vers la page de connexion sans paramètre pour éviter les redirections en boucle
            return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
        } catch (\Exception $e) {
            // En cas d'erreur, journaliser et rediriger vers la page de connexion
            $this->logger->error('Erreur lors du traitement de l\'échec d\'authentification: ' . $e->getMessage(), [
                'exception' => $e,
                'ip' => $request->getClientIp(),
            ]);
            return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
        }
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}