<?php

declare(strict_types=1);

namespace App\Repository\Qse;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditEvaluation;
use App\Entity\Qse\AuditRequirement;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditEvaluation>
 */
class AuditEvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditEvaluation::class);
    }

    public function findOneByAuditAndRequirement(Audit $audit, AuditRequirement $requirement): ?AuditEvaluation
    {
        return $this->findOneBy([
            'audit' => $audit,
            'requirement' => $requirement,
        ]);
    }

    /**
     * @return list<AuditEvaluation>
     */
    public function findByAuditAndChapter(Audit $audit, string $chapter): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.requirement', 'req')
            ->where('e.audit = :audit')
            ->andWhere('req.chapter = :chapter')
            ->setParameter('audit', $audit)
            ->setParameter('chapter', $chapter)
            ->orderBy('req.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countNonConformitiesForOwner(User $owner): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.owner = :owner')
            ->andWhere('e.score IN (:scores)')
            ->setParameter('owner', $owner)
            ->setParameter('scores', [1, 2])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
