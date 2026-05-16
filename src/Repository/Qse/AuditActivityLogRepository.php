<?php

declare(strict_types=1);

namespace App\Repository\Qse;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditActivityLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditActivityLog>
 */
class AuditActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditActivityLog::class);
    }

    /**
     * @return list<AuditActivityLog>
     */
    public function findRecentForAudit(Audit $audit, int $limit = 30): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.audit = :audit')
            ->setParameter('audit', $audit)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<AuditActivityLog>
     */
    public function findRecentForOwner(User $owner, int $limit = 8): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.audit', 'a')->addSelect('a')
            ->leftJoin('l.actor', 'actor')->addSelect('actor')
            ->leftJoin('l.auditEvaluation', 'ev')->addSelect('ev')
            ->leftJoin('ev.requirement', 'req')->addSelect('req')
            ->where('a.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
