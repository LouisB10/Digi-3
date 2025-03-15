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
} 