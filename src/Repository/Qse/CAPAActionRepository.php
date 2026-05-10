<?php

declare(strict_types=1);

namespace App\Repository\Qse;

use App\Entity\Qse\Audit;
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
}
