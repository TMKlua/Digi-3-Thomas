<?php

namespace App\Enum;

enum TaskComplexity: string
{
    case SIMPLE = 'simple';
    case MODERATE = 'moderate';
    case COMPLEX = 'complex';
    case VERY_COMPLEX = 'very_complex';
} 