<?php

declare(strict_types=1);

namespace App\Qse\Audit\ViewModel;

use App\Entity\Qse\Audit;
use App\Entity\User;
use App\Qse\Enum\AuditExecutionStatus;
use App\Repository\Qse\AuditActivityLogRepository;
use App\Repository\Qse\AuditEvaluationRepository;
use App\Repository\Qse\AuditRepository;
use App\Repository\Qse\CAPAActionRepository;
use App\Repository\Qse\CockpitMetricsRepository;
use App\Twig\QseTwigExtension;

final class AuditBoardViewModelFactory
{
    private const STALE_DAYS = 30;

    public function __construct(
        private readonly AuditRepository $auditRepository,
        private readonly AuditEvaluationRepository $evaluationRepository,
        private readonly CAPAActionRepository $capaActionRepository,
        private readonly AuditActivityLogRepository $activityLogRepository,
        private readonly CockpitMetricsRepository $cockpitMetricsRepository,
        private readonly AuditActivityTimelinePresenter $timelinePresenter,
        private readonly QseTwigExtension $qseTwigExtension,
    ) {
    }

    public function build(User $owner, AuditBoardFilters $filters): AuditBoardViewModel
    {
        $cockpit = $this->cockpitMetricsRepository->getMetrics($owner);
        $statusCounts = $this->auditRepository->countByStatusForOwner($owner);
        $ncCounts = $this->evaluationRepository->countOpenNcByOwner($owner);
        $progressSum = $this->evaluationRepository->sumProgressByOwner($owner);

        $inProgress = ($statusCounts[AuditExecutionStatus::EN_COURS->value] ?? 0)
            + ($statusCounts[AuditExecutionStatus::PREPARE->value] ?? 0);

        $globalProgress = $progressSum['total'] > 0
            ? round(100.0 * $progressSum['answered'] / $progressSum['total'], 1)
            : 0.0;

        $kpis = new AuditBoardKpis(
            totalAudits: $this->auditRepository->countTotalForOwner($owner),
            inProgress: $inProgress,
            overdue: (int) ($cockpit['staleAuditDrafts'] ?? 0),
            avgCompliancePercent: $cockpit['avgAuditCompliancePercent'] ?? null,
            openMajorNc: $ncCounts['major'],
            openMinorNc: $ncCounts['minor'],
            criticalCapasLinked: $this->capaActionRepository->countOpenCriticalLinkedToAuditsByOwner($owner),
            activeStandards: $this->auditRepository->countActiveStandardsForOwner($owner),
            globalProgressPercent: $globalProgress,
            answeredRequirements: $progressSum['answered'],
            totalRequirements: $progressSum['total'],
        );

        $pageResult = $this->auditRepository->findBoardPage($owner, $filters);
        $audits = $pageResult['items'];
        $totalCount = $pageResult['total'];
        $totalPages = max(1, (int) ceil($totalCount / $filters->perPage));

        $auditIds = array_values(array_filter(array_map(static fn (Audit $a): ?int => $a->getId(), $audits)));
        $standardIds = array_values(array_unique(array_filter(array_map(
            static fn (Audit $a): ?int => $a->getAuditStandard()?->getId(),
            $audits,
        ))));

        $capaCounts = $this->capaActionRepository->countLinkedToAuditIds($auditIds);
        $progressByAudit = $this->evaluationRepository->aggregateProgressByAuditIds($auditIds);
        $requirementsByStandard = $this->evaluationRepository->countRequirementsByStandardIds($standardIds);

        $cutoffStale = (new \DateTimeImmutable())->modify(sprintf('-%d days', self::STALE_DAYS));

        $rows = [];
        foreach ($audits as $audit) {
            $id = $audit->getId();
            if ($id === null) {
                continue;
            }
            $std = $audit->getAuditStandard();
            $stdId = $std?->getId() ?? 0;
            $totalReq = $requirementsByStandard[$stdId] ?? 0;
            $prog = $progressByAudit[$id] ?? ['answered' => 0, 'major' => 0, 'minor' => 0];
            $answered = $prog['answered'];
            $progressPercent = $totalReq > 0 ? round(100.0 * $answered / $totalReq, 1) : 0.0;

            $lastActivity = $audit->getUpdatedAt() ?? $audit->getCreatedAt();
            $isStale = $audit->getStatus() === AuditExecutionStatus::BROUILLON
                && $lastActivity < $cutoffStale;

            $rows[] = new AuditBoardRow(
                audit: $audit,
                label: $this->buildAuditLabel($audit),
                standardCode: (string) ($std?->getCode() ?? ''),
                standardName: (string) ($std?->getName() ?? ''),
                complianceRate: $audit->getGlobalComplianceRate(),
                progressPercent: $progressPercent,
                answeredRequirements: $answered,
                totalRequirements: $totalReq,
                majorNc: $prog['major'],
                minorNc: $prog['minor'],
                capaCount: $capaCounts[$id] ?? 0,
                hasOpenMajorNc: $prog['major'] > 0,
                isStale: $isStale,
                isBlocked: $isStale || $prog['major'] > 0,
                status: $audit->getStatus(),
            );
        }

        $attentionItems = $this->buildAttentionItems($owner, $rows, $kpis);
        $timelineEntries = array_map(
            fn ($log) => $this->timelinePresenter->present($log),
            $this->activityLogRepository->findRecentForOwner($owner, 8),
        );

        $filterOptions = [
            'standards' => array_map(
                static fn (array $s): array => ['code' => $s['code'], 'name' => $s['label']],
                $this->auditRepository->countGroupedByAuditStandardForOwner($owner),
            ),
            'auditors' => $this->auditRepository->findDistinctAuditorsForOwner($owner),
            'statuses' => array_map(
                fn (AuditExecutionStatus $s): array => [
                    'value' => $s->value,
                    'label' => $this->qseTwigExtension->statusLabel($s->value, 'audit_exec'),
                ],
                AuditExecutionStatus::cases(),
            ),
        ];

        return new AuditBoardViewModel(
            kpis: $kpis,
            rows: $rows,
            attentionItems: $attentionItems,
            timelineEntries: $timelineEntries,
            filterOptions: $filterOptions,
            totalCount: $totalCount,
            page: $filters->page,
            perPage: $filters->perPage,
            totalPages: $totalPages,
            capaCountsByAuditId: $capaCounts,
        );
    }

