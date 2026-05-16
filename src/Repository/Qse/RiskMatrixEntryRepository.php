<?php

declare(strict_types=1);

namespace App\Repository\Qse;

use App\Entity\Qse\RiskMatrixEntry;
use App\Entity\User;
use App\Qse\Enum\RiskEntryStatus;
use App\Qse\Risk\ViewModel\RiskBoardFilters;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RiskMatrixEntry>
 */
class RiskMatrixEntryRepository extends ServiceEntityRepository
{
    private const CRITICAL_SCORE = 12;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RiskMatrixEntry::class);
    }

    /**
     * @return list<RiskMatrixEntry>
     */
    public function findByOwner(User $owner, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('r.criticalityScore', 'DESC')
            ->addOrderBy('r.id', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function countTotalByOwner(User $owner): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOpenByOwner(User $owner): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.owner = :owner')
            ->andWhere('r.status != :cloture')
            ->setParameter('owner', $owner)
            ->setParameter('cloture', RiskEntryStatus::CLOTURE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCriticalByOwner(User $owner): int
    {
        return (int) $this->applyCriticalCriteria(
            $this->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.owner = :owner')
                ->andWhere('r.status != :cloture')
                ->setParameter('owner', $owner)
                ->setParameter('cloture', RiskEntryStatus::CLOTURE),
        )->getQuery()->getSingleScalarResult();
    }

    public function countInTreatmentByOwner(User $owner): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.owner = :owner')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('owner', $owner)
            ->setParameter('statuses', [
                RiskEntryStatus::IDENTIFIE,
                RiskEntryStatus::EN_ANALYSE,
                RiskEntryStatus::SOUS_SURVEILLANCE,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countClosedThisMonthByOwner(User $owner): int
    {
        $start = new \DateTimeImmutable('first day of this month midnight');

        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.owner = :owner')
            ->andWhere('r.status = :cloture')
            ->andWhere('COALESCE(r.updatedAt, r.createdAt) >= :start')
            ->setParameter('owner', $owner)
            ->setParameter('cloture', RiskEntryStatus::CLOTURE)
            ->setParameter('start', $start)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countReviewOverdueByOwner(User $owner): int
    {
        $today = new \DateTimeImmutable('today');

        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.owner = :owner')
            ->andWhere('r.status != :cloture')
            ->andWhere('r.reviewAt IS NOT NULL')
            ->andWhere('r.reviewAt < :today')
            ->setParameter('owner', $owner)
            ->setParameter('cloture', RiskEntryStatus::CLOTURE)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countReviewWithinDaysByOwner(User $owner, int $days): int
    {
        $today = new \DateTimeImmutable('today');
        $end = $today->modify(sprintf('+%d days', $days));

        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.owner = :owner')
            ->andWhere('r.status != :cloture')
            ->andWhere('r.reviewAt IS NOT NULL')
            ->andWhere('r.reviewAt >= :today')
            ->andWhere('r.reviewAt <= :end')
            ->setParameter('owner', $owner)
            ->setParameter('cloture', RiskEntryStatus::CLOTURE)
            ->setParameter('today', $today)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<RiskMatrixEntry>
     */
    public function findRecentByOwner(User $owner, int $limit = 8): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('r.updatedAt', 'DESC')
            ->addOrderBy('r.createdAt', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{items: list<RiskMatrixEntry>, total: int}
     */
    public function findBoardPage(User $owner, RiskBoardFilters $filters): array
    {
        $qb = $this->buildBoardQuery($owner, $filters);
        $offset = ($filters->page - 1) * $filters->perPage;
        $qb->setFirstResult($offset)->setMaxResults($filters->perPage);

        $paginator = new Paginator($qb, fetchJoinCollection: true);

        /** @var list<RiskMatrixEntry> $items */
        $items = iterator_to_array($paginator);

        return ['items' => $items, 'total' => \count($paginator)];
    }

    public function countCreatedBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.createdAt >= :from')
            ->andWhere('r.createdAt < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function adminCountOpenNonClosed(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status != :cloture')
            ->setParameter('cloture', RiskEntryStatus::CLOTURE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function adminCountCriticalOrHighScore(): int
    {
        return (int) $this->applyCriticalCriteria(
            $this->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.status != :cloture')
                ->setParameter('cloture', RiskEntryStatus::CLOTURE),
        )->getQuery()->getSingleScalarResult();
    }

    /**
     * @return list<RiskMatrixEntry>
     */
    public function adminFindRecentOpen(int $limit = 25): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.owner', 'o')->addSelect('o')
            ->where('r.status != :cloture')
            ->setParameter('cloture', RiskEntryStatus::CLOTURE)
            ->orderBy('r.criticalityScore', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    private function buildBoardQuery(User $owner, RiskBoardFilters $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.linkedCapas', 'capa')->addSelect('capa')
            ->where('r.owner = :owner')
            ->setParameter('owner', $owner);

        $search = $filters->search;
        if ($search !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(r.identifiedRisk) LIKE :search',
                    'LOWER(COALESCE(r.description, \'\')) LIKE :search',
                    'LOWER(COALESCE(r.responsible, \'\')) LIKE :search',
                    'LOWER(COALESCE(r.concernedProcess, \'\')) LIKE :search',
                ),
            )->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        $status = $filters->statusEnum();
        if ($status !== null) {
            $qb->andWhere('r.status = :status')->setParameter('status', $status);
        }

        $this->applyCriticalityFilter($qb, $filters->criticality);

        match ($filters->sort) {
            RiskBoardFilters::SORT_OLDEST => $qb->orderBy('r.createdAt', 'ASC')->addOrderBy('r.id', 'ASC'),
            RiskBoardFilters::SORT_SCORE_ASC => $qb->orderBy('r.criticalityScore', 'ASC')->addOrderBy('r.id', 'ASC'),
            RiskBoardFilters::SORT_REVIEW_SOON => $qb
                ->addSelect('CASE WHEN r.reviewAt IS NULL THEN 1 ELSE 0 END AS HIDDEN reviewNull')
                ->orderBy('reviewNull', 'ASC')
                ->addOrderBy('r.reviewAt', 'ASC')
                ->addOrderBy('r.criticalityScore', 'DESC'),
            RiskBoardFilters::SORT_SCORE_DESC => $qb->orderBy('r.criticalityScore', 'DESC')->addOrderBy('r.id', 'DESC'),
            default => $qb->orderBy('r.updatedAt', 'DESC')
                ->addOrderBy('r.createdAt', 'DESC')
                ->addOrderBy('r.id', 'DESC'),
        };

        return $qb;
    }

    private function applyCriticalityFilter(QueryBuilder $qb, string $criticality): void
    {
        if ($criticality === '') {
            return;
        }

        match ($criticality) {
            RiskBoardFilters::CRITICALITY_LOW => $qb->andWhere(
                '(r.criticalityScore IS NULL OR r.criticalityScore < :medScore)
                AND r.status NOT IN (:critStat, :cloture, :maitrise)
                AND (r.riskLevel IS NULL OR r.riskLevel != :criticalLevel)',
            )
                ->setParameter('medScore', 6)
                ->setParameter('critStat', RiskEntryStatus::CRITIQUE)
                ->setParameter('cloture', RiskEntryStatus::CLOTURE)
                ->setParameter('maitrise', RiskEntryStatus::MAITRISE)
                ->setParameter('criticalLevel', 'critical'),
            RiskBoardFilters::CRITICALITY_MEDIUM => $qb->andWhere(
                'r.criticalityScore >= :medScore AND r.criticalityScore < :critScore
                AND r.status NOT IN (:critStat, :cloture, :maitrise)',
            )
                ->setParameter('medScore', 6)
                ->setParameter('critScore', self::CRITICAL_SCORE)
                ->setParameter('critStat', RiskEntryStatus::CRITIQUE)
                ->setParameter('cloture', RiskEntryStatus::CLOTURE)
                ->setParameter('maitrise', RiskEntryStatus::MAITRISE),
            RiskBoardFilters::CRITICALITY_CRITICAL => $this->applyCriticalCriteria($qb),
            RiskBoardFilters::CRITICALITY_CONTROLLED => $qb->andWhere(
                'r.status IN (:controlled)',
            )->setParameter('controlled', [RiskEntryStatus::MAITRISE, RiskEntryStatus::CLOTURE]),
            default => null,
        };
    }

    private function applyCriticalCriteria(QueryBuilder $qb): QueryBuilder
    {
        return $qb->andWhere(
            $qb->expr()->orX(
                'r.status = :critStat',
                'r.criticalityScore >= :critScore',
                'r.riskLevel = :criticalLevel',
            ),
        )
            ->setParameter('critStat', RiskEntryStatus::CRITIQUE)
            ->setParameter('critScore', self::CRITICAL_SCORE)
            ->setParameter('criticalLevel', 'critical');
    }
}
