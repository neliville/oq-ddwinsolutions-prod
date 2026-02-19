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
}
