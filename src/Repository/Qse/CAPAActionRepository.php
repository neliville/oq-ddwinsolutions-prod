<?php

declare(strict_types=1);

namespace App\Repository\Qse;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditEvaluation;
use App\Entity\Qse\CAPAAction;
use App\Entity\User;
use App\Qse\Enum\CapaStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CAPAAction>
 */
class CAPAActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CAPAAction::class);
    }

    /**
     * @return list<CAPAAction>
     */
    public function findByOwner(User $owner, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('c.dueAt', 'ASC')
            ->addOrderBy('c.id', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function countOpenCriticalByOwner(User $owner): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.owner = :owner')
            ->andWhere('c.status NOT IN (:closed)')
            ->andWhere('c.criticality IN (:crits)')
            ->setParameter('owner', $owner)
            ->setParameter('closed', CapaStatus::terminalStatuses())
            ->setParameter('crits', ['high', 'critical'])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCreatedBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.createdAt >= :from')
            ->andWhere('c.createdAt < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * CAPA ouvertes avec criticité élevée (tous utilisateurs).
     */
    public function adminCountOpenHighCriticality(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.status NOT IN (:closed)')
            ->andWhere('c.criticality IN (:crits)')
            ->setParameter('closed', CapaStatus::terminalStatuses())
            ->setParameter('crits', ['high', 'critical'])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * CAPA non closes avec échéance dépassée.
     */
    public function adminCountOpenOverdue(\DateTimeImmutable $todayUtcStart): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.status NOT IN (:closed)')
            ->andWhere('c.dueAt IS NOT NULL')
            ->andWhere('c.dueAt < :today')
            ->setParameter('closed', CapaStatus::terminalStatuses())
            ->setParameter('today', $todayUtcStart)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<CAPAAction>
     */
    public function adminFindRecentOpen(int $limit = 25): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.owner', 'o')->addSelect('o')
            ->where('c.status NOT IN (:closed)')
            ->setParameter('closed', CapaStatus::terminalStatuses())
            ->orderBy('c.dueAt', 'ASC')
            ->addOrderBy('c.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Nombre d’actions CAPA dont la source remonte à l’audit (via une évaluation ou une constatation).
     */
    public function countLinkedToAudit(Audit $audit): int
    {
        $id = $audit->getId();
        if ($id === null) {
            return 0;
        }

        return $this->countLinkedToAuditIds([$id])[$id] ?? 0;
    }

    /**
     * @param list<int> $auditIds
     *
     * @return array<int, int> id d’audit → nombre d’actions CAPA liées
     */
    public function countLinkedToAuditIds(array $auditIds): array
    {
        $auditIds = array_values(array_unique(array_filter($auditIds, static fn (int $v): bool => $v > 0)));
        if ($auditIds === []) {
            return [];
        }
        /** @var array<int, int> $out */
        $out = array_fill_keys($auditIds, 0);

        $rowsEv = $this->createQueryBuilder('c')
            ->select('IDENTITY(e.audit) AS aid', 'COUNT(c.id) AS cnt')
            ->join('c.sourceAuditEvaluation', 'e')
            ->where('e.audit IN (:ids)')
            ->setParameter('ids', $auditIds)
            ->groupBy('e.audit')
            ->getQuery()
            ->getArrayResult();
        foreach ($rowsEv as $row) {
            $aid = (int) ($row['aid'] ?? 0);
            if ($aid > 0 && isset($out[$aid])) {
                $out[$aid] += (int) ($row['cnt'] ?? 0);
            }
        }

        $rowsFind = $this->createQueryBuilder('c')
            ->select('IDENTITY(ev.audit) AS aid', 'COUNT(c.id) AS cnt')
            ->join('c.sourceAuditFinding', 'f')
            ->join('f.auditEvaluation', 'ev')
            ->where('ev.audit IN (:ids)')
            ->setParameter('ids', $auditIds)
            ->groupBy('ev.audit')
            ->getQuery()
            ->getArrayResult();
        foreach ($rowsFind as $row) {
            $aid = (int) ($row['aid'] ?? 0);
            if ($aid > 0 && isset($out[$aid])) {
                $out[$aid] += (int) ($row['cnt'] ?? 0);
            }
        }

        return $out;
    }

    /**
     * @return list<CAPAAction>
     */
    public function findLinkedToAudit(Audit $audit): array
    {
        $id = $audit->getId();
        if ($id === null) {
            return [];
        }

        $byEv = $this->createQueryBuilder('c')
            ->join('c.sourceAuditEvaluation', 'e')
            ->where('e.audit = :audit')
            ->setParameter('audit', $audit)
            ->getQuery()
            ->getResult();

        $byFinding = $this->createQueryBuilder('c')
            ->join('c.sourceAuditFinding', 'f')
            ->join('f.auditEvaluation', 'ev')
            ->where('ev.audit = :audit')
            ->setParameter('audit', $audit)
            ->getQuery()
            ->getResult();

        $merged = [];
        $seen = [];
        foreach (array_merge($byEv, $byFinding) as $capa) {
            if (!$capa instanceof CAPAAction) {
                continue;
            }
            $cid = $capa->getId();
            if ($cid !== null && !isset($seen[$cid])) {
                $seen[$cid] = true;
                $merged[] = $capa;
            }
        }

        return $merged;
    }

    public function findOpenBySourceAuditEvaluation(int $evaluationId): ?CAPAAction
    {
        $result = $this->createQueryBuilder('c')
            ->where('c.sourceAuditEvaluation = :evalId')
            ->andWhere('c.status NOT IN (:closed)')
            ->setParameter('evalId', $evaluationId)
            ->setParameter('closed', CapaStatus::terminalStatuses())
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof CAPAAction ? $result : null;
    }

    /**
     * @param list<int> $evaluationIds
     *
     * @return array<int, CAPAAction> id d’évaluation → CAPA ouverte la plus récente
     */
    public function findOpenBySourceAuditEvaluationIds(array $evaluationIds): array
    {
        $evaluationIds = array_values(array_unique(array_filter($evaluationIds, static fn (int $v): bool => $v > 0)));
        if ($evaluationIds === []) {
            return [];
        }

        /** @var list<CAPAAction> $capas */
        $capas = $this->createQueryBuilder('c')
            ->join('c.sourceAuditEvaluation', 'e')
            ->addSelect('e')
            ->where('e.id IN (:ids)')
            ->andWhere('c.status NOT IN (:closed)')
            ->setParameter('ids', $evaluationIds)
            ->setParameter('closed', CapaStatus::terminalStatuses())
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($capas as $capa) {
            if (!$capa instanceof CAPAAction) {
                continue;
            }
            $evalId = $capa->getSourceAuditEvaluation()?->getId();
            if ($evalId !== null && !isset($map[$evalId])) {
                $map[$evalId] = $capa;
            }
        }

        return $map;
    }

    /**
     * @return array<int, CAPAAction> id d’évaluation → CAPA ouverte
     */
    public function findOpenCapasIndexedByEvaluationForAudit(Audit $audit): array
    {
        $evaluationIds = [];
        foreach ($audit->getEvaluations() as $ev) {
            if ($ev instanceof AuditEvaluation && $ev->getId() !== null) {
                $evaluationIds[] = $ev->getId();
            }
        }

        return $this->findOpenBySourceAuditEvaluationIds($evaluationIds);
    }
}
