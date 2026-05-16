<?php

declare(strict_types=1);

namespace App\Qse\Risk\ViewModel;

use App\Entity\Qse\RiskMatrixEntry;
use App\Entity\User;
use App\Qse\Enum\RiskEntryStatus;
use App\Qse\Service\RiskCapaPolicy;
use App\Qse\Service\RiskCriticalityPresenter;
use App\Qse\Service\RiskHeatmapBuilder;
use App\Qse\Service\RiskPriorityActionsBuilder;
use App\Qse\Service\QseActivityAggregator;
use App\Repository\Qse\RiskMatrixEntryRepository;
use App\Twig\QseTwigExtension;

final class RiskBoardViewModelFactory
{
    public function __construct(
        private readonly RiskMatrixEntryRepository $riskRepository,
        private readonly RiskCapaPolicy $riskCapaPolicy,
        private readonly RiskCriticalityPresenter $criticalityPresenter,
        private readonly RiskHeatmapBuilder $heatmapBuilder,
        private readonly RiskPriorityActionsBuilder $priorityActionsBuilder,
        private readonly QseActivityAggregator $activityAggregator,
        private readonly QseTwigExtension $qseTwigExtension,
    ) {
    }

    public function buildCockpitShell(User $owner): RiskCockpitShell
    {
        $total = $this->riskRepository->countTotalByOwner($owner);

        return new RiskCockpitShell(
            kpis: $this->buildKpis($owner),
            heatmap: $this->heatmapBuilder->buildPreview($owner),
            priorityItems: $this->priorityActionsBuilder->build($owner),
            activity: $this->activityAggregator->recentForRisks($owner, 8),
            totalRisks: $total,
        );
    }

    public function build(User $owner, RiskBoardFilters $filters): RiskBoardViewModel
    {
        $pageResult = $this->riskRepository->findBoardPage($owner, $filters);
        $totalCount = $pageResult['total'];
        $totalPages = max(1, (int) ceil($totalCount / $filters->perPage));
        $today = new \DateTimeImmutable('today');

        $rows = [];
        foreach ($pageResult['items'] as $entry) {
            $id = $entry->getId();
            if ($id === null) {
                continue;
            }
            $crit = $this->criticalityPresenter->present($entry);
            $reviewAt = $entry->getReviewAt();
            $rows[] = new RiskBoardRow(
                entry: $entry,
                id: $id,
                label: $entry->getIdentifiedRisk(),
                criticalityScore: $entry->getCriticalityScore(),
                criticalityLevel: $crit['level'],
                criticalityLabel: $crit['label'],
                status: $entry->getStatus(),
                statusLabel: $this->qseTwigExtension->statusLabel($entry->getStatus()->value, 'risk'),
                responsible: $entry->getResponsible(),
                reviewAt: $reviewAt,
                capaCount: $entry->getLinkedCapas()->count(),
                requiresCapa: $this->riskCapaPolicy->requiresLinkedCapa($entry),
                reviewOverdue: $reviewAt !== null
                    && $entry->getStatus() !== RiskEntryStatus::CLOTURE
                    && $reviewAt < $today,
            );
        }

        return new RiskBoardViewModel(
            rows: $rows,
            filterOptions: $this->buildFilterOptions(),
            totalCount: $totalCount,
            page: $filters->page,
            perPage: $filters->perPage,
            totalPages: $totalPages,
        );
    }

    private function buildKpis(User $owner): RiskBoardKpis
    {
        return new RiskBoardKpis(
            openRisks: $this->riskRepository->countOpenByOwner($owner),
            criticalRisks: $this->riskRepository->countCriticalByOwner($owner),
            inTreatment: $this->riskRepository->countInTreatmentByOwner($owner),
            closedThisMonth: $this->riskRepository->countClosedThisMonthByOwner($owner),
            totalRisks: $this->riskRepository->countTotalByOwner($owner),
        );
    }

    /**
     * @return array{
     *   statuses: list<array{value: string, label: string}>,
     *   criticalities: list<array{value: string, label: string}>
     * }
     */
    private function buildFilterOptions(): array
    {
        $statuses = [];
        foreach (RiskEntryStatus::cases() as $status) {
            $statuses[] = [
                'value' => $status->value,
                'label' => $this->qseTwigExtension->statusLabel($status->value, 'risk'),
            ];
        }

        return [
            'statuses' => $statuses,
            'criticalities' => [
                ['value' => '', 'label' => 'Toutes criticités'],
                ['value' => RiskBoardFilters::CRITICALITY_LOW, 'label' => 'Faible'],
                ['value' => RiskBoardFilters::CRITICALITY_MEDIUM, 'label' => 'Modéré'],
                ['value' => RiskBoardFilters::CRITICALITY_CRITICAL, 'label' => 'Critique'],
                ['value' => RiskBoardFilters::CRITICALITY_CONTROLLED, 'label' => 'Maîtrisé'],
            ],
        ];
    }
}
