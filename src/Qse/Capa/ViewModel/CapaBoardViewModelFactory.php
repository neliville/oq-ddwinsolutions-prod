<?php

declare(strict_types=1);

namespace App\Qse\Capa\ViewModel;

use App\Entity\Qse\CAPAAction;
use App\Entity\User;
use App\Qse\Dto\ActivityItem;
use App\Qse\Enum\CapaStatus;
use App\Qse\Enum\CapaType;
use App\Qse\Service\QseActivityAggregator;
use App\Repository\Qse\CAPAActionRepository;
use App\Repository\Qse\CockpitMetricsRepository;
use App\Twig\QseTwigExtension;

final class CapaBoardViewModelFactory
{
    private const WORKFLOW_TOTAL = 6;

    /** @var array<string, int> */
    private const WORKFLOW_STEP = [
        CapaStatus::BROUILLON->value => 1,
        CapaStatus::A_VALIDER->value => 2,
        CapaStatus::VALIDEE->value => 3,
        CapaStatus::EN_COURS->value => 4,
        CapaStatus::REOUVERTE->value => 4,
        CapaStatus::EN_ATTENTE_DE_VERIFICATION->value => 5,
        CapaStatus::CLOTUREE->value => 6,
        CapaStatus::ANNULEE->value => 0,
    ];

    /** @var array<string, string> */
    private const CRITICALITY_LABELS = [
        'low' => 'Faible',
        'medium' => 'Moyenne',
        'high' => 'Élevée',
        'critical' => 'Critique',
    ];

    /** @var array<string, string> */
    private const TOOL_LABELS = [
        'ishikawa' => 'Ishikawa',
        'five_why' => '5 Pourquoi',
        'eight_d' => '8D',
        'amdec' => 'AMDEC',
        'pareto' => 'Pareto',
        'qqoqccp' => 'QQOQCCP',
        'risk' => 'Risques',
    ];

    public function __construct(
        private readonly CAPAActionRepository $capaRepository,
        private readonly CockpitMetricsRepository $cockpitMetricsRepository,
        private readonly QseActivityAggregator $activityAggregator,
        private readonly QseTwigExtension $qseTwigExtension,
    ) {
    }

    public function build(User $owner, CapaBoardFilters $filters): CapaBoardViewModel
    {
        $cockpit = $this->cockpitMetricsRepository->getMetrics($owner);
        $statusCounts = $this->capaRepository->countByStatusForOwner($owner);

        $kpis = new CapaBoardKpis(
            totalCapas: $this->capaRepository->countTotalForOwner($owner),
            openCapas: $this->capaRepository->countOpenForOwner($owner),
            overdue: (int) ($cockpit['overdueOpenCapas'] ?? 0),
            awaitingValidation: $statusCounts[CapaStatus::A_VALIDER->value] ?? 0,
            awaitingVerification: (int) ($cockpit['capasAwaitingVerification'] ?? 0),
            dueNext7Days: (int) ($cockpit['dueCapaNext7Days'] ?? 0),
        );

        $pageResult = $this->capaRepository->findBoardPage($owner, $filters);
        $capas = $pageResult['items'];
        $totalCount = $pageResult['total'];
        $totalPages = max(1, (int) ceil($totalCount / $filters->perPage));

        $today = new \DateTimeImmutable('today');
        $dueSoonLimit = $today->modify('+7 days');

        $rows = [];
        foreach ($capas as $capa) {
            $rows[] = $this->buildRow($capa, $today, $dueSoonLimit);
        }

        $attentionItems = $this->buildAttentionItems($owner, $rows, $kpis);
        $activityItems = array_values(array_filter(
            $this->activityAggregator->recent($owner, 12),
            static fn (ActivityItem $item): bool => $item->type === 'capa',
        ));

        $filterOptions = [
            'statuses' => array_map(
                fn (CapaStatus $s): array => [
                    'value' => $s->value,
                    'label' => $this->qseTwigExtension->statusLabel($s->value, 'capa'),
                ],
                CapaStatus::cases(),
            ),
            'types' => array_map(
                fn (CapaType $t): array => [
                    'value' => $t->value,
                    'label' => $this->qseTwigExtension->capaTypeLabel($t->value),
                ],
                CapaType::cases(),
            ),
            'criticalities' => ['low', 'medium', 'high', 'critical'],
        ];

        return new CapaBoardViewModel(
            kpis: $kpis,
            rows: $rows,
            attentionItems: $attentionItems,
            activityItems: $activityItems,
            filterOptions: $filterOptions,
            totalCount: $totalCount,
            page: $filters->page,
            perPage: $filters->perPage,
            totalPages: $totalPages,
        );
    }

