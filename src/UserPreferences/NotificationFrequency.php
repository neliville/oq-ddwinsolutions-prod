<?php

declare(strict_types=1);

namespace App\UserPreferences;

enum NotificationFrequency: string
{
    case IMMEDIATE = 'immediate';
    case WEEKLY = 'weekly';
}
