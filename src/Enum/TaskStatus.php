<?php

namespace App\Enum;

enum TaskStatus: string
{
    case NEW = 'new';
    case IN_PROGRESS = 'in_progress';
    case REVIEW = 'review';
    case COMPLETED = 'completed';
    case BLOCKED = 'blocked';
    
    public function getLabel(): string
    {
        return match($this) {
            self::NEW => 'Nouvelle',
            self::IN_PROGRESS => 'En cours',
            self::REVIEW => 'En revue',
            self::COMPLETED => 'Terminée',
            self::BLOCKED => 'Bloquée',
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::NEW => 'blue',
            self::IN_PROGRESS => 'green',
            self::REVIEW => 'orange',
            self::COMPLETED => 'purple',
            self::BLOCKED => 'red',
        };
    }
} 