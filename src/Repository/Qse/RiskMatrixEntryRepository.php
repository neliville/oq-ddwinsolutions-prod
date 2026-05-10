<?php

declare(strict_types=1);

namespace App\Repository\Qse;

use App\Entity\Qse\RiskMatrixEntry;
use App\Entity\User;
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
}
