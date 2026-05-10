<?php

declare(strict_types=1);

namespace App\Collaboration;

enum CollaboratorInvitationRole: string
{
    case LECTEUR = 'lecteur';
    case CONTRIBUTEUR = 'contributeur';
    case RESPONSABLE = 'responsable';

    public function label(): string
    {
        return match ($this) {
            self::LECTEUR => 'Lecteur',
            self::CONTRIBUTEUR => 'Contributeur',
            self::RESPONSABLE => 'Responsable',
        };
    }
}
