<?php

declare(strict_types=1);

namespace App\Qse\Enum;

enum CapaStatus: string
{
    case BROUILLON = 'brouillon';
    case A_VALIDER = 'a_valider';
    case VALIDEE = 'validee';
    case EN_COURS = 'en_cours';
    case EN_ATTENTE_DE_VERIFICATION = 'en_attente_de_verification';
    case CLOTUREE = 'cloturee';
    case REOUVERTE = 'reouverte';
    case ANNULEE = 'annulee';

    /**
     * @return list<self>
     */
    public static function terminalStatuses(): array
    {
        return [self::CLOTUREE, self::ANNULEE];
    }
}
