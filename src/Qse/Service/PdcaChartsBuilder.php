<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\User;
use App\Qse\Enum\AuditVerdict;
use App\Qse\Enum\CapaStatus;
use App\Repository\Qse\CAPAActionRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Payloads ApexCharts pour le cockpit PDCA (kind « pdca-cockpit »).
 */
final class PdcaChartsBuilder
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly CAPAActionRepository $capaRepository,
        private readonly RiskHeatmapBuilder $riskHeatmapBuilder,
    ) {
    }

    /**
     * @param array<string, mixed> $cockpit
     * @param array{audits_by_standard?: list<array{code: string, label: string, cnt: int}>} $check
     * @param array{plan: int|null, do: int|null, check: int|null, act: int|null} $phaseScores
     *
     * @return array{charts: list<array<string, mixed>>}
     */
    public function build(User $owner, array $cockpit, array $check, array $phaseScores): array
    {
        $charts = [];

        $conformity = $this->buildConformityDonut($owner);
        if ($conformity !== null) {
            $charts[] = $conformity;
        }

        $capaHealth = $this->buildCapaHealthDonut($owner, $cockpit);
        if ($capaHealth !== null) {
            $charts[] = $capaHealth;
        }

        $auditsByNorm = $this->buildAuditsByStandardBar($check['audits_by_standard'] ?? []);
        if ($auditsByNorm !== null) {
            $charts[] = $auditsByNorm;
        }

        $heatmap = $this->riskHeatmapBuilder->buildChartPayload($owner);
        if ($heatmap !== null) {
            $charts[] = $heatmap;
        }

        $monthly = $this->buildMonthlyEvolution($owner);
        if ($monthly !== null) {
            $charts[] = $monthly;
        }

        $radar = $this->buildPdcaRadar($phaseScores);
        if ($radar !== null) {
            $charts[] = $radar;
        }

        return ['charts' => $charts];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildConformityDonut(User $owner): ?array
    {
        $em = $this->registry->getManager();
        $rows = $em->createQueryBuilder()
            ->select('e.verdict AS v', 'COUNT(e.id) AS cnt')
            ->from(\App\Entity\Qse\AuditEvaluation::class, 'e')
            ->where('e.owner = :owner')
            ->andWhere('e.verdict IS NOT NULL')
            ->andWhere('e.verdict != :ne')
            ->setParameter('owner', $owner)
            ->setParameter('ne', AuditVerdict::NOT_EVALUATED)
            ->groupBy('e.verdict')
            ->getQuery()
            ->getScalarResult();

        if ($rows === []) {
            return null;
        }

        $order = [
            AuditVerdict::CONFORM,
            AuditVerdict::OBSERVATION,
            AuditVerdict::MINOR_NC,
            AuditVerdict::MAJOR_NC,
            AuditVerdict::NOT_APPLICABLE,
        ];
        $counts = [];
        foreach ($rows as $row) {
            $v = $row['v'] instanceof AuditVerdict ? $row['v'] : AuditVerdict::tryFrom((string) ($row['v'] ?? ''));
            if ($v !== null) {
                $counts[$v->value] = (int) ($row['cnt'] ?? 0);
            }
        }

        $labels = [];
        $series = [];
        $colors = ['#22c55e', '#eab308', '#f97316', '#dc2626', '#94a3b8'];
        $colorIdx = 0;
        foreach ($order as $verdict) {
            $n = $counts[$verdict->value] ?? 0;
            if ($n > 0) {
                $labels[] = $verdict->label();
                $series[] = $n;
                ++$colorIdx;
            }
        }

        if ($labels === []) {
            return null;
        }

        return [
            'ref' => 'conformity',
            'type' => 'donut',
            'labels' => $labels,
            'series' => $series,
            'colors' => \array_slice($colors, 0, \count($labels)),
            'title' => 'Conformité des évaluations',
        ];
    }

    /**
     * @param array<string, mixed> $cockpit
     *
     * @return array<string, mixed>|null
     */
    private function buildCapaHealthDonut(User $owner, array $cockpit): ?array
    {
        $open = (int) ($cockpit['openCapaCount'] ?? 0);
        $overdue = (int) ($cockpit['overdueOpenCapas'] ?? 0);
        $awaiting = (int) ($cockpit['capasAwaitingVerification'] ?? 0);

        $closed = (int) $this->capaRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.owner = :owner')
            ->andWhere('c.status = :st')
            ->setParameter('owner', $owner)
            ->setParameter('st', CapaStatus::CLOTUREE)
            ->getQuery()
            ->getSingleScalarResult();

        $openOnTrack = max(0, $open - $overdue - $awaiting);

        $labels = [];
        $series = [];
        $colors = [];
        $segments = [
            ['Ouvertes (dans les délais)', $openOnTrack, '#06b6d4'],
            ['En retard', $overdue, '#dc2626'],
            ['En vérification', $awaiting, '#f59e0b'],
            ['Clôturées', $closed, '#22c55e'],
        ];
        foreach ($segments as [$label, $n, $color]) {
            if ($n > 0) {
                $labels[] = $label;
                $series[] = $n;
                $colors[] = $color;
            }
        }

        if ($labels === []) {
            return null;
        }

        return [
            'ref' => 'capa_health',
            'type' => 'donut',
            'labels' => $labels,
            'series' => $series,
            'colors' => $colors,
            'title' => 'Santé CAPA',
        ];
    }

    /**
     * @param list<array{code: string, label: string, cnt: int}> $rows
     *
     * @return array<string, mixed>|null
     */
    private function buildAuditsByStandardBar(array $rows): ?array
    {
        if ($rows === []) {
            return null;
        }

        $labels = [];
        $series = [];
        foreach ($rows as $row) {
            $labels[] = (string) ($row['label'] ?? $row['code'] ?? '');
            $series[] = (int) ($row['cnt'] ?? 0);
        }

        return [
            'ref' => 'audits_by_standard',
            'type' => 'hbar',
            'labels' => $labels,
            'series' => $series,
            'name' => 'Audits',
            'title' => 'Audits par norme',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildMonthlyEvolution(User $owner): ?array
    {
        $labels = [];
        $auditValues = [];
        $capaValues = [];
        $now = new \DateTimeImmutable('first day of this month');

        for ($i = 5; $i >= 0; --$i) {
            $monthStart = $now->modify(sprintf('-%d months', $i));
            $monthEnd = $monthStart->modify('+1 month');
            $labels[] = $monthStart->format('M Y');

            $em = $this->registry->getManager();
            $auditValues[] = (int) $em->createQueryBuilder()
                ->select('COUNT(a.id)')
                ->from(\App\Entity\Qse\Audit::class, 'a')
                ->where('a.owner = :owner')
                ->andWhere('a.createdAt >= :from')
                ->andWhere('a.createdAt < :to')
                ->setParameter('owner', $owner)
                ->setParameter('from', $monthStart)
                ->setParameter('to', $monthEnd)
                ->getQuery()
                ->getSingleScalarResult();

            $capaValues[] = (int) $em->createQueryBuilder()
                ->select('COUNT(c.id)')
                ->from(\App\Entity\Qse\CAPAAction::class, 'c')
                ->where('c.owner = :owner')
                ->andWhere('c.createdAt >= :from')
                ->andWhere('c.createdAt < :to')
                ->setParameter('owner', $owner)
                ->setParameter('from', $monthStart)
                ->setParameter('to', $monthEnd)
                ->getQuery()
                ->getSingleScalarResult();
        }

        if (array_sum($auditValues) === 0 && array_sum($capaValues) === 0) {
            return null;
        }

        return [
            'ref' => 'monthly_evolution',
            'type' => 'area',
            'labels' => $labels,
            'series' => [
                ['name' => 'Audits', 'data' => $auditValues],
                ['name' => 'CAPA', 'data' => $capaValues],
            ],
            'title' => 'Évolution mensuelle',
        ];
    }

    /**
     * @param array{plan: int|null, do: int|null, check: int|null, act: int|null} $phaseScores
     *
     * @return array<string, mixed>|null
     */
    private function buildPdcaRadar(array $phaseScores): ?array
    {
        $labels = ['Plan', 'Do', 'Check', 'Act'];
        $values = [
            $phaseScores['plan'] ?? 0,
            $phaseScores['do'] ?? 0,
            $phaseScores['check'] ?? 0,
            $phaseScores['act'] ?? 0,
        ];

        if (array_sum($values) === 0) {
            return null;
        }

        return [
            'ref' => 'pdca_radar',
            'type' => 'radar',
            'labels' => $labels,
            'values' => $values,
            'title' => 'Répartition PDCA',
        ];
    }
}
