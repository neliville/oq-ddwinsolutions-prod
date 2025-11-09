<?php

namespace App\Repository;

use App\Entity\AdminLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdminLog>
 */
class AdminLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminLog::class);
    }

    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('al')
            ->join('al.user', 'u')
            ->addSelect('u')
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByFilters(
        ?string $action = null,
        ?string $entityType = null,
        ?int $userId = null,
        ?\DateTimeInterface $start = null,
        ?\DateTimeInterface $end = null,
        int $page = 1,
        int $limit = 50
    ): array {
        $qb = $this->createQueryBuilder('al')
            ->join('al.user', 'u')
            ->addSelect('u')
            ->orderBy('al.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($action) {
            $qb->andWhere('al.action = :action')
                ->setParameter('action', $action);
        }

        if ($entityType) {
            $qb->andWhere('al.entityType = :entityType')
                ->setParameter('entityType', $entityType);
        }

        if ($userId) {
            $qb->andWhere('al.user = :userId')
                ->setParameter('userId', $userId);
        }

        if ($start) {
            $qb->andWhere('al.createdAt >= :start')
                ->setParameter('start', $start);
        }

        if ($end) {
            $qb->andWhere('al.createdAt <= :end')
                ->setParameter('end', $end);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByFilters(
        ?string $action = null,
        ?string $entityType = null,
        ?int $userId = null,
        ?\DateTimeInterface $start = null,
        ?\DateTimeInterface $end = null
    ): int {
        $qb = $this->createQueryBuilder('al')
            ->select('COUNT(al.id)');

        if ($action) {
            $qb->andWhere('al.action = :action')
                ->setParameter('action', $action);
        }

        if ($entityType) {
            $qb->andWhere('al.entityType = :entityType')
                ->setParameter('entityType', $entityType);
        }

        if ($userId) {
            $qb->andWhere('al.user = :userId')
                ->setParameter('userId', $userId);
        }

        if ($start) {
            $qb->andWhere('al.createdAt >= :start')
                ->setParameter('start', $start);
        }

        if ($end) {
            $qb->andWhere('al.createdAt <= :end')
                ->setParameter('end', $end);
        }

        return $qb->getQuery()->getSingleScalarResult() ?? 0;
    }

    public function findActionsList(): array
    {
        return $this->createQueryBuilder('al')
            ->select('DISTINCT al.action')
            ->orderBy('al.action', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function findEntityTypesList(): array
    {
        return $this->createQueryBuilder('al')
            ->select('DISTINCT al.entityType')
            ->where('al.entityType IS NOT NULL')
            ->orderBy('al.entityType', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }
}

