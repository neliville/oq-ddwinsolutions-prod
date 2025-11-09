<?php

namespace App\Repository;

use App\Entity\IshikawaShareVisit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IshikawaShareVisit>
 */
class IshikawaShareVisitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IshikawaShareVisit::class);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByPeriod(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.visitedAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, array{shareId:int, title:string, visitCount:int}>
     */
    public function findTopSharedAnalyses(int $limit = 5): array
    {
        return $this->createQueryBuilder('v')
            ->select('IDENTITY(v.share) AS shareId')
            ->addSelect('a.title AS title')
            ->addSelect('COUNT(v.id) AS visitCount')
            ->join('v.share', 's')
            ->join('s.analysis', 'a')
            ->groupBy('shareId', 'title')
            ->orderBy('visitCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return array<int, array{visitedAt:\DateTimeImmutable, title:string, token:string}>
     */
    public function findRecentVisits(int $limit = 10): array
    {
        return $this->createQueryBuilder('v')
            ->select('v.visitedAt AS visitedAt')
            ->addSelect('a.title AS title')
            ->addSelect('s.token AS token')
            ->join('v.share', 's')
            ->join('s.analysis', 'a')
            ->orderBy('v.visitedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}


