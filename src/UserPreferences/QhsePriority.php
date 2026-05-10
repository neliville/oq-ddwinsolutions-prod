<?php

declare(strict_types=1);

namespace App\UserPreferences;

enum QhsePriority: string
{
    case QUALITY = 'quality';
    case SAFETY = 'safety';
    case ENVIRONMENT = 'environment';
    case COMPLIANCE = 'compliance';
}
