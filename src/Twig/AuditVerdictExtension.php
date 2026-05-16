<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Qse\AuditEvaluation;
use App\Qse\Enum\AuditVerdict;
use App\Qse\Service\AuditEvaluationVerdictHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AuditVerdictExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('audit_effective_verdict', $this->effectiveVerdict(...)),
            new TwigFunction('audit_verdict_label', $this->verdictLabel(...)),
            new TwigFunction('audit_verdict_suggests_capa', $this->suggestsCapa(...)),
            new TwigFunction('audit_verdict_requires_auto_capa', $this->requiresAutoCapa(...)),
            new TwigFunction('audit_verdict_choices', static fn (): array => AuditVerdict::orderedChoices()),
        ];
    }

    public function effectiveVerdict(?AuditEvaluation $evaluation): ?AuditVerdict
    {
        if ($evaluation === null) {
            return null;
        }

        return AuditEvaluationVerdictHelper::effectiveVerdict($evaluation);
    }

    public function verdictLabel(?AuditVerdict $verdict): string
    {
        return $verdict?->label() ?? '—';
    }

    public function suggestsCapa(?AuditVerdict $verdict): bool
    {
        return $verdict?->suggestsCapa() ?? false;
    }

    public function requiresAutoCapa(?AuditVerdict $verdict): bool
    {
        return $verdict?->requiresAutoCapa() ?? false;
    }
}
