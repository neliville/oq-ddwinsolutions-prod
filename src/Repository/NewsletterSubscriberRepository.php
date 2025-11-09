<?php

namespace App\Repository;

use App\Entity\NewsletterSubscriber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsletterSubscriber>
 */
class NewsletterSubscriberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsletterSubscriber::class);
    }

    public function countActive(): int
    {
        return $this->createQueryBuilder('ns')
            ->select('COUNT(ns.id)')
            ->where('ns.active = true')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    public function countNewSubscribers(\DateTimeInterface $since): int
    {
        return $this->createQueryBuilder('ns')
            ->select('COUNT(ns.id)')
            ->where('ns.subscribedAt >= :since')
            ->andWhere('ns.active = true')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    public function findByFilter(string $filter = 'all', int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('ns')
            ->orderBy('ns.subscribedAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        switch ($filter) {
            case 'active':
                $qb->where('ns.active = true');
                break;
            case 'inactive':
                $qb->where('ns.active = false');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    public function countByFilter(string $filter = 'all'): int
    {
        $qb = $this->createQueryBuilder('ns')
            ->select('COUNT(ns.id)');

        switch ($filter) {
            case 'active':
                $qb->where('ns.active = true');
                break;
            case 'inactive':
                $qb->where('ns.active = false');
                break;
        }

        return $qb->getQuery()->getSingleScalarResult() ?? 0;
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('ns')
            ->where('ns.active = true')
            ->orderBy('ns.subscribedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

