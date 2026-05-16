<?php

declare(strict_types=1);

namespace App\Qse\Service;

use App\Entity\Qse\AuditEvaluation;
use App\Entity\Qse\CAPAAction;
use App\Repository\Qse\CAPAActionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Crée automatiquement un brouillon CAPA pour les NC mineures/majeures à l’enregistrement d’une évaluation.
 */
final class AuditEvaluationAutoCapaService
{
    /** @var array<string, CAPAAction> Clé stable audit+exigence pour dédoublonnage intra-requête */
    private array $ensuredInRequest = [];

    private int $newlyCreatedCount = 0;

    public function __construct(
        private readonly AuditEvaluationCapaFactory $capaFactory,
        private readonly CAPAActionRepository $capaRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return CAPAAction|null CAPA créée, existante, ou null si verdict hors périmètre auto
     */
    public function ensureDraftForEvaluation(AuditEvaluation $evaluation): ?CAPAAction
    {
        $owner = $evaluation->getOwner();
        if ($owner === null) {
            return null;
        }

        $verdict = AuditEvaluationVerdictHelper::effectiveVerdict($evaluation);
        if ($verdict === null || !$verdict->requiresAutoCapa()) {
            return null;
        }

        $cacheKey = $this->cacheKey($evaluation);
        if (isset($this->ensuredInRequest[$cacheKey])) {
            return $this->ensuredInRequest[$cacheKey];
        }

        $evalId = $evaluation->getId();
        if ($evalId !== null) {
            $existing = $this->capaRepository->findOpenBySourceAuditEvaluation($evalId);
            if ($existing !== null) {
                $this->ensuredInRequest[$cacheKey] = $existing;

                return $existing;
            }
        }

        $capa = $this->capaFactory->createDraftFromEvaluation($evaluation, $owner);
        $meta = $capa->getMetadata() ?? [];
        $meta['auto_created_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
        $capa->setMetadata($meta);
        $this->entityManager->persist($capa);
        $this->ensuredInRequest[$cacheKey] = $capa;
        ++$this->newlyCreatedCount;

        return $capa;
    }

    public function pullNewlyCreatedCount(): int
    {
        $count = $this->newlyCreatedCount;
        $this->newlyCreatedCount = 0;

        return $count;
    }

    private function cacheKey(AuditEvaluation $evaluation): string
    {
        $auditId = $evaluation->getAudit()?->getId() ?? 'new';
        $reqId = $evaluation->getRequirement()?->getId() ?? 0;
        $evalId = $evaluation->getId() ?? 0;

        return sprintf('%s-%d-%d', (string) $auditId, $reqId, $evalId);
    }
}