    private function buildAuditLabel(Audit $audit): string
    {
        $company = $audit->getCompanyName() ?? 'Audit';
        $date = $audit->getAuditedAt()?->format('d/m/Y') ?? '';

        return $date !== '' ? $company . ' — ' . $date : $company;
    }

    /**
     * @param list<AuditBoardRow> $rows
     *
     * @return list<AuditBoardAttentionItem>
     */
    private function buildAttentionItems(User $owner, array $rows, AuditBoardKpis $kpis): array
    {
        $items = [];

        foreach ($rows as $row) {
            if ($row->isStale) {
                $items[] = new AuditBoardAttentionItem(
                    type: AuditBoardAttentionItem::TYPE_STALE,
                    title: $row->label,
                    description: 'Brouillon sans activité depuis plus de ' . self::STALE_DAYS . ' jours',
                    auditId: $row->audit->getId(),
                    ctaLabel: 'Continuer',
                    ctaRoute: 'app_qse_audit_show',
                    ctaParams: ['id' => $row->audit->getId()],
                );
            } elseif ($row->hasOpenMajorNc) {
                $items[] = new AuditBoardAttentionItem(
                    type: AuditBoardAttentionItem::TYPE_MAJOR_NC,
                    title: $row->label,
                    description: sprintf('%d NC majeure(s) ouverte(s)', $row->majorNc),
                    auditId: $row->audit->getId(),
                    ctaLabel: 'Corriger',
                    ctaRoute: 'app_qse_audit_show',
                    ctaParams: ['id' => $row->audit->getId()],
                );
            }
            if (\count($items) >= 6) {
                break;
            }
        }

        if ($kpis->criticalCapasLinked > 0 && \count($items) < 6) {
            $items[] = new AuditBoardAttentionItem(
                type: AuditBoardAttentionItem::TYPE_CAPA,
                title: 'CAPA critiques liées aux audits',
                description: sprintf('%d action(s) corrective(s) urgente(s)', $kpis->criticalCapasLinked),
                auditId: null,
                ctaLabel: 'Voir les CAPA',
                ctaRoute: 'app_qse_capa_index',
            );
        }

        $staleAudits = $this->auditRepository->findStaleByOwner($owner, self::STALE_DAYS, 3);
        foreach ($staleAudits as $audit) {
            $id = $audit->getId();
            if ($id === null) {
                continue;
            }
            $already = false;
            foreach ($items as $item) {
                if ($item->auditId === $id) {
                    $already = true;
                    break;
                }
            }
            if ($already) {
                continue;
            }
            $items[] = new AuditBoardAttentionItem(
                type: AuditBoardAttentionItem::TYPE_STALE,
                title: $this->buildAuditLabel($audit),
                description: 'Audit en retard — reprise recommandée',
                auditId: $id,
                ctaLabel: 'Continuer',
                ctaRoute: 'app_qse_audit_show',
                ctaParams: ['id' => $id],
            );
            if (\count($items) >= 8) {
                break;
            }
        }

        return $items;
    }
}
