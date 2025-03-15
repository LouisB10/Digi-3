<?php

namespace App\Enum;

enum TaskComplexity: string
{
    case SIMPLE = 'simple';
    case MODERATE = 'moderate';
    case COMPLEX = 'complex';
    case VERY_COMPLEX = 'very_complex';
    
    public function getLabel(): string
    {
        return match($this) {
            self::SIMPLE => 'Simple',
            self::MODERATE => 'Modérée',
            self::COMPLEX => 'Complexe',
            self::VERY_COMPLEX => 'Très complexe',
        };
    }
    
    public function getEstimatedHours(): int
    {
        return match($this) {
            self::SIMPLE => 4,
            self::MODERATE => 8,
            self::COMPLEX => 16,
            self::VERY_COMPLEX => 32,
        };
    }
} 