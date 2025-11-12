<?php

namespace App\Repository;

use App\Entity\EightDShare;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EightDShare>
 */
class EightDShareRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EightDShare::class);
    }

    public function findValidByToken(string $token): ?EightDShare
    {
        $share = $this->createQueryBuilder('s')
            ->where('s.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$share) {
            return null;
        }

        return $share->isExpired() ? null : $share;
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
}
