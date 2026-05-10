<?php

declare(strict_types=1);

namespace App\Qse\Enum;

enum AuditExecutionStatus: string
{
    case BROUILLON = 'brouillon';
    case PREPARE = 'prepare';
    case EN_COURS = 'en_cours';
    case TERMINE = 'termine';
    case VALIDE = 'valide';
    case ARCHIVE = 'archive';
}
