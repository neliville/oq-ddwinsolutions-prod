<?php

namespace App\Repository;

use App\Entity\ExportLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExportLog>
 */
class ExportLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExportLog::class);
    }

    public function countByFormat(): array
    {
        return $this->createQueryBuilder('el')
            ->select('el.format AS format', 'COUNT(el.id) AS total')
            ->groupBy('el.format')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    public function countByTool(): array
    {
        return $this->createQueryBuilder('el')
            ->select('el.tool AS tool', 'COUNT(el.id) AS total')
            ->groupBy('el.tool')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    public function countByUser(int $limit = 10): array
    {
        return $this->createQueryBuilder('el')
            ->leftJoin('el.user', 'u')
            ->select('u.email AS email', 'COUNT(el.id) AS total')
            ->where('el.user IS NOT NULL')
            ->groupBy('u.email')
            ->orderBy('total', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function findRecent(int $limit = 20): array
    {
        return $this->createQueryBuilder('el')
            ->leftJoin('el.user', 'u')
            ->addSelect('u')
            ->orderBy('el.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ExportLog[]
     */
    public function findRecentByUser(User $user, int $limit = 15): array
    {
        return $this->createQueryBuilder('el')
            ->where('el.user = :user')
            ->setParameter('user', $user)
            ->orderBy('el.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<array{tool: string, total: string|int}>
     */
    public function countByToolForUser(User $user): array
    {
        return $this->createQueryBuilder('el')
            ->select('el.tool AS tool', 'COUNT(el.id) AS total')
            ->where('el.user = :user')
            ->setParameter('user', $user)
            ->groupBy('el.tool')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Détail des exports par outil et format pour un utilisateur.
     *
     * @return list<array{tool: string, format: string, total: string|int}>
     */
    public function countByToolAndFormatForUser(User $user): array
    {
        return $this->createQueryBuilder('el')
            ->select('el.tool AS tool', 'el.format AS format', 'COUNT(el.id) AS total')
            ->where('el.user = :user')
            ->setParameter('user', $user)
            ->groupBy('el.tool')
            ->addGroupBy('el.format')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    public function countForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('el')
            ->select('COUNT(el.id)')
            ->where('el.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
