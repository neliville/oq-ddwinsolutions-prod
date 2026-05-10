<?php

declare(strict_types=1);

namespace App\UserPreferences;

enum CompanySize: string
{
    /** @deprecated Ancienne granularité — conservé pour hydratation si migration non appliquée */
    case TPE = 'tpe';
    /** @deprecated */
    case PME = 'pme';
    /** @deprecated */
    case ETI = 'eti';
    /** @deprecated */
    case LARGE = 'large';

    case SOLO = 'solo';
    case P2_10 = 'p2_10';
    case P11_50 = 'p11_50';
    case P51_250 = 'p51_250';
    case P251_1000 = 'p251_1000';
    case P1000_PLUS = 'p1000_plus';

    public function label(): string
    {
        return match ($this) {
            self::TPE => 'TPE (ancien)',
            self::PME => 'PME (ancien)',
            self::ETI => 'ETI (ancien)',
            self::LARGE => 'Grand groupe (ancien)',
            self::SOLO => 'Moi uniquement',
            self::P2_10 => '2 à 10 personnes',
            self::P11_50 => '11 à 50 personnes',
            self::P51_250 => '51 à 250 personnes',
            self::P251_1000 => '251 à 1000 personnes',
            self::P1000_PLUS => '1000+ personnes',
        };
    }
}
