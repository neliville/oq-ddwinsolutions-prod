<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\User;
use App\Repository\Qse\CockpitMetricsRepository;
use App\Repository\Qse\RiskMatrixEntryRepository;

/**
 * Actions prioritaires pour le Risk Control Center.
 */
final class RiskPriorityActionsBuilder
{
    public function __construct(
        private readonly CockpitMetricsRepository $cockpitMetricsRepository,
        private readonly RiskMatrixEntryRepository $riskRepository,
    ) {
    }

    /**
     * @return list<array{
     *   id: string,
     *   label: string,
     *   detail: string,
     *   count: int,
     *   tone: string,
     *   route: string,
     *   routeParams?: array<string, int|string>,
     *   cta_primary: string,
     *   cta_secondary: string|null
     * }>
     */
    public function build(User $owner): array
    {
        $items = [];
        $cockpit = $this->cockpitMetricsRepository->getMetrics($owner);

        $criticalNoCapa = (int) ($cockpit['criticalRisksWithoutCapa'] ?? 0);
        if ($criticalNoCapa > 0) {
            $items[] = [
                'id' => 'risks-no-capa',
                'label' => 'Risques critiques sans CAPA',
                'detail' => sprintf('%d risque(s) majeur(s) sans action corrective liée.', $criticalNoCapa),
                'count' => $criticalNoCapa,
                'tone' => 'amber',
                'route' => 'app_qse_risk_index',
                'cta_primary' => 'Traiter maintenant',
                'cta_secondary' => 'Voir la liste',
            ];
        }

        $overdue = $this->riskRepository->countReviewOverdueByOwner($owner);
        if ($overdue > 0) {
            $items[] = [
                'id' => 'risks-review-overdue',
                'label' => 'Révisions en retard',
                'detail' => sprintf('%d risque(s) dont la date de révision est dépassée.', $overdue),
                'count' => $overdue,
                'tone' => 'destructive',
                'route' => 'app_qse_risk_index',
                'cta_primary' => 'Traiter maintenant',
                'cta_secondary' => null,
            ];
        }

        $reviewSoon = $this->riskRepository->countReviewWithinDaysByOwner($owner, 7);
        if ($reviewSoon > 0) {
            $items[] = [
                'id' => 'risks-review-soon',
                'label' => 'Révisions sous 7 jours',
                'detail' => sprintf('%d risque(s) à revoir cette semaine.', $reviewSoon),
                'count' => $reviewSoon,
                'tone' => 'amber',
                'route' => 'app_qse_risk_index',
                'cta_primary' => 'Planifier',
                'cta_secondary' => null,
            ];
        }

        $criticalCount = $this->riskRepository->countCriticalByOwner($owner);
        if ($criticalCount > 0 && $criticalNoCapa === 0) {
            $items[] = [
                'id' => 'risks-critical',
                'label' => 'Risques critiques actifs',
                'detail' => sprintf('%d risque(s) à surveiller en priorité.', $criticalCount),
                'count' => $criticalCount,
                'tone' => 'destructive',
                'route' => 'app_qse_risk_index',
                'cta_primary' => 'Traiter maintenant',
                'cta_secondary' => null,
            ];
        }

        return $items;
    }
}
