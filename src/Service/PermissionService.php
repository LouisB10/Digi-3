<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Customers;
use App\Enum\UserRole;
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
        
        // Si l'utilisateur est admin, autoriser toutes les actions
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }
        
        // Vérifier les permissions selon l'action et la ressource
        $result = match ($resource) {
            'user' => $this->canPerformOnUser($action),
            'project' => $this->canPerformOnProject($action),
            'customer' => $this->canPerformOnCustomer($action),
            'parameter' => $this->canPerformOnParameter($action),
            'task' => $this->canPerformOnTask($action),
            'dashboard' => $this->canPerformOnDashboard($action),
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
            'view' => $this->security->isGranted('ROLE_RESPONSABLE') || $this->security->isGranted('ROLE_PROJECT_MANAGER'),
            'list' => $this->security->isGranted('ROLE_RESPONSABLE') || $this->security->isGranted('ROLE_PROJECT_MANAGER'),
            'edit' => $this->security->isGranted('ROLE_RESPONSABLE'),
            'create' => $this->security->isGranted('ROLE_RESPONSABLE'),
            'delete' => $this->security->isGranted('ROLE_RESPONSABLE'),
            'edit_role' => $this->security->isGranted('ROLE_ADMIN'),
            'delete_admin' => $this->security->isGranted('ROLE_ADMIN'),
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
            'delete' => $this->security->isGranted('ROLE_PROJECT_MANAGER'),
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
            'edit' => $this->security->isGranted('ROLE_RESPONSABLE'),
            'create' => $this->security->isGranted('ROLE_RESPONSABLE'),
            'delete' => $this->security->isGranted('ROLE_RESPONSABLE'),
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
            'config' => $this->security->isGranted('ROLE_ADMIN'), // Configuration de l'application
            default => false,
        };
    }

    /**
     * Vérifie si l'utilisateur peut effectuer une action sur les tâches
     */
    private function canPerformOnTask(string $action): bool
    {
        return match ($action) {
            'view' => true, // Tout utilisateur authentifié peut voir les tâches
            'list' => true, // Tout utilisateur authentifié peut voir la liste des tâches
            'edit' => true, // Tout utilisateur authentifié peut modifier les tâches
            'create' => $this->security->isGranted('ROLE_LEAD_DEVELOPER'), // À partir de lead développeur
            'delete' => $this->security->isGranted('ROLE_PROJECT_MANAGER'), // À partir de chef de projet
            'assign' => $this->security->isGranted('ROLE_PROJECT_MANAGER'), // À partir de chef de projet
            default => false,
        };
    }

    /**
     * Vérifie si l'utilisateur peut effectuer une action sur le dashboard et les KPI
     */
    private function canPerformOnDashboard(string $action): bool
    {
        return match ($action) {
            'view' => true, // Tout utilisateur authentifié peut voir le dashboard
            'edit' => $this->security->isGranted('ROLE_PROJECT_MANAGER'), // À partir de chef de projet
            'create' => $this->security->isGranted('ROLE_PROJECT_MANAGER'), // À partir de chef de projet
            'delete' => $this->security->isGranted('ROLE_PROJECT_MANAGER'), // À partir de chef de projet
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

    /**
     * Vérifie si l'utilisateur courant peut modifier un utilisateur spécifique
     */
    public function canEditUser(User $targetUser): bool
    {
        $currentUser = $this->security->getUser();
        
        // Si pas d'utilisateur connecté, refuser l'accès
        if (!$currentUser instanceof User) {
            return false;
        }
        
        // Si l'utilisateur est admin, autoriser
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }
        
        // Si l'utilisateur essaie de se modifier lui-même, autoriser
        if ($currentUser->getId() === $targetUser->getId()) {
            return true;
        }
        
        // Si l'utilisateur n'a pas le rôle RESPONSABLE, refuser
        if (!$this->security->isGranted('ROLE_RESPONSABLE')) {
            return false;
        }
        
        // Vérifier la hiérarchie des rôles
        $currentUserRole = $currentUser->getUserRole();
        $targetUserRole = $targetUser->getUserRole();
        
        if (!$currentUserRole || !$targetUserRole) {
            return $this->security->isGranted('ROLE_RESPONSABLE');
        }
        
        // Un responsable ne peut pas modifier un administrateur
        if ($targetUserRole === UserRole::ADMIN) {
            return false;
        }
        
        // Un utilisateur ne peut pas modifier un utilisateur avec un rôle supérieur
        return UserRole::getRoleWeight($currentUserRole) >= UserRole::getRoleWeight($targetUserRole);
    }

    /**
     * Vérifie si l'utilisateur courant peut supprimer un utilisateur spécifique
     */
    public function canDeleteUser(User $targetUser): bool
    {
        $currentUser = $this->security->getUser();
        
        // Si pas d'utilisateur connecté, refuser l'accès
        if (!$currentUser instanceof User) {
            return false;
        }
        
        // Si l'utilisateur est admin, autoriser (sauf pour se supprimer lui-même)
        if ($this->security->isGranted('ROLE_ADMIN') && $currentUser->getId() !== $targetUser->getId()) {
            return true;
        }
        
        // Un utilisateur ne peut pas se supprimer lui-même
        if ($currentUser->getId() === $targetUser->getId()) {
            return false;
        }
        
        // Si l'utilisateur n'a pas le rôle RESPONSABLE, refuser
        if (!$this->security->isGranted('ROLE_RESPONSABLE')) {
            return false;
        }
        
        // Vérifier la hiérarchie des rôles
        $currentUserRole = $currentUser->getUserRole();
        $targetUserRole = $targetUser->getUserRole();
        
        if (!$currentUserRole || !$targetUserRole) {
            return $this->security->isGranted('ROLE_RESPONSABLE');
        }
        
        // Un responsable ne peut pas supprimer un administrateur
        if ($targetUserRole === UserRole::ADMIN) {
            return false;
        }
        
        // Un utilisateur ne peut pas supprimer un utilisateur avec un rôle supérieur
        return UserRole::getRoleWeight($currentUserRole) >= UserRole::getRoleWeight($targetUserRole);
    }

    /**
     * Vérifie si l'utilisateur courant peut modifier un client spécifique
     */
    public function canEditCustomer(Customers $customer): bool
    {
        // Pour les clients, la permission est basée uniquement sur le rôle
        return $this->canPerform('edit', 'customer');
    }

    /**
     * Vérifie si l'utilisateur courant peut supprimer un client spécifique
     */
    public function canDeleteCustomer(Customers $customer): bool
    {
        // Pour les clients, la permission est basée uniquement sur le rôle
        return $this->canPerform('delete', 'customer');
    }
}
