<?php

namespace App\Repository;

use App\Entity\IshikawaShareVisit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
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

    /**
     * @return array<int, array{visit_date:string, visit_count:int}>
     */
    public function findVisitsByDay(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $connection = $this->getEntityManager()->getConnection();
        $platform = $connection->getDatabasePlatform()->getName();
        $table = $this->getTableName($connection);

        if ($platform === 'postgresql') {
            $expression = "TO_CHAR(visited_at, 'YYYY-MM-DD')";
        } elseif ($platform === 'sqlite') {
            $expression = "strftime('%Y-%m-%d', visited_at)";
        } else {
            $expression = 'DATE(visited_at)';
        }

        $sql = sprintf(
            'SELECT %s AS visit_date, COUNT(*) AS visit_count FROM %s WHERE visited_at BETWEEN :start AND :end GROUP BY visit_date ORDER BY visit_date ASC',
            $expression,
            $table
        );

        return $connection->executeQuery(
            $sql,
            [
                'start' => $start,
                'end' => $end,
            ],
            [
                'start' => Types::DATETIME_MUTABLE,
                'end' => Types::DATETIME_MUTABLE,
            ]
        )->fetchAllAssociative();
    }

    private function getTableName(Connection $connection): string
    {
        return $connection->quoteIdentifier(
            $this->getEntityManager()->getClassMetadata(IshikawaShareVisit::class)->getTableName()
        );
    }
}


