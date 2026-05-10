<?php

declare(strict_types=1);

namespace App\Collaboration;

enum SharedAccessLevel: string
{
    case LECTURE_SEULE = 'lecture_seule';
    case COMMENTAIRE = 'commentaire';
    case CONTRIBUTION_LEGERE = 'contribution_legere';

    public function label(): string
    {
        return match ($this) {
            self::LECTURE_SEULE => 'Lecture seule',
            self::COMMENTAIRE => 'Commentaire',
            self::CONTRIBUTION_LEGERE => 'Contribution légère',
        };
    }
}
