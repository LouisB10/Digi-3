<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Psr\Log\LoggerInterface;

class PermissionService
{
    private $security;
    private $logger;

    public function __construct(Security $security, LoggerInterface $logger)
    {
        $this->security = $security;
        $this->logger = $logger;
    }

    /**
     * Vérifie si l'utilisateur peut effectuer une action sur une ressource
     */
    public function canPerform(string $action, string $resource): bool
    {
        // Récupérer l'utilisateur connecté
        $user = $this->security->getUser();
        
        // Si pas d'utilisateur connecté, refuser l'accès
        if (!$user) {
            $this->logger->info('Tentative d\'accès sans authentification', [
                'action' => $action,
                'resource' => $resource,
            ]);
            return false;
        }
        
        // Vérifier les permissions selon l'action et la ressource
        $result = match ($resource) {
            'user' => $this->canPerformOnUser($action),
            'project' => $this->canPerformOnProject($action),
            'customer' => $this->canPerformOnCustomer($action),
            'parameter' => $this->canPerformOnParameter($action),
            default => false,
        };
        
        $this->logger->debug('Vérification de permission', [
            'user' => $user->getUserIdentifier(),
            'action' => $action,
            'resource' => $resource,
            'result' => $result,
        ]);
        
        return $result;
    }

    /**
     * Vérifie si l'utilisateur peut effectuer une action sur les utilisateurs
     */
    private function canPerformOnUser(string $action): bool
    {
        return match ($action) {
            'view' => $this->security->isGranted('ROLE_PROJECT_MANAGER'),
            'list' => $this->security->isGranted('ROLE_PROJECT_MANAGER'),
            'edit' => $this->security->isGranted('ROLE_RESPONSABLE') || $this->security->isGranted('ROLE_ADMIN'),
            'create' => $this->security->isGranted('ROLE_RESPONSABLE') || $this->security->isGranted('ROLE_ADMIN'),
            'delete' => $this->security->isGranted('ROLE_ADMIN'),
            default => false,
        };
    }

    /**
     * Vérifie si l'utilisateur peut effectuer une action sur les projets
     */
    private function canPerformOnProject(string $action): bool
    {
        return match ($action) {
            'view' => true, // Tout utilisateur authentifié peut voir les projets
            'list' => true, // Tout utilisateur authentifié peut voir la liste des projets
            'edit' => $this->security->isGranted('ROLE_PROJECT_MANAGER'),
            'create' => $this->security->isGranted('ROLE_PROJECT_MANAGER'),
            'delete' => $this->security->isGranted('ROLE_RESPONSABLE') || $this->security->isGranted('ROLE_ADMIN'),
            default => false,
        };
    }

    /**
     * Vérifie si l'utilisateur peut effectuer une action sur les clients
     */
    private function canPerformOnCustomer(string $action): bool
    {
        return match ($action) {
            'view' => $this->security->isGranted('ROLE_PROJECT_MANAGER'),
            'list' => $this->security->isGranted('ROLE_PROJECT_MANAGER'),
            'edit' => $this->security->isGranted('ROLE_RESPONSABLE') || $this->security->isGranted('ROLE_ADMIN'),
            'create' => $this->security->isGranted('ROLE_RESPONSABLE') || $this->security->isGranted('ROLE_ADMIN'),
            'delete' => $this->security->isGranted('ROLE_ADMIN'),
            default => false,
        };
    }

    /**
     * Vérifie si l'utilisateur peut effectuer une action sur les paramètres de l'application
     */
    private function canPerformOnParameter(string $action): bool
    {
        return match ($action) {
            'view' => true, // Tout utilisateur authentifié peut voir les paramètres
            'edit' => $this->security->isGranted('ROLE_ADMIN'),
            default => false,
        };
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $role): bool
    {
        return $this->security->isGranted($role);
    }

    /**
     * Récupère les rôles de l'utilisateur courant
     */
    public function getUserRoles(): array
    {
        $user = $this->security->getUser();
        if (!$user) {
            return [];
        }
        
        return $user->getRoles();
    }
}
