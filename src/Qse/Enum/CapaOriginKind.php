<?php

declare(strict_types=1);

namespace App\Qse\Enum;

enum CapaOriginKind: string
{
    case SYSTEM = 'system';
    case CUSTOM = 'custom';
}
