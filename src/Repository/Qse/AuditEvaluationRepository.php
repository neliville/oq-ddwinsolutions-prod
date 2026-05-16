<?php

declare(strict_types=1);

namespace App\Repository\Qse;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditEvaluation;
use App\Entity\Qse\AuditRequirement;
use App\Entity\User;
use App\Qse\Enum\AuditVerdict;
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
        $counts = $this->countOpenNcByOwner($owner);

        return $counts['major'] + $counts['minor'];
    }

    /**
     * @return array{major: int, minor: int}
     */
    public function countOpenNcByOwner(User $owner): array
    {
        $rows = $this->createQueryBuilder('e')
            ->select('e.verdict AS verdict', 'COUNT(e.id) AS cnt')
            ->join('e.audit', 'a')
            ->where('a.owner = :owner')
            ->andWhere('e.verdict IN (:verdicts)')
            ->setParameter('owner', $owner)
            ->setParameter('verdicts', [AuditVerdict::MAJOR_NC, AuditVerdict::MINOR_NC])
            ->groupBy('e.verdict')
            ->getQuery()
            ->getArrayResult();

        $major = 0;
        $minor = 0;
        foreach ($rows as $row) {
            $verdict = $row['verdict'];
            $value = $verdict instanceof AuditVerdict ? $verdict->value : (string) $verdict;
            $cnt = (int) ($row['cnt'] ?? 0);
            if ($value === AuditVerdict::MAJOR_NC->value) {
                $major = $cnt;
            } elseif ($value === AuditVerdict::MINOR_NC->value) {
                $minor = $cnt;
            }
        }

        return ['major' => $major, 'minor' => $minor];
    }

    /**
     * @param list<int> $auditIds
     *
     * @return array<int, array{answered: int, major: int, minor: int}>
     */
    public function aggregateProgressByAuditIds(array $auditIds): array
    {
        $auditIds = array_values(array_unique(array_filter($auditIds, static fn (int $v): bool => $v > 0)));
        /** @var array<int, array{answered: int, major: int, minor: int}> $out */
        $out = array_fill_keys($auditIds, ['answered' => 0, 'major' => 0, 'minor' => 0]);
        if ($auditIds === []) {
            return $out;
        }

        $rows = $this->createQueryBuilder('e')
            ->select('IDENTITY(e.audit) AS aid')
            ->addSelect('COUNT(e.id) AS answered')
            ->addSelect('SUM(CASE WHEN e.verdict = :major THEN 1 ELSE 0 END) AS major_nc')
            ->addSelect('SUM(CASE WHEN e.verdict = :minor THEN 1 ELSE 0 END) AS minor_nc')
            ->where('e.audit IN (:ids)')
            ->andWhere('e.verdict IS NOT NULL')
            ->andWhere('e.verdict != :notEval')
            ->setParameter('ids', $auditIds)
            ->setParameter('major', AuditVerdict::MAJOR_NC)
            ->setParameter('minor', AuditVerdict::MINOR_NC)
            ->setParameter('notEval', AuditVerdict::NOT_EVALUATED)
            ->groupBy('e.audit')
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $row) {
            $aid = (int) ($row['aid'] ?? 0);
            if ($aid > 0 && isset($out[$aid])) {
                $out[$aid] = [
                    'answered' => (int) ($row['answered'] ?? 0),
                    'major' => (int) ($row['major_nc'] ?? 0),
                    'minor' => (int) ($row['minor_nc'] ?? 0),
                ];
            }
        }

        return $out;
    }

    /**
     * @return array{answered: int, total: int}
     */
    public function sumProgressByOwner(User $owner): array
    {
        $answered = (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->join('e.audit', 'a')
            ->where('a.owner = :owner')
            ->andWhere('e.verdict IS NOT NULL')
            ->andWhere('e.verdict != :notEval')
            ->setParameter('owner', $owner)
            ->setParameter('notEval', AuditVerdict::NOT_EVALUATED)
            ->getQuery()
            ->getSingleScalarResult();

        $total = (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(req.id)')
            ->from(AuditRequirement::class, 'req')
            ->join(Audit::class, 'a', 'WITH', 'a.auditStandard = req.auditStandard')
            ->where('a.owner = :owner')
            ->andWhere('req.active = true')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();

        return ['answered' => $answered, 'total' => $total];
    }

    /**
     * @param list<int> $standardIds
     *
     * @return array<int, int>
     */
    public function countRequirementsByStandardIds(array $standardIds): array
    {
        $standardIds = array_values(array_unique(array_filter($standardIds, static fn (int $v): bool => $v > 0)));
        /** @var array<int, int> $out */
        $out = array_fill_keys($standardIds, 0);
        if ($standardIds === []) {
            return $out;
        }

        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(req.auditStandard) AS sid', 'COUNT(req.id) AS cnt')
            ->from(AuditRequirement::class, 'req')
            ->where('req.auditStandard IN (:ids)')
            ->andWhere('req.active = true')
            ->setParameter('ids', $standardIds)
            ->groupBy('req.auditStandard')
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $row) {
            $sid = (int) ($row['sid'] ?? 0);
            if ($sid > 0) {
                $out[$sid] = (int) ($row['cnt'] ?? 0);
            }
        }

        return $out;
    }
}
