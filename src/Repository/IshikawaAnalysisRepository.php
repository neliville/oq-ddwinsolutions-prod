<?php

namespace App\Repository;

use App\Entity\IshikawaAnalysis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IshikawaAnalysis>
 */
class IshikawaAnalysisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IshikawaAnalysis::class);
    }

    /**
     * @return IshikawaAnalysis[]
     */
    public function findByUser(?int $userId): array
    {
        $qb = $this->createQueryBuilder('i')
            ->where('i.user = :user')
            ->setParameter('user', $userId)
            ->orderBy('i.createdAt', 'DESC');

       

        return $qb->getQuery()->getResult();
    }

    /**
     * Count analyses by user
     */
    public function countByUser(?int $userId): int
    {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.user = :user')
            ->setParameter('user', $userId);

        return $qb->getQuery()->getSingleScalarResult() ?? 0;
    }
}

