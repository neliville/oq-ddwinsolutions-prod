<?php

declare(strict_types=1);

namespace App\Repository\Qse;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditEvaluation;
use App\Entity\Qse\CAPAAction;
use App\Entity\User;
use App\Qse\Capa\ViewModel\CapaBoardFilters;
use App\Qse\Enum\CapaStatus;
use App\Qse\Enum\CapaType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

    public function countOpenCriticalLinkedToAuditsByOwner(User $owner): int
    {
        $today = (new \DateTimeImmutable())->format('Y-m-d');

        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.id)')
            ->join('c.sourceAuditEvaluation', 'e')
            ->join('e.audit', 'a')
            ->where('c.owner = :owner')
            ->andWhere('a.owner = :owner')
            ->andWhere('c.status NOT IN (:closed)')
            ->andWhere('c.criticality IN (:crits)')
            ->andWhere('(c.dueAt IS NULL OR c.dueAt < :today)')
            ->setParameter('owner', $owner)
            ->setParameter('closed', CapaStatus::terminalStatuses())
            ->setParameter('crits', ['high', 'critical'])
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countTotalForOwner(User $owner): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOpenForOwner(User $owner): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.owner = :owner')
            ->andWhere('c.status NOT IN (:closed)')
            ->setParameter('owner', $owner)
            ->setParameter('closed', CapaStatus::terminalStatuses())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<string, int>
     */
    public function countByStatusForOwner(User $owner): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.status AS status', 'COUNT(c.id) AS cnt')
            ->where('c.owner = :owner')
            ->setParameter('owner', $owner)
            ->groupBy('c.status')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $status = $row['status'];
            $key = $status instanceof CapaStatus ? $status->value : (string) $status;
            $out[$key] = (int) ($row['cnt'] ?? 0);
        }

        return $out;
    }

    public function buildBoardQuery(User $owner, CapaBoardFilters $filters): QueryBuilder
    {
        $today = new \DateTimeImmutable('today');

        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.origin', 'o')->addSelect('o')
            ->leftJoin('c.sourceAuditEvaluation', 'e')->addSelect('e')
            ->leftJoin('e.audit', 'a')->addSelect('a')
            ->where('c.owner = :owner')
            ->setParameter('owner', $owner);

        if ($filters->search !== '') {
            $qb->andWhere(
                'LOWER(c.title) LIKE :search
                OR LOWER(COALESCE(c.description, \'\')) LIKE :search
                OR LOWER(COALESCE(c.responsible, \'\')) LIKE :search
                OR LOWER(COALESCE(o.name, \'\')) LIKE :search',
            )->setParameter('search', '%' . mb_strtolower($filters->search) . '%');
        }

        $status = $filters->statusEnum();
        if ($status !== null) {
            $qb->andWhere('c.status = :status')->setParameter('status', $status);
        }

        $type = $filters->capaTypeEnum();
        if ($type !== null) {
            $qb->andWhere('c.capaType = :capaType')->setParameter('capaType', $type);
        }

        if ($filters->criticality !== '') {
            $qb->andWhere('c.criticality = :crit')->setParameter('crit', $filters->criticality);
        }

        if ($filters->overdueOnly) {
            $qb->andWhere('c.status NOT IN (:closed)')
                ->andWhere('c.dueAt IS NOT NULL')
                ->andWhere('c.dueAt < :today')
                ->setParameter('closed', CapaStatus::terminalStatuses())
                ->setParameter('today', $today);
        }

        if ($filters->dueFrom !== null) {
            $qb->andWhere('c.dueAt >= :dfrom')
                ->setParameter('dfrom', new \DateTimeImmutable($filters->dueFrom));
        }

        if ($filters->dueTo !== null) {
            $qb->andWhere('c.dueAt <= :dto')
                ->setParameter('dto', new \DateTimeImmutable($filters->dueTo));
        }

        match ($filters->sort) {
            CapaBoardFilters::SORT_OLDEST => $qb->orderBy('c.updatedAt', 'ASC')->addOrderBy('c.id', 'ASC'),
            CapaBoardFilters::SORT_DUE_ASC => $qb->addOrderBy('c.dueAt', 'ASC')->addOrderBy('c.id', 'DESC'),
            CapaBoardFilters::SORT_DUE_DESC => $qb->addOrderBy('c.dueAt', 'DESC')->addOrderBy('c.id', 'DESC'),
            CapaBoardFilters::SORT_TITLE => $qb->orderBy('c.title', 'ASC')->addOrderBy('c.id', 'DESC'),
            default => $qb->orderBy('c.updatedAt', 'DESC')->addOrderBy('c.id', 'DESC'),
        };

        return $qb;
    }

    /**
     * @return array{items: list<CAPAAction>, total: int}
     */
    public function findBoardPage(User $owner, CapaBoardFilters $filters): array
    {
        $qb = $this->buildBoardQuery($owner, $filters);
        $offset = ($filters->page - 1) * $filters->perPage;
        $qb->setFirstResult($offset)->setMaxResults($filters->perPage);

        $paginator = new Paginator($qb, fetchJoinCollection: true);

        /** @var list<CAPAAction> $items */
        $items = iterator_to_array($paginator);

        return ['items' => $items, 'total' => \count($paginator)];
    }

    /**
     * @return list<string>
     */
    public function findDistinctResponsiblesForOwner(User $owner): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('DISTINCT c.responsible AS responsible')
            ->where('c.owner = :owner')
            ->andWhere('c.responsible IS NOT NULL')
            ->andWhere('TRIM(c.responsible) != \'\'')
            ->setParameter('owner', $owner)
            ->orderBy('c.responsible', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $v = trim((string) ($row['responsible'] ?? ''));
            if ($v !== '') {
                $out[] = $v;
            }
        }

        return $out;
    }

    /**
     * @return list<CAPAAction>
     */
    public function findOverdueForOwner(User $owner, int $limit = 5): array
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('c')
            ->where('c.owner = :owner')
            ->andWhere('c.status NOT IN (:closed)')
            ->andWhere('c.dueAt IS NOT NULL')
            ->andWhere('c.dueAt < :today')
            ->setParameter('owner', $owner)
            ->setParameter('closed', CapaStatus::terminalStatuses())
            ->setParameter('today', $today)
            ->orderBy('c.dueAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<CAPAAction>
     */
    public function findAwaitingVerificationForOwner(User $owner, int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.owner = :owner')
            ->andWhere('c.status = :st')
            ->setParameter('owner', $owner)
            ->setParameter('st', CapaStatus::EN_ATTENTE_DE_VERIFICATION)
            ->orderBy('c.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
