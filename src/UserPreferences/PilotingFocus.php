<?php

declare(strict_types=1);

namespace App\UserPreferences;

enum PilotingFocus: string
{
    case AUDIT = 'audit';
    case CAPA = 'capa';
    case RISK = 'risk';
    case PDCA = 'pdca';
    case COMPLIANCE = 'compliance';
    case CERTIFICATION_PREP = 'certification_prep';
    case GLOBAL_PILOTING = 'global_piloting';
    case CONTINUOUS_IMPROVEMENT = 'continuous_improvement';

    public function label(): string
    {
        return match ($this) {
            self::AUDIT => 'Audit interne',
            self::CAPA => 'CAPA / actions correctives',
            self::RISK => 'Gestion des risques',
            self::COMPLIANCE => 'Conformité ISO',
            self::CERTIFICATION_PREP => 'Préparation certification',
            self::GLOBAL_PILOTING => 'Pilotage QHSE global',
            self::CONTINUOUS_IMPROVEMENT => 'Amélioration continue',
            self::PDCA => 'PDCA',
        };
    }
}
