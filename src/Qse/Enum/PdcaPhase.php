<?php

declare(strict_types=1);

namespace App\Qse\Enum;

/**
 * Phase PDCA pour regroupement cockpit (mapping métier documenté dans le plan produit).
 */
enum PdcaPhase: string
{
    case PLAN = 'plan';
    case DO = 'do';
    case CHECK = 'check';
    case ACT = 'act';
}
