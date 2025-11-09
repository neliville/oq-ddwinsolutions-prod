<?php

namespace App\Repository;

use App\Entity\ContactMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContactMessage>
 */
class ContactMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactMessage::class);
    }

    public function countUnread(): int
    {
        return $this->createQueryBuilder('cm')
            ->select('COUNT(cm.id)')
            ->where('cm.read = false OR cm.read IS NULL')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    public function countByPeriod(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        return $this->createQueryBuilder('cm')
            ->select('COUNT(cm.id)')
            ->where('cm.createdAt >= :start')
            ->andWhere('cm.createdAt <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    public function findByFilter(string $filter = 'all', int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('cm')
            ->orderBy('cm.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        switch ($filter) {
            case 'unread':
                $qb->where('cm.read = false OR cm.read IS NULL');
                break;
            case 'read':
                $qb->where('cm.read = true');
                break;
            case 'replied':
                $qb->where('cm.replied = true');
                break;
            case 'unreplied':
                $qb->where('cm.replied = false OR cm.replied IS NULL');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    public function countByFilter(string $filter = 'all'): int
    {
        $qb = $this->createQueryBuilder('cm')
            ->select('COUNT(cm.id)');

        switch ($filter) {
            case 'unread':
                $qb->where('cm.read = false OR cm.read IS NULL');
                break;
            case 'read':
                $qb->where('cm.read = true');
                break;
            case 'replied':
                $qb->where('cm.replied = true');
                break;
            case 'unreplied':
                $qb->where('cm.replied = false OR cm.replied IS NULL');
                break;
        }

        return $qb->getQuery()->getSingleScalarResult() ?? 0;
    }
}

