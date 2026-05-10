<?php

declare(strict_types=1);

namespace App\Qse\Enum;

enum AuditFindingType: string
{
    case MAJOR_NC = 'major_nc';
    case MINOR_NC = 'minor_nc';
    case OBSERVATION = 'observation';
    case IMPROVEMENT = 'improvement';
    case STRENGTH = 'strength';
}
