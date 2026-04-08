<?php

namespace App\Repository;

/**
 * Agrège les données des 6 outils pour le dashboard (counts + derniers enregistrements).
 * Pas d'entité Doctrine : service d'agrégation uniquement.
 */
final class AnalyticsRepository
{
    public function __construct(
        private readonly IshikawaAnalysisRepository $ishikawaRepository,
        private readonly FiveWhyAnalysisRepository $fiveWhyRepository,
        private readonly QqoqccpAnalysisRepository $qqoqccpRepository,
        private readonly AmdecAnalysisRepository $amdecRepository,
        private readonly ParetoAnalysisRepository $paretoRepository,
        private readonly EightDAnalysisRepository $eightDRepository,
    ) {
    }

    /**
     * Retourne les totaux par outil, le total global et les N derniers enregistrements (tous outils confondus).
     *
     * @return array{
     *     totalRecords: int,
     *     ishikawaRecords: int,
     *     fiveWhyRecords: int,
     *     qqoqccpRecords: int,
     *     amdecRecords: int,
     *     paretoRecords: int,
     *     eightDRecords: int,
     *     recentRecords: list<array{record: object, type: string}>
     * }
     */
    public function getUserToolCounts(int $userId): array
    {
        $ishikawaCount = $this->ishikawaRepository->countByUser($userId);
        $fiveWhyCount = $this->fiveWhyRepository->countByUser($userId);
        $qqoqccpCount = $this->qqoqccpRepository->countByUser($userId);
        $amdecCount = $this->amdecRepository->countByUser($userId);
        $paretoCount = $this->paretoRepository->countByUser($userId);
        $eightDCount = $this->eightDRepository->countByUser($userId);

        $totalRecords = $ishikawaCount + $fiveWhyCount + $qqoqccpCount + $amdecCount + $paretoCount + $eightDCount;

        $ishikawaRecords = $this->ishikawaRepository->findByUser($userId);
        $fiveWhyRecords = $this->fiveWhyRepository->findByUser($userId);
        $qqoqccpRecords = $this->qqoqccpRepository->findByUser($userId);
        $amdecRecords = $this->amdecRepository->findByUser($userId);
        $paretoRecords = $this->paretoRepository->findByUser($userId);
        $eightDRecords = $this->eightDRepository->findByUser($userId);

        $recentRecords = array_merge(
            $ishikawaRecords,
            $fiveWhyRecords,
            $qqoqccpRecords,
            $amdecRecords,
            $paretoRecords,
            $eightDRecords
        );
        usort($recentRecords, fn ($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        $recentRecords = array_slice($recentRecords, 0, 5);

        $enrichedRecords = array_map(function ($record) {
            $type = match (true) {
                $record instanceof \App\Entity\IshikawaAnalysis => 'ishikawa',
                $record instanceof \App\Entity\FiveWhyAnalysis => 'fivewhy',
                $record instanceof \App\Entity\QqoqccpAnalysis => 'qqoqccp',
                $record instanceof \App\Entity\AmdecAnalysis => 'amdec',
                $record instanceof \App\Entity\ParetoAnalysis => 'pareto',
                $record instanceof \App\Entity\EightDAnalysis => 'eightd',
                default => 'unknown',
            };
            return ['record' => $record, 'type' => $type];
        }, $recentRecords);

        return [
            'totalRecords' => $totalRecords,
            'ishikawaRecords' => $ishikawaCount,
            'fiveWhyRecords' => $fiveWhyCount,
            'qqoqccpRecords' => $qqoqccpCount,
            'amdecRecords' => $amdecCount,
            'paretoRecords' => $paretoCount,
            'eightDRecords' => $eightDCount,
            'recentRecords' => $enrichedRecords,
        ];
    }

    /**
     * Totaux globaux (tous utilisateurs) par outil — pour classement « outils les plus utilisés ».
     *
     * @return list<array{key: string, label: string, count: int}>
     */
    /**
     * Créations par outil pour un utilisateur, triées par nombre décroissant.
     *
     * @return list<array{key: string, label: string, count: int}>
     */
    public function getPerUserToolBreakdown(int $userId): array
    {
        $c = $this->getUserToolCounts($userId);
        $rows = [
            ['key' => 'ishikawa', 'label' => 'Ishikawa', 'count' => $c['ishikawaRecords']],
            ['key' => 'fivewhy', 'label' => '5 Pourquoi', 'count' => $c['fiveWhyRecords']],
            ['key' => 'qqoqccp', 'label' => 'QQOQCCP', 'count' => $c['qqoqccpRecords']],
            ['key' => 'amdec', 'label' => 'AMDEC', 'count' => $c['amdecRecords']],
            ['key' => 'pareto', 'label' => 'Pareto', 'count' => $c['paretoRecords']],
            ['key' => 'eightd', 'label' => '8D', 'count' => $c['eightDRecords']],
        ];
        usort($rows, static fn (array $a, array $b) => $b['count'] <=> $a['count']);

        return $rows;
    }

    public function getGlobalCreationCountsByTool(): array
    {
        $rows = [
            ['key' => 'ishikawa', 'label' => 'Ishikawa', 'count' => $this->countEntities($this->ishikawaRepository)],
            ['key' => 'fivewhy', 'label' => '5 Pourquoi', 'count' => $this->countEntities($this->fiveWhyRepository)],
            ['key' => 'qqoqccp', 'label' => 'QQOQCCP', 'count' => $this->countEntities($this->qqoqccpRepository)],
            ['key' => 'amdec', 'label' => 'AMDEC', 'count' => $this->countEntities($this->amdecRepository)],
            ['key' => 'pareto', 'label' => 'Pareto', 'count' => $this->countEntities($this->paretoRepository)],
            ['key' => 'eightd', 'label' => '8D', 'count' => $this->countEntities($this->eightDRepository)],
        ];
        usort($rows, static fn (array $a, array $b) => $b['count'] <=> $a['count']);

        return $rows;
    }

    /**
     * Dernières analyses fusionnées (tous outils) pour un utilisateur.
     *
     * @return list<array{title: string, type: string, typeLabel: string, createdAt: \DateTimeImmutable}>
     */
    public function getRecentAnalysesForUser(int $userId, int $limit = 30): array
    {
        $ishikawaRecords = $this->ishikawaRepository->findByUser($userId);
        $fiveWhyRecords = $this->fiveWhyRepository->findByUser($userId);
        $qqoqccpRecords = $this->qqoqccpRepository->findByUser($userId);
        $amdecRecords = $this->amdecRepository->findByUser($userId);
        $paretoRecords = $this->paretoRepository->findByUser($userId);
        $eightDRecords = $this->eightDRepository->findByUser($userId);

        $merged = array_merge(
            $ishikawaRecords,
            $fiveWhyRecords,
            $qqoqccpRecords,
            $amdecRecords,
            $paretoRecords,
            $eightDRecords
        );
        usort($merged, fn ($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        $merged = array_slice($merged, 0, $limit);

        $out = [];
        foreach ($merged as $record) {
            $type = match (true) {
                $record instanceof \App\Entity\IshikawaAnalysis => 'ishikawa',
                $record instanceof \App\Entity\FiveWhyAnalysis => 'fivewhy',
                $record instanceof \App\Entity\QqoqccpAnalysis => 'qqoqccp',
                $record instanceof \App\Entity\AmdecAnalysis => 'amdec',
                $record instanceof \App\Entity\ParetoAnalysis => 'pareto',
                $record instanceof \App\Entity\EightDAnalysis => 'eightd',
                default => 'unknown',
            };
            $createdAt = $record->getCreatedAt();
            if (!$createdAt instanceof \DateTimeImmutable) {
                continue;
            }
            $out[] = [
                'title' => (string) ($record->getTitle() ?? 'Sans titre'),
                'type' => $type,
                'typeLabel' => self::toolTypeLabel($type),
                'createdAt' => $createdAt,
            ];
        }

        return $out;
    }

    private static function toolTypeLabel(string $type): string
    {
        return match ($type) {
            'ishikawa' => 'Ishikawa',
            'fivewhy' => '5 Pourquoi',
            'qqoqccp' => 'QQOQCCP',
            'amdec' => 'AMDEC',
            'pareto' => 'Pareto',
            'eightd' => '8D',
            default => $type,
        };
    }

    private function countEntities(
        IshikawaAnalysisRepository|FiveWhyAnalysisRepository|QqoqccpAnalysisRepository|AmdecAnalysisRepository|ParetoAnalysisRepository|EightDAnalysisRepository $repository,
    ): int {
        return (int) $repository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
