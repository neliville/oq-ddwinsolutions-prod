<?php

declare(strict_types=1);

namespace App\Qse\Enum;

enum RiskCategory: string
{
    case QUALITY = 'Q';
    case SAFETY = 'S';
    case ENVIRONMENT = 'E';
    case COMPLIANCE = 'compliance';
    case CUSTOMER = 'customer';
    case SUPPLIER = 'supplier';
}
