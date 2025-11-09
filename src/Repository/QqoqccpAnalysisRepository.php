<?php

namespace App\Repository;

use App\Entity\QqoqccpAnalysis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QqoqccpAnalysis>
 */
class QqoqccpAnalysisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QqoqccpAnalysis::class);
    }

    /**
     * @return QqoqccpAnalysis[]
     */
    public function findByUser(?int $userId): array
    {
        $qb = $this->createQueryBuilder('q')
            ->where('q.user = :user')
            ->setParameter('user', $userId)
            ->orderBy('CASE WHEN q.updatedAt IS NULL THEN q.createdAt ELSE q.updatedAt END', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function countByUser(?int $userId): int
    {
        $qb = $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.user = :user')
            ->setParameter('user', $userId);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}


