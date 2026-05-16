<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\Qse\Audit;
use App\Entity\Qse\CAPAAction;
use App\Entity\Qse\RiskMatrixEntry;
use App\Entity\User;
use App\Repository\Qse\RiskMatrixEntryRepository;
use App\Qse\Dto\ActivityItem;
use App\Repository\AnalyticsRepository;
use App\Repository\Qse\CockpitMetricsRepository;

/**
 * Agrège l’activité récente QSE (audits, CAPA, outils d’analyse) sans journal dédié.
 */
final class QseActivityAggregator
{
    public function __construct(
        private readonly CockpitMetricsRepository $cockpitMetricsRepository,
        private readonly AnalyticsRepository $analyticsRepository,
        private readonly RiskMatrixEntryRepository $riskRepository,
    ) {
    }

    /**
     * @return list<ActivityItem>
     */
    public function recent(User $owner, int $limit = 8): array
    {
        $items = [];

        foreach ($this->cockpitMetricsRepository->findRecentAudits($owner, $limit) as $audit) {
            $items[] = $this->fromAudit($audit);
        }

        foreach ($this->cockpitMetricsRepository->findRecentCapasForKanban($owner, $limit) as $capa) {
            $items[] = $this->fromCapa($capa);
        }

        $toolCounts = $this->analyticsRepository->getUserToolCounts($owner->getId() ?? 0);
        foreach ($toolCounts['recentRecords'] ?? [] as $row) {
            $item = $this->fromToolRecord($row);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        usort($items, static fn (ActivityItem $a, ActivityItem $b): int => $b->occurredAt <=> $a->occurredAt);

        return \array_slice($items, 0, $limit);
    }

    /**
     * Activité récente centrée sur les risques (page Risk Control Center).
     *
     * @return list<ActivityItem>
     */
    public function recentForRisks(User $owner, int $limit = 8): array
    {
        $items = [];
        foreach ($this->riskRepository->findRecentByOwner($owner, $limit * 2) as $risk) {
            $items[] = $this->fromRisk($risk);
        }

        usort($items, static fn (ActivityItem $a, ActivityItem $b): int => $b->occurredAt <=> $a->occurredAt);

        return \array_slice($items, 0, $limit);
    }

    private function fromRisk(RiskMatrixEntry $risk): ActivityItem
    {
        $createdAt = $risk->getCreatedAt();
        $updatedAt = $risk->getUpdatedAt();
        $at = $updatedAt ?? $createdAt;
        $isNew = $updatedAt === null
            || ($createdAt !== null && $updatedAt->getTimestamp() === $createdAt->getTimestamp());

        $label = $isNew ? 'Risque ajouté' : 'Fiche risque mise à jour';
        $description = $risk->getIdentifiedRisk();

        return new ActivityItem(
            id: 'risk-' . ($risk->getId() ?? 0),
            type: 'risk',
            label: $label,
            description: $description,
            routeName: $risk->getId() ? 'app_qse_risk_show' : null,
            routeParams: $risk->getId() ? ['id' => $risk->getId()] : null,
            occurredAt: $at,
            icon: 'lucide:shield-alert',
            tone: 'amber',
        );
    }

    private function fromAudit(Audit $audit): ActivityItem
    {
        $label = $audit->getCompanyName() ?: 'Audit';
        $standard = $audit->getAuditStandard();
        if ($standard !== null) {
            $label .= ' · ' . $standard->getName();
        }
        $at = $audit->getUpdatedAt() ?? $audit->getCreatedAt() ?? new \DateTimeImmutable();

        return new ActivityItem(
            id: 'audit-' . ($audit->getId() ?? 0),
            type: 'audit',
            label: 'Audit mis à jour',
            description: $label,
            routeName: $audit->getId() ? 'app_qse_audit_show' : null,
            routeParams: $audit->getId() ? ['id' => $audit->getId()] : null,
            occurredAt: $at,
            icon: 'lucide:clipboard-list',
            tone: 'sky',
        );
    }

    private function fromCapa(CAPAAction $capa): ActivityItem
    {
        $at = $capa->getUpdatedAt() ?? $capa->getCreatedAt() ?? new \DateTimeImmutable();

        return new ActivityItem(
            id: 'capa-' . ($capa->getId() ?? 0),
            type: 'capa',
            label: 'CAPA mise à jour',
            description: $capa->getTitle(),
            routeName: $capa->getId() ? 'app_qse_capa_show' : null,
            routeParams: $capa->getId() ? ['id' => $capa->getId()] : null,
            occurredAt: $at,
            icon: 'lucide:list-todo',
            tone: 'emerald',
        );
    }

    /**
     * @param array{record: object, type: string} $row
     */
    private function fromToolRecord(array $row): ?ActivityItem
    {
        $record = $row['record'] ?? null;
        $type = (string) ($row['type'] ?? '');
        if (!\is_object($record)) {
            return null;
        }

        $title = method_exists($record, 'getTitle') ? (string) ($record->getTitle() ?? '') : '';
        if ($title === '') {
            $title = 'Analyse';
        }

        $createdAt = method_exists($record, 'getCreatedAt') ? $record->getCreatedAt() : null;
        if (!$createdAt instanceof \DateTimeImmutable) {
            return null;
        }

        $id = method_exists($record, 'getId') ? $record->getId() : 0;

        return match ($type) {
            'ishikawa' => new ActivityItem(
                'ishikawa-' . $id,
                'ishikawa',
                'Analyse Ishikawa créée',
                $title,
                null,
                null,
                $createdAt,
                'lucide:git-branch',
                'violet',
                'app_ishikawa_index',
                (int) $id,
            ),
            'fivewhy' => new ActivityItem(
                'fivewhy-' . $id,
                'fivewhy',
                'Analyse 5 Pourquoi créée',
                $title,
                null,
                null,
                $createdAt,
                'lucide:circle-help',
                'violet',
                'app_fivewhy_index',
                (int) $id,
            ),
            'qqoqccp' => new ActivityItem(
                'qqoqccp-' . $id,
                'qqoqccp',
                'Analyse QQOQCCP créée',
                $title,
                null,
                null,
                $createdAt,
                'lucide:messages-square',
                'sky',
                'app_qqoqccp_index',
                (int) $id,
            ),
            'amdec' => new ActivityItem(
                'amdec-' . $id,
                'amdec',
                'Analyse AMDEC créée',
                $title,
                null,
                null,
                $createdAt,
                'lucide:table-2',
                'sky',
                'app_amdec_index',
                (int) $id,
            ),
            'pareto' => new ActivityItem(
                'pareto-' . $id,
                'pareto',
                'Analyse Pareto créée',
                $title,
                null,
                null,
                $createdAt,
                'lucide:chart-no-axes-column',
                'amber',
                'app_pareto_index',
                (int) $id,
            ),
            'eightd' => new ActivityItem(
                'eightd-' . $id,
                'eightd',
                'Dossier 8D créé',
                $title,
                null,
                null,
                $createdAt,
                'lucide:package',
                'orange',
                'app_eightd_index',
                (int) $id,
            ),
            default => null,
        };
    }
}
