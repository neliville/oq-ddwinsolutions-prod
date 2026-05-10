<?php

declare(strict_types=1);

namespace App\UserPreferences;

enum PrimaryStandard: string
{
    case ISO_9001 = 'iso_9001';
    case ISO_14001 = 'iso_14001';
    case ISO_45001 = 'iso_45001';
    case MULTI_REF = 'multi_ref';
    case UNDEFINED = 'undefined';

    public function label(): string
    {
        return match ($this) {
            self::ISO_9001 => 'ISO 9001',
            self::ISO_14001 => 'ISO 14001',
            self::ISO_45001 => 'ISO 45001',
            self::MULTI_REF => 'Multi-référentiels',
            self::UNDEFINED => 'Pas encore défini',
        };
    }
}
