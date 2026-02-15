<?php

namespace App\Lead\Service;

use App\Entity\User;
use App\Repository\AmdecAnalysisRepository;
use App\Repository\EightDAnalysisRepository;
use App\Repository\FiveWhyAnalysisRepository;
use App\Repository\IshikawaAnalysisRepository;
use App\Repository\ParetoAnalysisRepository;
use App\Repository\QqoqccpAnalysisRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gestion des quotas freemium (exports mensuels, nombre de sauvegardes).
 * Premium = illimité.
 */
final class QuotaService
{
    private const FREE_EXPORT_QUOTA = 10;
    private const FREE_SAVE_QUOTA = 5;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AmdecAnalysisRepository $amdecAnalysisRepository,
        private readonly EightDAnalysisRepository $eightDAnalysisRepository,
        private readonly FiveWhyAnalysisRepository $fiveWhyAnalysisRepository,
        private readonly IshikawaAnalysisRepository $ishikawaAnalysisRepository,
        private readonly ParetoAnalysisRepository $paretoAnalysisRepository,
        private readonly QqoqccpAnalysisRepository $qqoqccpAnalysisRepository,
    ) {
    }

    public function hasExportQuotaRemaining(User $user): bool
    {
        if ($this->isPremium($user)) {
            return true;
        }
        $this->resetMonthlyCountersIfNeeded($user);

        return $user->getExportCountThisMonth() < self::FREE_EXPORT_QUOTA;
    }

    public function incrementExportCount(User $user): void
    {
        $this->resetMonthlyCountersIfNeeded($user);
        $user->setExportCountThisMonth($user->getExportCountThisMonth() + 1);
        $this->entityManager->flush();
    }

    public function canSaveNew(User $user): bool
    {
        if ($this->isPremium($user)) {
            return true;
        }
        $totalSaved = $this->countUserAnalyses($user);

        return $totalSaved < self::FREE_SAVE_QUOTA;
    }

    public function getExportQuotaRemaining(User $user): ?int
    {
        if ($this->isPremium($user)) {
            return null;
        }
        $this->resetMonthlyCountersIfNeeded($user);
        $remaining = self::FREE_EXPORT_QUOTA - $user->getExportCountThisMonth();

        return max(0, $remaining);
    }

    public function getSaveQuotaLimit(User $user): ?int
    {
        if ($this->isPremium($user)) {
            return null;
        }

        return self::FREE_SAVE_QUOTA;
    }

    private function isPremium(User $user): bool
    {
        if ('premium' === $user->getPlan()) {
            return true;
        }
        $until = $user->getPremiumUntil();
        if ($until && $until > new \DateTimeImmutable()) {
            return true;
        }

        return false;
    }

    private function resetMonthlyCountersIfNeeded(User $user): void
    {
        $now = new \DateTimeImmutable();
        $startOfCurrentMonth = new \DateTimeImmutable($now->format('Y-m-01'));
        $lastReset = $user->getLastExportReset();
        if (null === $lastReset || $lastReset < $startOfCurrentMonth) {
            $user->setExportCountThisMonth(0);
            $user->setLastExportReset($startOfCurrentMonth);
            $this->entityManager->flush();
        }
    }

    private function countUserAnalyses(User $user): int
    {
        $count = 0;
        $count += $this->amdecAnalysisRepository->count(['user' => $user]);
        $count += $this->eightDAnalysisRepository->count(['user' => $user]);
        $count += $this->fiveWhyAnalysisRepository->count(['user' => $user]);
        $count += $this->ishikawaAnalysisRepository->count(['user' => $user]);
        $count += $this->paretoAnalysisRepository->count(['user' => $user]);
        $count += $this->qqoqccpAnalysisRepository->count(['user' => $user]);

        return $count;
    }
}
