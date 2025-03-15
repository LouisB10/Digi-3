<?php

namespace App\Enum;

enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';
    
    public function getLabel(): string
    {
        return match($this) {
            self::LOW => 'Basse',
            self::MEDIUM => 'Moyenne',
            self::HIGH => 'Haute',
            self::URGENT => 'Urgente',
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::LOW => 'green',
            self::MEDIUM => 'blue',
            self::HIGH => 'orange',
            self::URGENT => 'red',
        };
    }
} 