<?php

declare(strict_types=1);

namespace App\Repository\Qse;

use App\Entity\Qse\AuditRequirement;
use App\Entity\Qse\AuditStandard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditRequirement>
 */
class AuditRequirementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditRequirement::class);
    }

    public function findOneByStandardAndLegacyKey(AuditStandard $standard, string $legacyKey): ?AuditRequirement
    {
        return $this->findOneBy(['auditStandard' => $standard, 'legacyKey' => $legacyKey]);
    }

    /**
     * @return list<AuditRequirement>
     */
    public function findByChapterOrderedForStandard(string $chapter, AuditStandard $standard): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.chapter = :ch')
            ->andWhere('r.auditStandard = :std')
            ->andWhere('r.active = true')
            ->setParameter('ch', $chapter)
            ->setParameter('std', $standard)
            ->orderBy('r.displayOrder', 'ASC')
            ->addOrderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<string>
     */
    public function findDistinctChaptersForStandard(AuditStandard $standard): array
    {
        $chapters = $this->createQueryBuilder('r')
            ->select('r.chapter')
            ->distinct()
            ->where('r.active = :a')
            ->andWhere('r.auditStandard = :std')
            ->setParameter('a', true)
            ->setParameter('std', $standard)
            ->orderBy('r.chapter', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return array_values(array_map(static fn ($c): string => (string) $c, $chapters));
    }

    public function chapterExistsForStandard(string $chapter, AuditStandard $standard): bool
    {
        $c = (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.chapter = :ch')
            ->andWhere('r.auditStandard = :std')
            ->andWhere('r.active = true')
            ->setParameter('ch', $chapter)
            ->setParameter('std', $standard)
            ->getQuery()
            ->getSingleScalarResult();

        return $c > 0;
    }

    /**
     * @return list<AuditRequirement>
     */
    public function findAdminListingForStandard(AuditStandard $standard, int $limit = 500): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.auditStandard = :std')
            ->setParameter('std', $standard)
            ->orderBy('r.chapter', 'ASC')
            ->addOrderBy('r.displayOrder', 'ASC')
            ->addOrderBy('r.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
