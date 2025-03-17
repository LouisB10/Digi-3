<?php

namespace App\Enum;

enum UserRole: string
{
    case ADMIN = 'ROLE_ADMIN';
    case RESPONSABLE = 'ROLE_RESPONSABLE';
    case PROJECT_MANAGER = 'ROLE_PROJECT_MANAGER';
    case LEAD_DEVELOPER = 'ROLE_LEAD_DEVELOPER';
    case DEVELOPER = 'ROLE_DEVELOPER';
    case USER = 'ROLE_USER';
    
    public function getLabel(): string
    {
        return match($this) {
            self::ADMIN => 'Administrateur',
            self::RESPONSABLE => 'Responsable',
            self::PROJECT_MANAGER => 'Chef de projet',
            self::LEAD_DEVELOPER => 'Lead développeur',
            self::DEVELOPER => 'Développeur',
            self::USER => 'Utilisateur',
        };
    }
    
    /**
     * Récupère tous les rôles disponibles pour l'affichage dans un formulaire
     * 
     * @param UserRole|null $minRole Le rôle minimum requis pour afficher les rôles (null pour tous)
     * @return array Un tableau associatif [valeur => libellé]
     */
    public static function getChoices(?UserRole $minRole = null): array
    {
        $choices = [];
        $cases = self::cases();
        
        // Trier les rôles par ordre hiérarchique (du plus bas au plus élevé)
        usort($cases, function($a, $b) {
            return self::getRoleWeight($a) <=> self::getRoleWeight($b);
        });
        
        foreach ($cases as $role) {
            // Si un rôle minimum est spécifié, ne pas inclure les rôles supérieurs
            if ($minRole !== null && self::getRoleWeight($role) > self::getRoleWeight($minRole)) {
                continue;
            }
            
            $choices[$role->getLabel()] = $role->value;
        }
        
        return $choices;
    }
    
    /**
     * Détermine si un rôle est supérieur ou égal à un autre
     */
    public static function isGranted(UserRole $userRole, UserRole $requiredRole): bool
    {
        return self::getRoleWeight($userRole) >= self::getRoleWeight($requiredRole);
    }
    
    /**
     * Obtient le poids hiérarchique d'un rôle (plus le nombre est élevé, plus le rôle est important)
     */
    public static function getRoleWeight(UserRole $role): int
    {
        return match($role) {
            self::ADMIN => 50,
            self::RESPONSABLE => 40,
            self::PROJECT_MANAGER => 30,
            self::LEAD_DEVELOPER => 20,
            self::DEVELOPER => 10,
            self::USER => 1,
        };
    }
} 