<?php

declare(strict_types=1);

namespace App\Repository\Qse;

use App\Entity\Qse\Audit;
use App\Entity\User;
use App\Qse\Audit\ViewModel\AuditBoardFilters;
use App\Qse\Enum\AuditExecutionStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Audit>
 */
class AuditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Audit::class);
    }

    /**
     * @return list<Audit>
     */
    public function findByOwner(User $owner): array
    {
        return $this->findBy(['owner' => $owner], ['auditedAt' => 'DESC', 'id' => 'DESC']);
    }

    public function findOneOwnedBy(int $id, User $owner): ?Audit
    {
        return $this->findOneBy(['id' => $id, 'owner' => $owner]);
    }

    /**
     * @return list<array{code: string, label: string, cnt: int}>
     */
    public function countGroupedByAuditStandardForOwner(User $owner): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('s.code AS code', 's.name AS label', 'COUNT(a.id) AS cnt')
            ->join('a.auditStandard', 's')
            ->where('a.owner = :owner')
            ->setParameter('owner', $owner)
            ->groupBy('s.id')
            ->orderBy('s.displayOrder', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'code' => (string) $r['code'],
                'label' => (string) $r['label'],
                'cnt' => (int) $r['cnt'],
            ];
        }

        return $out;
    }

    public function countCreatedBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.createdAt >= :from')
            ->andWhere('a.createdAt < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function adminCountNonTerminated(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.status NOT IN (:done)')
            ->setParameter('done', [
                AuditExecutionStatus::TERMINE,
                AuditExecutionStatus::VALIDE,
                AuditExecutionStatus::ARCHIVE,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function adminCountStaleDrafts(\DateTimeImmutable $olderThan): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.status = :brouillon')
            ->andWhere('a.updatedAt IS NOT NULL OR a.createdAt IS NOT NULL')
            ->andWhere('COALESCE(a.updatedAt, a.createdAt) < :cutoff')
            ->setParameter('brouillon', AuditExecutionStatus::BROUILLON)
            ->setParameter('cutoff', $olderThan)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Audit>
     */
    public function adminFindRecentNonTerminated(int $limit = 25): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.owner', 'o')->addSelect('o')
            ->join('a.auditStandard', 's')->addSelect('s')
            ->where('a.status NOT IN (:done)')
            ->setParameter('done', [
                AuditExecutionStatus::TERMINE,
                AuditExecutionStatus::VALIDE,
                AuditExecutionStatus::ARCHIVE,
            ])
            ->orderBy('a.auditedAt', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function buildBoardQuery(User $owner, AuditBoardFilters $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.auditStandard', 's')->addSelect('s')
            ->where('a.owner = :owner')
            ->setParameter('owner', $owner);

        if ($filters->search !== '') {
            $qb->andWhere(
                'LOWER(COALESCE(a.companyName, \'\')) LIKE :search
                OR LOWER(COALESCE(a.mainAuditor, \'\')) LIKE :search
                OR LOWER(s.code) LIKE :search
                OR LOWER(s.name) LIKE :search',
            )->setParameter('search', '%' . mb_strtolower($filters->search) . '%');
        }

        $status = $filters->statusEnum();
        if ($status !== null) {
            $qb->andWhere('a.status = :status')->setParameter('status', $status);
        }

        if ($filters->standardCode !== '') {
            $qb->andWhere('s.code = :code')->setParameter('code', $filters->standardCode);
        }

        if ($filters->auditor !== '') {
            $qb->andWhere('a.mainAuditor = :auditor')->setParameter('auditor', $filters->auditor);
        }

        if ($filters->complianceMin !== null) {
            $qb->andWhere('a.globalComplianceRate IS NOT NULL')
                ->andWhere('a.globalComplianceRate >= :cmin')
                ->setParameter('cmin', (float) $filters->complianceMin);
        }

        if ($filters->dateFrom !== null) {
            $qb->andWhere('a.auditedAt >= :dfrom')
                ->setParameter('dfrom', new \DateTimeImmutable($filters->dateFrom));
        }

        if ($filters->dateTo !== null) {
            $qb->andWhere('a.auditedAt <= :dto')
                ->setParameter('dto', new \DateTimeImmutable($filters->dateTo));
        }

        match ($filters->sort) {
            AuditBoardFilters::SORT_OLDEST => $qb->orderBy('a.auditedAt', 'ASC')->addOrderBy('a.id', 'ASC'),
            AuditBoardFilters::SORT_COMPLIANCE_ASC => $qb->orderBy('a.globalComplianceRate', 'ASC')->addOrderBy('a.id', 'DESC'),
            AuditBoardFilters::SORT_COMPLIANCE_DESC => $qb->orderBy('a.globalComplianceRate', 'DESC')->addOrderBy('a.id', 'DESC'),
            AuditBoardFilters::SORT_COMPANY => $qb->orderBy('a.companyName', 'ASC')->addOrderBy('a.auditedAt', 'DESC'),
            default => $qb->orderBy('a.auditedAt', 'DESC')->addOrderBy('a.id', 'DESC'),
        };

        return $qb;
    }

    /**
     * @return array{items: list<Audit>, total: int}
     */
    public function findBoardPage(User $owner, AuditBoardFilters $filters): array
    {
        $qb = $this->buildBoardQuery($owner, $filters);
        $offset = ($filters->page - 1) * $filters->perPage;
        $qb->setFirstResult($offset)->setMaxResults($filters->perPage);

        $paginator = new Paginator($qb, fetchJoinCollection: true);

        /** @var list<Audit> $items */
        $items = iterator_to_array($paginator);

        return ['items' => $items, 'total' => \count($paginator)];
    }

    /**
     * @return array<string, int>
     */
    public function countByStatusForOwner(User $owner): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('a.status AS status', 'COUNT(a.id) AS cnt')
            ->where('a.owner = :owner')
            ->setParameter('owner', $owner)
            ->groupBy('a.status')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $status = $row['status'];
            $key = $status instanceof AuditExecutionStatus ? $status->value : (string) $status;
            $out[$key] = (int) ($row['cnt'] ?? 0);
        }

        return $out;
    }

    public function countTotalForOwner(User $owner): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActiveStandardsForOwner(User $owner): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(DISTINCT s.id)')
            ->join('a.auditStandard', 's')
            ->where('a.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Audit>
     */
    public function findStaleByOwner(User $owner, int $days = 30, int $limit = 5): array
    {
        $cutoff = (new \DateTimeImmutable())->modify(sprintf('-%d days', $days));

        return $this->createQueryBuilder('a')
            ->join('a.auditStandard', 's')->addSelect('s')
            ->where('a.owner = :owner')
            ->andWhere('a.status = :brouillon')
            ->andWhere('COALESCE(a.updatedAt, a.createdAt) < :cutoff')
            ->setParameter('owner', $owner)
            ->setParameter('brouillon', AuditExecutionStatus::BROUILLON)
            ->setParameter('cutoff', $cutoff)
            ->orderBy('a.updatedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<string>
     */
    public function findDistinctAuditorsForOwner(User $owner): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('DISTINCT a.mainAuditor AS auditor')
            ->where('a.owner = :owner')
            ->andWhere('a.mainAuditor IS NOT NULL')
            ->andWhere('TRIM(a.mainAuditor) != \'\'')
            ->setParameter('owner', $owner)
            ->orderBy('a.mainAuditor', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $v = trim((string) ($row['auditor'] ?? ''));
            if ($v !== '') {
                $out[] = $v;
            }
        }

        return $out;
    }
}
