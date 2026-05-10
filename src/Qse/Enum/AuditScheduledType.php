<?php

declare(strict_types=1);

namespace App\Qse\Enum;

enum AuditScheduledType: string
{
    case INTERNAL = 'internal';
    case SUPPLIER = 'supplier';
    case PROCESS = 'process';
    case SYSTEM = 'system';
    case FIELD = 'field';
}
