<?php

namespace App\Enum;

enum ProjectStatus: string
{
    case NEW = 'new';
    case IN_PROGRESS = 'in_progress';
    case ON_HOLD = 'on_hold';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    
    public function getLabel(): string
    {
        return match($this) {
            self::NEW => 'Nouveau',
            self::IN_PROGRESS => 'En cours',
            self::ON_HOLD => 'En attente',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::NEW => 'blue',
            self::IN_PROGRESS => 'green',
            self::ON_HOLD => 'orange',
            self::COMPLETED => 'purple',
            self::CANCELLED => 'red',
        };
    }
} 