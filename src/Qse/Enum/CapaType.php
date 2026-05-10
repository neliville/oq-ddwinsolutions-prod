<?php

declare(strict_types=1);

namespace App\Qse\Enum;

enum CapaType: string
{
    case CORRECTIVE = 'corrective';
    case PREVENTIVE = 'preventive';
    case MAITRISE = 'maitrise';
}
