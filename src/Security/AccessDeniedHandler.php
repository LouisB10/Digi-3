<?php

namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    private $twig;
    private $logger;
    private $urlGenerator;

    public function __construct(
        Environment $twig,
        LoggerInterface $logger,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->twig = $twig;
        $this->logger = $logger;
        $this->urlGenerator = $urlGenerator;
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        // Journaliser l'accès refusé
        $this->logger->warning('Accès refusé', [
            'path' => $request->getPathInfo(),
            'user' => $request->getUser(),
            'ip' => $request->getClientIp(),
            'exception' => $accessDeniedException->getMessage(),
        ]);

        // Rendre la page d'erreur 403 personnalisée
        $content = $this->twig->render('bundles/TwigBundle/Exception/error403.html.twig');
        
        return new Response($content, Response::HTTP_FORBIDDEN);
    }
} 