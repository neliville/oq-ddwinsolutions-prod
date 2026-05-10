<?php

declare(strict_types=1);

namespace App\Qse\Enum;

enum AuditPlanStatus: string
{
    case BROUILLON = 'brouillon';
    case PLANIFIE = 'planifie';
    case PROGRAMME = 'programme';
    case EN_COURS = 'en_cours';
    case TERMINE = 'termine';
    case ARCHIVE = 'archive';
}
