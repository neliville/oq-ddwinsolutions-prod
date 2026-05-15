<?php

declare(strict_types=1);

namespace App\Repository\Qse;

use App\Entity\Qse\RiskMatrixEntry;
use App\Entity\User;
use App\Qse\Enum\RiskEntryStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RiskMatrixEntry>
 */
class RiskMatrixEntryRepository extends ServiceEntityRepository
{
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
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status != :cloture')
            ->andWhere('r.status = :critStat OR (r.criticalityScore IS NOT NULL AND r.criticalityScore >= :score)')
            ->setParameter('cloture', RiskEntryStatus::CLOTURE)
            ->setParameter('critStat', RiskEntryStatus::CRITIQUE)
            ->setParameter('score', 12)
            ->getQuery()
            ->getSingleScalarResult();
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
}
