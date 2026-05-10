<?php

declare(strict_types=1);

namespace App\Repository\Qse;

use App\Entity\Qse\AuditStandard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditStandard>
 */
class AuditStandardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditStandard::class);
    }

    public function findOneByCode(string $code): ?AuditStandard
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * @return list<AuditStandard>
     */
    public function findVisibleOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.visible = true')
            ->andWhere('s.active = true')
            ->orderBy('s.displayOrder', 'ASC')
            ->addOrderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
