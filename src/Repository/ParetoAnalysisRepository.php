<?php

namespace App\Repository;

use App\Entity\ParetoAnalysis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParetoAnalysis>
 */
class ParetoAnalysisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParetoAnalysis::class);
    }

    /**
     * @return ParetoAnalysis[]
     */
    public function findByUser(?int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->setParameter('user', $userId)
            ->orderBy('CASE WHEN p.updatedAt IS NULL THEN p.createdAt ELSE p.updatedAt END', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByUser(?int $userId): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.user = :user')
            ->setParameter('user', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}


