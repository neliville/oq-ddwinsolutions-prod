<?php

namespace App\Repository;

use App\Entity\AmdecAnalysis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AmdecAnalysis>
 */
class AmdecAnalysisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AmdecAnalysis::class);
    }

    /**
     * @return AmdecAnalysis[]
     */
    public function findByUser(?int $userId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->setParameter('user', $userId)
            ->orderBy('CASE WHEN a.updatedAt IS NULL THEN a.createdAt ELSE a.updatedAt END', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByUser(?int $userId): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.user = :user')
            ->setParameter('user', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}


