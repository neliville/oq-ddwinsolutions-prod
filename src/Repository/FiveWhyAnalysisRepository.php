<?php

namespace App\Repository;

use App\Entity\FiveWhyAnalysis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FiveWhyAnalysis>
 */
class FiveWhyAnalysisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FiveWhyAnalysis::class);
    }

    /**
     * @return FiveWhyAnalysis[]
     */
    public function findByUser(?int $userId): array
    {
        $qb = $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->setParameter('user', $userId)
            ->orderBy('CASE WHEN f.updatedAt IS NULL THEN f.createdAt ELSE f.updatedAt END', 'DESC');

       

        return $qb->getQuery()->getResult();
    }

    /**
     * Count analyses by user
     */
    public function countByUser(?int $userId): int
    {
        $qb = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.user = :user')
            ->setParameter('user', $userId);

        return $qb->getQuery()->getSingleScalarResult() ?? 0;
    }
}

