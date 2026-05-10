<?php

declare(strict_types=1);

namespace App\Qse\Enum;

enum RiskEntryStatus: string
{
    case IDENTIFIE = 'identifie';
    case EN_ANALYSE = 'en_analyse';
    case MAITRISE = 'maitrise';
    case SOUS_SURVEILLANCE = 'sous_surveillance';
    case CRITIQUE = 'critique';
    case CLOTURE = 'cloture';
}
