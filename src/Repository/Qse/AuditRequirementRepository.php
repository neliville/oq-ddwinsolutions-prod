<?php

declare(strict_types=1);

namespace App\Repository\Qse;

use App\Entity\Qse\AuditRequirement;
use App\Qse\Import\AuditRequirementChapterSort;
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
            ->getQuery()
            ->getSingleColumnResult();

        $labels = array_values(array_map(static fn ($c): string => (string) $c, $chapters));

        return AuditRequirementChapterSort::sortDistinctChapters($labels);
    }

    public function countActiveForStandard(AuditStandard $standard): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.auditStandard = :std')
            ->andWhere('r.active = :a')
            ->setParameter('std', $standard)
            ->setParameter('a', true)
            ->getQuery()
            ->getSingleScalarResult();
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
        /** @var list<AuditRequirement> $rows */
        $rows = $this->createQueryBuilder('r')
            ->where('r.auditStandard = :std')
            ->setParameter('std', $standard)
            ->addOrderBy('r.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        usort(
            $rows,
            static function (AuditRequirement $a, AuditRequirement $b): int {
                $ch = AuditRequirementChapterSort::compareChapterHeadings($a->getChapter(), $b->getChapter());
                if ($ch !== 0) {
                    return $ch;
                }
                $ord = $a->getDisplayOrder() <=> $b->getDisplayOrder();
                if ($ord !== 0) {
                    return $ord;
                }

                return ($a->getId() ?? 0) <=> ($b->getId() ?? 0);
            },
        );

        return $rows;
    }
}