    private function buildRow(CAPAAction $capa, \DateTimeImmutable $today, \DateTimeImmutable $dueSoonLimit): CapaBoardRow
    {
        $status = $capa->getStatus();
        $dueAt = $capa->getDueAt();
        $isTerminal = \in_array($status, CapaStatus::terminalStatuses(), true);
        $isOverdue = !$isTerminal && $dueAt !== null && $dueAt < $today;
        $isDueSoon = !$isTerminal && !$isOverdue && $dueAt !== null && $dueAt <= $dueSoonLimit;

        [$sourceLabel, $sourceRoute, $sourceRouteParams] = $this->resolveSource($capa);

        $crit = $capa->getCriticality();

        return new CapaBoardRow(
            capa: $capa,
            status: $status,
            workflowStep: self::WORKFLOW_STEP[$status->value] ?? 1,
            workflowTotal: self::WORKFLOW_TOTAL,
            isOverdue: $isOverdue,
            isDueSoon: $isDueSoon,
            sourceLabel: $sourceLabel,
            sourceRoute: $sourceRoute,
            sourceRouteParams: $sourceRouteParams,
            originName: (string) ($capa->getOrigin()?->getName() ?? '—'),
            typeLabel: $this->qseTwigExtension->capaTypeLabel($capa->getCapaType()->value),
            criticalityLabel: $crit !== null ? (self::CRITICALITY_LABELS[$crit] ?? ucfirst($crit)) : null,
        );
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?array<string, int|string>}
     */
    private function resolveSource(CAPAAction $capa): array
    {
        $eval = $capa->getSourceAuditEvaluation();
        if ($eval !== null) {
            $audit = $eval->getAudit();
            if ($audit !== null && $audit->getId() !== null) {
                $company = $audit->getCompanyName() ?? 'Audit';
                $date = $audit->getAuditedAt()?->format('d/m/Y') ?? '';
                $label = $date !== '' ? $company . ' — ' . $date : $company;

                return [$label, 'app_qse_audit_show', ['id' => $audit->getId()]];
            }
        }

        $tool = $capa->getSourceTool();
        if ($tool !== null && $tool !== '') {
            $label = self::TOOL_LABELS[$tool] ?? ucfirst(str_replace('_', ' ', $tool));

            return ['Outil : ' . $label, null, null];
        }

        return [null, null, null];
    }

    /**
     * @param list<CapaBoardRow> $rows
     *
     * @return list<CapaBoardAttentionItem>
     */
    private function buildAttentionItems(User $owner, array $rows, CapaBoardKpis $kpis): array
    {
        $items = [];
        $seenIds = [];

        foreach ($rows as $row) {
            $id = $row->capa->getId();
            if ($id === null) {
                continue;
            }
            if ($row->isOverdue) {
                $items[] = new CapaBoardAttentionItem(
                    type: CapaBoardAttentionItem::TYPE_OVERDUE,
                    title: $row->capa->getTitle(),
                    description: 'Échéance dépassée' . ($row->capa->getDueAt() ? ' — ' . $row->capa->getDueAt()->format('d/m/Y') : ''),
                    capaId: $id,
                    ctaLabel: 'Traiter',
                    ctaRoute: 'app_qse_capa_show',
                    ctaParams: ['id' => $id],
                );
                $seenIds[$id] = true;
            } elseif ($row->status === CapaStatus::EN_ATTENTE_DE_VERIFICATION) {
                $items[] = new CapaBoardAttentionItem(
                    type: CapaBoardAttentionItem::TYPE_VERIFICATION,
                    title: $row->capa->getTitle(),
                    description: 'Vérification d’efficacité à compléter',
                    capaId: $id,
                    ctaLabel: 'Vérifier',
                    ctaRoute: 'app_qse_capa_show',
                    ctaParams: ['id' => $id],
                );
                $seenIds[$id] = true;
            } elseif ($row->status === CapaStatus::A_VALIDER) {
                $items[] = new CapaBoardAttentionItem(
                    type: CapaBoardAttentionItem::TYPE_VALIDATION,
                    title: $row->capa->getTitle(),
                    description: 'En attente de validation',
                    capaId: $id,
                    ctaLabel: 'Valider',
                    ctaRoute: 'app_qse_capa_show',
                    ctaParams: ['id' => $id],
                );
                $seenIds[$id] = true;
            } elseif (\in_array($row->capa->getCriticality(), ['high', 'critical'], true)
                && !\in_array($row->status, CapaStatus::terminalStatuses(), true)) {
                $items[] = new CapaBoardAttentionItem(
                    type: CapaBoardAttentionItem::TYPE_CRITICAL,
                    title: $row->capa->getTitle(),
                    description: 'Criticité ' . strtolower($row->criticalityLabel ?? 'élevée'),
                    capaId: $id,
                    ctaLabel: 'Ouvrir',
                    ctaRoute: 'app_qse_capa_show',
                    ctaParams: ['id' => $id],
                );
                $seenIds[$id] = true;
            }
            if (\count($items) >= 6) {
                break;
            }
        }

        if (\count($items) < 6) {
            foreach ($this->capaRepository->findOverdueForOwner($owner, 3) as $capa) {
                $id = $capa->getId();
                if ($id === null || isset($seenIds[$id])) {
                    continue;
                }
                $items[] = new CapaBoardAttentionItem(
                    type: CapaBoardAttentionItem::TYPE_OVERDUE,
                    title: $capa->getTitle(),
                    description: 'CAPA en retard — reprise recommandée',
                    capaId: $id,
                    ctaLabel: 'Traiter',
                    ctaRoute: 'app_qse_capa_show',
                    ctaParams: ['id' => $id],
                );
                $seenIds[$id] = true;
                if (\count($items) >= 8) {
                    break;
                }
            }
        }

        if ($kpis->awaitingVerification > 0 && \count($items) < 8) {
            foreach ($this->capaRepository->findAwaitingVerificationForOwner($owner, 2) as $capa) {
                $id = $capa->getId();
                if ($id === null || isset($seenIds[$id])) {
                    continue;
                }
                $items[] = new CapaBoardAttentionItem(
                    type: CapaBoardAttentionItem::TYPE_VERIFICATION,
                    title: $capa->getTitle(),
                    description: 'Vérification d’efficacité en attente',
                    capaId: $id,
                    ctaLabel: 'Vérifier',
                    ctaRoute: 'app_qse_capa_show',
                    ctaParams: ['id' => $id],
                );
                if (\count($items) >= 8) {
                    break;
                }
            }
        }

        return $items;
    }
}
