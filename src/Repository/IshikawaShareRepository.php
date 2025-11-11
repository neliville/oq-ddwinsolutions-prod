<?php

namespace App\Repository;

use App\Entity\IshikawaShare;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IshikawaShare>
 */
class IshikawaShareRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IshikawaShare::class);
    }

    public function findValidByToken(string $token): ?IshikawaShare
    {
        /** @var IshikawaShare|null $share */
        $share = $this->createQueryBuilder('s')
            ->where('s.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$share) {
            return null;
        }

        if ($share->isExpired()) {
            return null;
        }

        return $share;
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActive(\DateTimeImmutable $asOf): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.expiresAt >= :now')
            ->setParameter('now', $asOf)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByPeriod(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, array{email:string, shareCount:int}>
     */
    public function findTopSharers(int $limit = 5): array
    {
        return $this->createQueryBuilder('s')
            ->select('u.email AS email')
            ->addSelect('COUNT(s.id) AS shareCount')
            ->join('s.analysis', 'a')
            ->join('a.user', 'u')
            ->groupBy('u.email')
            ->orderBy('shareCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return array<int, array{token:string, title:string, createdAt:\DateTimeImmutable, expiresAt:\DateTimeImmutable, owner:string}>
     */
    public function findRecentShares(int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.token AS token')
            ->addSelect('s.createdAt AS createdAt')
            ->addSelect('s.expiresAt AS expiresAt')
            ->addSelect('a.title AS title')
            ->addSelect('u.email AS owner')
            ->join('s.analysis', 'a')
            ->join('a.user', 'u')
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return array<int, array{share_date:string, share_count:int}>
     */
    public function findSharesByDay(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $connection = $this->getEntityManager()->getConnection();
        $platform = $connection->getDatabasePlatform()->getName();
        $table = $this->getTableName($connection);

        if ($platform === 'postgresql') {
            $expression = "TO_CHAR(created_at, 'YYYY-MM-DD')";
        } elseif ($platform === 'sqlite') {
            $expression = "strftime('%Y-%m-%d', created_at)";
        } else {
            $expression = 'DATE(created_at)';
        }

        $sql = sprintf(
            'SELECT %s AS share_date, COUNT(*) AS share_count FROM %s WHERE created_at BETWEEN :start AND :end GROUP BY share_date ORDER BY share_date ASC',
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
            $this->getEntityManager()->getClassMetadata(IshikawaShare::class)->getTableName()
        );
    }
}


