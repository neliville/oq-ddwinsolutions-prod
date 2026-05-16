<?php

declare(strict_types=1);

namespace App\Repository\Qse;

use App\Entity\User;
use App\Qse\Enum\AuditExecutionStatus;
use App\Qse\Enum\AuditVerdict;
use App\Qse\Enum\AuditPlanStatus;
use App\Qse\Enum\CapaStatus;
use App\Qse\Enum\RiskEntryStatus;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Agrégations légères pour le tableau de bord cockpit (requêtes séparées, limites fixes).
 */
final class CockpitMetricsRepository
{
    public function __construct(
        private readonly ManagerRegistry $registry,
    ) {
    }

    /**
     * @return array{
     *   overdueAuditPlans: int,
     *   openCapaCount: int,
     *   dueCapaNext7Days: int,
     *   criticalRisksWithoutCapa: int,
     *   openNonConformEvaluations: int,
     *   overdueOpenCapas: int,
     *   capasAwaitingVerification: int,
     *   staleAuditDrafts: int,
     *   openCapasWithoutResponsible: int,
     *   capasInVerificationMissingComment: int,
     *   avgAuditCompliancePercent: float|null
     * }
     */
    public function getMetrics(User $owner): array
    {
        $em = $this->registry->getManager();
        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $todayDt = new \DateTimeImmutable($today);
        $cutoffStale = $todayDt->modify('-14 days');

        $overduePlans = (int) $em->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(\App\Entity\Qse\AuditPlan::class, 'p')
            ->where('p.owner = :owner')
            ->andWhere('p.plannedAt IS NOT NULL')
            ->andWhere('p.plannedAt < :today')
            ->andWhere('p.status IN (:st)')
            ->setParameter('owner', $owner)
            ->setParameter('today', $today)
            ->setParameter('st', [AuditPlanStatus::PLANIFIE, AuditPlanStatus::EN_COURS])
            ->getQuery()
            ->getSingleScalarResult();

        $openCapa = (int) $em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(\App\Entity\Qse\CAPAAction::class, 'c')
            ->where('c.owner = :owner')
            ->andWhere('c.status NOT IN (:closed)')
            ->setParameter('owner', $owner)
            ->setParameter('closed', CapaStatus::terminalStatuses())
            ->getQuery()
            ->getSingleScalarResult();

        $in7 = (new \DateTimeImmutable('+7 days'))->format('Y-m-d');
        $dueSoon = (int) $em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(\App\Entity\Qse\CAPAAction::class, 'c')
            ->where('c.owner = :owner')
            ->andWhere('c.dueAt IS NOT NULL')
            ->andWhere('c.dueAt BETWEEN :today AND :in7')
            ->andWhere('c.status NOT IN (:closed)')
            ->setParameter('owner', $owner)
            ->setParameter('today', $today)
            ->setParameter('in7', $in7)
            ->setParameter('closed', CapaStatus::terminalStatuses())
            ->getQuery()
            ->getSingleScalarResult();

        $allCritical = $em->getRepository(\App\Entity\Qse\RiskMatrixEntry::class)
            ->createQueryBuilder('r')
            ->where('r.owner = :owner')
            ->andWhere('r.criticalityScore >= :thr')
            ->setParameter('owner', $owner)
            ->setParameter('thr', 12)
            ->getQuery()
            ->getResult();
        $criticalNoCapa = 0;
        foreach ($allCritical as $r) {
            if ($r->getLinkedCapas()->isEmpty()) {
                ++$criticalNoCapa;
            }
        }

        $openNcEval = (int) $em->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(\App\Entity\Qse\AuditEvaluation::class, 'e')
            ->where('e.owner = :owner')
            ->andWhere('e.verdict IN (:verdicts)')
            ->setParameter('owner', $owner)
            ->setParameter('verdicts', [AuditVerdict::MINOR_NC, AuditVerdict::MAJOR_NC])
            ->getQuery()
            ->getSingleScalarResult();

        $overdueCapas = (int) $em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(\App\Entity\Qse\CAPAAction::class, 'c')
            ->where('c.owner = :owner')
            ->andWhere('c.dueAt IS NOT NULL')
            ->andWhere('c.dueAt < :today')
            ->andWhere('c.status NOT IN (:closed)')
            ->setParameter('owner', $owner)
            ->setParameter('today', $today)
            ->setParameter('closed', CapaStatus::terminalStatuses())
            ->getQuery()
            ->getSingleScalarResult();

        $capasAwaitingVerification = (int) $em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(\App\Entity\Qse\CAPAAction::class, 'c')
            ->where('c.owner = :owner')
            ->andWhere('c.status = :st')
            ->setParameter('owner', $owner)
            ->setParameter('st', CapaStatus::EN_ATTENTE_DE_VERIFICATION)
            ->getQuery()
            ->getSingleScalarResult();

        $staleDrafts = (int) $em->createQueryBuilder()
            ->select('COUNT(a.id)')
            ->from(\App\Entity\Qse\Audit::class, 'a')
            ->where('a.owner = :owner')
            ->andWhere('a.status = :brouillon')
            ->andWhere('COALESCE(a.updatedAt, a.createdAt) < :cutoff')
            ->setParameter('owner', $owner)
            ->setParameter('brouillon', AuditExecutionStatus::BROUILLON)
            ->setParameter('cutoff', $cutoffStale)
            ->getQuery()
            ->getSingleScalarResult();

        $capasNoResp = (int) $em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(\App\Entity\Qse\CAPAAction::class, 'c')
            ->where('c.owner = :owner')
            ->andWhere('c.status NOT IN (:closed)')
            ->andWhere('c.responsible IS NULL OR TRIM(c.responsible) = \'\'')
            ->setParameter('owner', $owner)
            ->setParameter('closed', CapaStatus::terminalStatuses())
            ->getQuery()
            ->getSingleScalarResult();

        $capasVerifNoComment = (int) $em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(\App\Entity\Qse\CAPAAction::class, 'c')
            ->where('c.owner = :owner')
            ->andWhere('c.status = :st')
            ->andWhere('c.effectivenessComment IS NULL OR TRIM(c.effectivenessComment) = \'\'')
            ->setParameter('owner', $owner)
            ->setParameter('st', CapaStatus::EN_ATTENTE_DE_VERIFICATION)
            ->getQuery()
            ->getSingleScalarResult();

        $avgCompliance = $em->createQueryBuilder()
            ->select('AVG(a.globalComplianceRate)')
            ->from(\App\Entity\Qse\Audit::class, 'a')
            ->where('a.owner = :owner')
            ->andWhere('a.globalComplianceRate IS NOT NULL')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
        $avgCompliancePercent = $avgCompliance !== null ? round((float) $avgCompliance, 1) : null;

        return [
            'overdueAuditPlans' => $overduePlans,
            'openCapaCount' => $openCapa,
            'dueCapaNext7Days' => $dueSoon,
            'criticalRisksWithoutCapa' => $criticalNoCapa,
            'openNonConformEvaluations' => $openNcEval,
            'overdueOpenCapas' => $overdueCapas,
            'capasAwaitingVerification' => $capasAwaitingVerification,
            'staleAuditDrafts' => $staleDrafts,
            'openCapasWithoutResponsible' => $capasNoResp,
            'capasInVerificationMissingComment' => $capasVerifNoComment,
            'avgAuditCompliancePercent' => $avgCompliancePercent,
        ];
    }

