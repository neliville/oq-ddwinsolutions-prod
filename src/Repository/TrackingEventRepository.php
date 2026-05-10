<?php

declare(strict_types=1);

namespace App\Repository;

use App\Application\Analytics\TrackingEventType;
use App\Entity\TrackingEvent;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrackingEvent>
 */
class TrackingEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackingEvent::class);
    }

    public function countByTypeBetween(
        TrackingEventType $type,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): int {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.eventType = :type')
            ->andWhere('t.createdAt >= :from')
            ->andWhere('t.createdAt < :to')
            ->setParameter('type', $type)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Utilisateurs distincts ayant au moins un événement dans la fenêtre (hors anonymes).
     */
    public function countDistinctUsersBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(DISTINCT u.id)')
            ->join('t.user', 'u')
            ->where('t.createdAt >= :from')
            ->andWhere('t.createdAt < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<array{event_type: string, cnt: int}>
     */
    public function countGroupedByTypeBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('t.eventType AS event_type', 'COUNT(t.id) AS cnt')
            ->where('t.createdAt >= :from')
            ->andWhere('t.createdAt < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('t.eventType')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $type = $row['event_type'];
            $out[] = [
                'event_type' => $type instanceof TrackingEventType ? $type->value : (string) $type,
                'cnt' => (int) $row['cnt'],
            ];
        }

        return $out;
    }

    /**
     * @return list<array{tool: string, cnt: int}>
     */
    public function countTopToolsOpenedBetween(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 8): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('t.tool AS tool', 'COUNT(t.id) AS cnt')
            ->where('t.eventType = :type')
            ->andWhere('t.createdAt >= :from')
            ->andWhere('t.createdAt < :to')
            ->andWhere('t.tool IS NOT NULL')
            ->andWhere('t.tool != :empty')
            ->setParameter('type', TrackingEventType::TOOL_OPENED)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('empty', '')
            ->groupBy('t.tool')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $out[] = ['tool' => (string) $row['tool'], 'cnt' => (int) $row['cnt']];
        }

        return $out;
    }

    /**
     * @return list<TrackingEvent>
     */
    public function findRecent(int $limit = 20): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<TrackingEvent>
     */
    public function findRecentForUser(User $user, \DateTimeImmutable $since, int $limit = 400): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :u')
            ->andWhere('t.createdAt >= :since')
            ->setParameter('u', $user)
            ->setParameter('since', $since)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
