<?php

declare(strict_types=1);

namespace App\Qse\Audit\ViewModel;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditActivityLog;
use App\Qse\Enum\AuditVerdict;
use App\Qse\Service\AuditEvaluationVerdictHelper;
use App\Repository\Qse\AuditActivityLogRepository;
use App\Repository\Qse\AuditRequirementRepository;

final class AuditCockpitViewModelFactory
{
    public function __construct(
        private readonly AuditRequirementRepository $requirementRepository,
        private readonly AuditActivityLogRepository $activityLogRepository,
    ) {
    }

    public function build(Audit $audit): AuditCockpitMetrics
    {
        $std = $audit->getAuditStandard();
        if ($std === null) {
            return new AuditCockpitMetrics(
                totalRequirements: 0,
                answeredRequirements: 0,
                majorNc: 0,
                minorNc: 0,
                observations: 0,
                toReview: 0,
                conform: 0,
                notApplicable: 0,
                notEvaluated: 0,
                evidenceMissing: 0,
                globalComplianceRate: $audit->getGlobalComplianceRate(),
                verdictCounts: [],
                chapterSummaries: [],
                chartConfig: $this->emptyChartConfig(),
                timelinePreview: [],
            );
        }

        $evaluationsByReqId = [];
        foreach ($audit->getEvaluations() as $ev) {
            $rid = $ev->getRequirement()?->getId();
            if ($rid !== null) {
                $evaluationsByReqId[$rid] = $ev;
            }
        }

        $chapters = $this->requirementRepository->findDistinctChaptersForStandard($std);
        $verdictCounts = [];
        foreach (AuditVerdict::orderedChoices() as $v) {
            $verdictCounts[$v->value] = 0;
        }

        $total = 0;
        $answered = 0;
        $majorNc = 0;
        $minorNc = 0;
        $observations = 0;
        $toReview = 0;
        $conform = 0;
        $notApplicable = 0;
        $notEvaluated = 0;
        $evidenceMissing = 0;
        $chapterSummaries = [];

        foreach ($chapters as $chapter) {
            $requirements = $this->requirementRepository->findByChapterOrderedForStandard($chapter, $std);
            $chTotal = \count($requirements);
            $chAnswered = 0;
            $chConform = 0;
            $chPartiel = 0;
            $chNc = 0;
            $chCounts = [];
            foreach (AuditVerdict::orderedChoices() as $v) {
                $chCounts[$v->value] = 0;
            }

            foreach ($requirements as $req) {
                ++$total;
                $ev = $evaluationsByReqId[$req->getId()] ?? null;
                $v = $ev !== null ? AuditEvaluationVerdictHelper::effectiveVerdict($ev) : null;

                if ($v === null) {
                    ++$notEvaluated;
                    ++$chCounts[AuditVerdict::NOT_EVALUATED->value];
                    ++$verdictCounts[AuditVerdict::NOT_EVALUATED->value];

                    continue;
                }

                ++$chCounts[$v->value];
                ++$verdictCounts[$v->value];

                if ($v === AuditVerdict::NOT_EVALUATED) {
                    ++$notEvaluated;

                    continue;
                }

                ++$answered;
                ++$chAnswered;
                match ($v) {
                    AuditVerdict::CONFORM => (function () use (&$conform, &$chConform): void {
                        ++$conform;
                        ++$chConform;
                    })(),
                    AuditVerdict::OBSERVATION => (function () use (&$observations, &$chPartiel): void {
                        ++$observations;
                        ++$chPartiel;
                    })(),
                    AuditVerdict::TO_REVIEW => (function () use (&$toReview, &$chPartiel): void {
                        ++$toReview;
                        ++$chPartiel;
                    })(),
                    AuditVerdict::MINOR_NC => (function () use (&$minorNc, &$chNc): void {
                        ++$minorNc;
                        ++$chNc;
                    })(),
                    AuditVerdict::MAJOR_NC => (function () use (&$majorNc, &$chNc): void {
                        ++$majorNc;
                        ++$chNc;
                    })(),
                    AuditVerdict::NOT_APPLICABLE => ++$notApplicable,
                    default => null,
                };

                if ($v->suggestsCapa() && ($ev === null || trim((string) $ev->getEvidence()) === '')) {
                    ++$evidenceMissing;
                }
            }

            $applicable = $chConform + $chPartiel + $chNc;
            $chRate = $applicable > 0 ? round(100.0 * $chConform / $applicable, 1) : null;
            $chapterSummaries[$chapter] = [
                'chapter' => $chapter,
                'total' => $chTotal,
                'answered' => $chAnswered,
                'conformRate' => $chRate,
                'verdictCounts' => $chCounts,
            ];
        }

        $logs = $this->activityLogRepository->findRecentForAudit($audit, 8);
        $timelinePreview = [];
        foreach ($logs as $log) {
            $timelinePreview[] = $this->buildTimelineEntry($log);
        }

        return new AuditCockpitMetrics(
            totalRequirements: $total,
            answeredRequirements: $answered,
            majorNc: $majorNc,
            minorNc: $minorNc,
            observations: $observations,
            toReview: $toReview,
            conform: $conform,
            notApplicable: $notApplicable,
            notEvaluated: $notEvaluated,
            evidenceMissing: $evidenceMissing,
            globalComplianceRate: $audit->getGlobalComplianceRate(),
            verdictCounts: $verdictCounts,
            chapterSummaries: $chapterSummaries,
            chartConfig: $this->buildChartConfig($chapterSummaries, $verdictCounts),
            timelinePreview: $timelinePreview,
        );
    }