    /**
     * Données structurées pour les blocs 2–4 du dashboard manager (listes courtes).
     *
     * @return array{
     *   metrics: array,
     *   weak_chapters: list<array{chapter: string, issues: int}>,
     *   top_risks: list<\App\Entity\Qse\RiskMatrixEntry>,
     *   risks_review_soon: list<\App\Entity\Qse\RiskMatrixEntry>,
     *   capas_by_status: array<string, list<\App\Entity\Qse\CAPAAction>>
     * }
     */
    public function buildManagerDashboard(User $owner): array
    {
        $em = $this->registry->getManager();
        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $in14 = (new \DateTimeImmutable($today))->modify('+14 days')->format('Y-m-d');

        $weakRaw = $em->createQueryBuilder()
            ->select('req.chapter AS ch', 'COUNT(ev.id) AS cnt')
            ->from(\App\Entity\Qse\AuditEvaluation::class, 'ev')
            ->join('ev.requirement', 'req')
            ->join('ev.audit', 'aud')
            ->where('ev.owner = :owner')
            ->andWhere('ev.score IN (:scores)')
            ->setParameter('owner', $owner)
            ->setParameter('scores', [1, 2])
            ->groupBy('req.chapter')
            ->orderBy('COUNT(ev.id)', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getScalarResult();

        $weakChapters = [];
        foreach ($weakRaw as $row) {
            $weakChapters[] = [
                'chapter' => (string) ($row['ch'] ?? ''),
                'issues' => (int) ($row['cnt'] ?? 0),
            ];
        }

        /** @var list<\App\Entity\Qse\RiskMatrixEntry> $topRisks */
        $topRisks = $em->getRepository(\App\Entity\Qse\RiskMatrixEntry::class)
            ->createQueryBuilder('r')
            ->where('r.owner = :owner')
            ->andWhere('r.status != :cloture')
            ->setParameter('owner', $owner)
            ->setParameter('cloture', RiskEntryStatus::CLOTURE)
            ->orderBy('r.criticalityScore', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        /** @var list<\App\Entity\Qse\RiskMatrixEntry> $reviewSoon */
        $reviewSoon = $em->getRepository(\App\Entity\Qse\RiskMatrixEntry::class)
            ->createQueryBuilder('r')
            ->where('r.owner = :owner')
            ->andWhere('r.reviewAt IS NOT NULL')
            ->andWhere('r.reviewAt >= :today')
            ->andWhere('r.reviewAt <= :in14')
            ->andWhere('r.status != :cloture')
            ->setParameter('owner', $owner)
            ->setParameter('today', $today)
            ->setParameter('in14', $in14)
            ->setParameter('cloture', RiskEntryStatus::CLOTURE)
            ->orderBy('r.reviewAt', 'ASC')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();

        /** @var list<\App\Entity\Qse\CAPAAction> $openCapas */
        $openCapas = $em->getRepository(\App\Entity\Qse\CAPAAction::class)
            ->createQueryBuilder('c')
            ->where('c.owner = :owner')
            ->andWhere('c.status NOT IN (:closed)')
            ->setParameter('owner', $owner)
            ->setParameter('closed', CapaStatus::terminalStatuses())
            ->orderBy('c.dueAt', 'ASC')
            ->addOrderBy('c.updatedAt', 'DESC')
            ->setMaxResults(36)
            ->getQuery()
            ->getResult();

        /** @var array<string, list<\App\Entity\Qse\CAPAAction>> $byStatus */
        $byStatus = [];
        foreach ($openCapas as $capa) {
            $k = $capa->getStatus()->value;
            $byStatus[$k] ??= [];
            $byStatus[$k][] = $capa;
        }

        return [
            'metrics' => $this->getMetrics($owner),
            'weak_chapters' => $weakChapters,
            'top_risks' => $topRisks,
            'risks_review_soon' => $reviewSoon,
            'capas_by_status' => $byStatus,
        ];
    }

    /**
     * @return list<\App\Entity\Qse\CAPAAction>
     */
    public function findRecentCapasForKanban(User $owner, int $limit = 12): array
    {
        return $this->registry->getRepository(\App\Entity\Qse\CAPAAction::class)
            ->createQueryBuilder('c')
            ->where('c.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('c.updatedAt', 'DESC')
            ->addOrderBy('c.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<\App\Entity\Qse\Audit>
     */
    public function findRecentAudits(User $owner, int $limit = 5): array
    {
        return $this->registry->getRepository(\App\Entity\Qse\Audit::class)
            ->createQueryBuilder('a')
            ->where('a.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('a.auditedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
