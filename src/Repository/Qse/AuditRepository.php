<?php

declare(strict_types=1);

namespace App\Repository\Qse;

use App\Entity\Qse\Audit;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Audit>
 */
class AuditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Audit::class);
    }

    /**
     * @return list<Audit>
     */
    public function findByOwner(User $owner): array
    {
        return $this->findBy(['owner' => $owner], ['auditedAt' => 'DESC', 'id' => 'DESC']);
    }

    public function findOneOwnedBy(int $id, User $owner): ?Audit
    {
        return $this->findOneBy(['id' => $id, 'owner' => $owner]);
    }

    /**
     * @return list<array{code: string, label: string, cnt: int}>
     */
    public function countGroupedByAuditStandardForOwner(User $owner): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('s.code AS code', 's.name AS label', 'COUNT(a.id) AS cnt')
            ->join('a.auditStandard', 's')
            ->where('a.owner = :owner')
            ->setParameter('owner', $owner)
            ->groupBy('s.id')
            ->orderBy('s.displayOrder', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'code' => (string) $r['code'],
                'label' => (string) $r['label'],
                'cnt' => (int) $r['cnt'],
            ];
        }

        return $out;
    }

    public function countCreatedBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.createdAt >= :from')
            ->andWhere('a.createdAt < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