    /**
     * @param array<string, array<string, mixed>> $chapterSummaries
     * @param array<string, int>                  $verdictCounts
     *
     * @return array<string, mixed>
     */
    private function buildChartConfig(array $chapterSummaries, array $verdictCounts): array
    {
        $labels = [];
        $radarValues = [];
        foreach ($chapterSummaries as $row) {
            $labels[] = (string) $row['chapter'];
            $radarValues[] = $row['conformRate'] ?? 0.0;
        }

        return [
            'radar' => [
                'labels' => $labels,
                'values' => $radarValues,
            ],
            'distribution' => [
                'labels' => ['Conforme', 'Observation', 'À revoir', 'NC mineure', 'NC majeure', 'N/A', 'Non évalué'],
                'values' => [
                    $verdictCounts[AuditVerdict::CONFORM->value] ?? 0,
                    $verdictCounts[AuditVerdict::OBSERVATION->value] ?? 0,
                    $verdictCounts[AuditVerdict::TO_REVIEW->value] ?? 0,
                    $verdictCounts[AuditVerdict::MINOR_NC->value] ?? 0,
                    $verdictCounts[AuditVerdict::MAJOR_NC->value] ?? 0,
                    $verdictCounts[AuditVerdict::NOT_APPLICABLE->value] ?? 0,
                    $verdictCounts[AuditVerdict::NOT_EVALUATED->value] ?? 0,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyChartConfig(): array
    {
        return [
            'radar' => ['labels' => [], 'values' => []],
            'distribution' => ['labels' => [], 'values' => []],
        ];
    }

    /**
     * Construit une entrée de timeline orientée métier (premium SaaS) à partir
     * du log brut. Les libellés techniques (snake_case) ne fuitent jamais vers la vue.
     *
     * @return array{
     *     action: string,
     *     time: string,
     *     date: string,
     *     iso8601: string,
     *     title: string,
     *     detail: ?string,
     *     icon: string,
     *     tone: string,
     *     actor: ?string
     * }
     */
    private function buildTimelineEntry(AuditActivityLog $log): array
    {
        $createdAt = $log->getCreatedAt();
        $action = $log->getAction();
        $payload = $log->getPayload() ?? [];
        $req = $log->getAuditEvaluation()?->getRequirement();
        $isoArticle = $req?->getIsoArticle() ?? (\is_string($payload['iso_article'] ?? null) ? $payload['iso_article'] : null);

        $verdictValue = \is_string($payload['verdict'] ?? null) ? $payload['verdict'] : null;
        $verdict = $verdictValue !== null ? AuditVerdict::tryFrom($verdictValue) : null;

        [$title, $detail, $icon, $tone] = $this->resolveActionPresentation($action, $verdict, $isoArticle);

        $actor = null;
        $user = $log->getActor();
        if ($user !== null) {
            $email = (string) ($user->getEmail() ?? '');
            // Affichage compact : on garde la part avant @ si l’email est dispo.
            if ($email !== '') {
                $atPos = mb_strpos($email, '@');
                $actor = $atPos !== false ? mb_substr($email, 0, $atPos) : $email;
            }
        }

        return [
            'action' => $action,
            'time' => $createdAt->format('H:i'),
            'date' => $createdAt->format('d/m/Y'),
            'iso8601' => $createdAt->format(\DateTimeInterface::ATOM),
            'title' => $title,
            'detail' => $detail,
            'icon' => $icon,
            'tone' => $tone,
            'actor' => $actor,
        ];
    }

    /**
     * Mapping action métier → (titre, détail, icône Lucide, ton sémantique).
     * Fallback humanisé (jamais de snake_case affiché).
     *
     * @return array{0: string, 1: ?string, 2: string, 3: string}
     */
    private function resolveActionPresentation(string $action, ?AuditVerdict $verdict, ?string $isoArticle): array
    {
        $articleSuffix = $isoArticle !== null && $isoArticle !== '' ? sprintf('Chapitre %s', $isoArticle) : null;

        switch ($action) {
            case 'evaluation_saved':
                $title = $articleSuffix !== null ? $articleSuffix.' mis à jour' : 'Évaluation enregistrée';

                return match ($verdict) {
                    AuditVerdict::CONFORM => [$title, 'Conformité enregistrée', 'lucide:circle-check', 'success'],
                    AuditVerdict::OBSERVATION => [$title, 'Observation enregistrée', 'lucide:eye', 'info'],
                    AuditVerdict::TO_REVIEW => [$title, 'Marqué à revoir', 'lucide:circle-help', 'warning'],
                    AuditVerdict::MINOR_NC => [$title, 'Non-conformité mineure détectée', 'lucide:triangle-alert', 'warning'],
                    AuditVerdict::MAJOR_NC => [$title, 'Non-conformité majeure détectée', 'lucide:shield-alert', 'danger'],
                    AuditVerdict::NOT_APPLICABLE => [$title, 'Marqué non applicable', 'lucide:circle-slash', 'neutral'],
                    AuditVerdict::NOT_EVALUATED, null => [$title, 'Évaluation initialisée', 'lucide:circle-dashed', 'neutral'],
                };

            case 'capa_created':
                return ['CAPA créée', $articleSuffix !== null ? 'À partir du '.lcfirst($articleSuffix) : 'Action corrective lancée', 'lucide:clipboard-list', 'info'];

            case 'capa_updated':
                return ['CAPA mise à jour', $articleSuffix, 'lucide:clipboard-pen', 'info'];

            case 'capa_closed':
                return ['CAPA clôturée', $articleSuffix, 'lucide:clipboard-check', 'success'];

            case 'audit_created':
                return ['Audit créé', null, 'lucide:file-plus', 'info'];

            case 'audit_validated':
                return ['Audit validé', 'Synthèse verrouillée', 'lucide:shield-check', 'success'];

            case 'audit_reopened':
                return ['Audit rouvert', null, 'lucide:rotate-ccw', 'warning'];

            case 'non_conformity_detected':
                return ['Non-conformité détectée', $articleSuffix, 'lucide:shield-alert', 'danger'];

            case 'evidence_added':
                return ['Preuve ajoutée', $articleSuffix, 'lucide:paperclip', 'info'];

            case 'comment_added':
                return ['Commentaire ajouté', $articleSuffix, 'lucide:message-square', 'neutral'];

            case 'chapter_validated':
                return [$articleSuffix !== null ? $articleSuffix.' validé' : 'Chapitre validé', 'Conformité confirmée', 'lucide:check-check', 'success'];
        }

        // Fallback générique : on humanise le code action pour ne jamais afficher de snake_case brut.
        $humanized = ucfirst(str_replace('_', ' ', $action));

        return [$humanized, $articleSuffix, 'lucide:activity', 'neutral'];
    }
}
