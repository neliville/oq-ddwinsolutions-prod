<?php

namespace App\Repository;

use App\Entity\EightDAnalysis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EightDAnalysis>
 */
class EightDAnalysisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EightDAnalysis::class);
    }

    /**
     * @return EightDAnalysis[]
     */
    public function findByUser(?int $userId): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.user = :user')
            ->setParameter('user', $userId)
            ->orderBy('CASE WHEN e.updatedAt IS NULL THEN e.createdAt ELSE e.updatedAt END', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByUser(?int $userId): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.user = :user')
            ->setParameter('user', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}


