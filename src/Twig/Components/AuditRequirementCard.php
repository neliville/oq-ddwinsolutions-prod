<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditEvaluation;
use App\Entity\Qse\AuditRequirement;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('AuditRequirementCard')]
final class AuditRequirementCard
{
    public AuditRequirement $requirement;
    public ?AuditEvaluation $evaluation = null;
    public Audit $audit;
    public bool $readOnly = false;

    /** Si false, l’accordéon est replié au chargement (cockpit chapitre long). */
    public bool $expanded = true;
}
