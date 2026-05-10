<?php

declare(strict_types=1);

namespace App\Repository\Qse;

use App\Entity\Qse\AuditPlan;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditPlan>
 */
class AuditPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditPlan::class);
    }

    /**
     * @return list<AuditPlan>
     */
    public function findByOwner(User $owner): array
    {
        return $this->findBy(['owner' => $owner], ['plannedAt' => 'DESC', 'id' => 'DESC']);
    }
}
