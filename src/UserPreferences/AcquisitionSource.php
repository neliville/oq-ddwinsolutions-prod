<?php

declare(strict_types=1);

namespace App\UserPreferences;

enum AcquisitionSource: string
{
    case SEARCH = 'search';
    case LINKEDIN = 'linkedin';
    case WORD_OF_MOUTH = 'word_of_mouth';
    case NEWSLETTER = 'newsletter';
    case YOUTUBE = 'youtube';
    case BLOG = 'blog';
    case TRAINING_AUDIT = 'training_audit';
    case PROFESSIONAL_NETWORK = 'professional_network';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SEARCH => 'Google / moteur de recherche',
            self::LINKEDIN => 'LinkedIn',
            self::WORD_OF_MOUTH => 'Recommandation / bouche-à-oreille',
            self::NEWSLETTER => 'Newsletter',
            self::YOUTUBE => 'YouTube',
            self::BLOG => 'Blog / article',
            self::TRAINING_AUDIT => 'Formation / audit',
            self::PROFESSIONAL_NETWORK => 'Réseau professionnel',
            self::OTHER => 'Autre',
        };
    }
}
