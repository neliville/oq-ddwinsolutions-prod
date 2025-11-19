<?php

namespace App\Repository;

use App\Entity\PageView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Types;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<PageView>
 */
class PageViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageView::class);
    }

    public function countByPeriod(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        return $this->createQueryBuilder('pv')
            ->select('COUNT(pv.id)')
            ->where('pv.visitedAt >= :start')
            ->andWhere('pv.visitedAt <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    public function findMostVisitedPages(int $limit = 10): array
    {
        return $this->createQueryBuilder('pv')
            ->select('pv.url', 'COUNT(pv.id) as visitCount')
            ->groupBy('pv.url')
            ->orderBy('visitCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findTopReferers(int $limit = 10): array
    {
        return $this->createQueryBuilder('pv')
            ->select('pv.referer', 'COUNT(pv.id) as visitCount')
            ->where('pv.referer IS NOT NULL')
            ->andWhere('pv.referer != \'\'')
            ->groupBy('pv.referer')
            ->orderBy('visitCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findTopCountries(int $limit = 10): array
    {
        return $this->createQueryBuilder('pv')
            ->select('pv.country', 'COUNT(pv.id) as visitCount')
            ->where('pv.country IS NOT NULL')
            ->groupBy('pv.country')
            ->orderBy('visitCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findTopCities(int $limit = 10): array
    {
        return $this->createQueryBuilder('pv')
            ->select('pv.city', 'pv.country', 'COUNT(pv.id) as visitCount')
            ->where('pv.city IS NOT NULL')
            ->groupBy('pv.city', 'pv.country')
            ->orderBy('visitCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findTopDevices(int $limit = 10): array
    {
        return $this->createQueryBuilder('pv')
            ->select('pv.device', 'COUNT(pv.id) as visitCount')
            ->where('pv.device IS NOT NULL')
            ->groupBy('pv.device')
            ->orderBy('visitCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByUserType(bool $authenticated): int
    {
        $qb = $this->createQueryBuilder('pv')
            ->select('COUNT(pv.id)');

        if ($authenticated) {
            $qb->where('pv.user IS NOT NULL');
        } else {
            $qb->where('pv.user IS NULL');
        }

        return $qb->getQuery()->getSingleScalarResult() ?? 0;
    }

    public function countByUserTypeAndPeriod(bool $authenticated, \DateTimeInterface $start, \DateTimeInterface $end): int
    {
        $qb = $this->createQueryBuilder('pv')
            ->select('COUNT(pv.id)')
            ->where('pv.visitedAt >= :start')
            ->andWhere('pv.visitedAt <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($authenticated) {
            $qb->andWhere('pv.user IS NOT NULL');
        } else {
            $qb->andWhere('pv.user IS NULL');
        }

        return $qb->getQuery()->getSingleScalarResult() ?? 0;
    }

    public function findVisitsByDay(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $connection = $this->getEntityManager()->getConnection();
        $platform = $connection->getDatabasePlatform()->getName();
        $table = $this->getTableName($connection);

        if ($platform === 'postgresql') {
            $expression = "TO_CHAR(visited_at, 'YYYY-MM-DD')";
        } elseif ($platform === 'sqlite') {
            $expression = "strftime('%Y-%m-%d', visited_at)";
        } else { // mysql, mariadb…
            $expression = 'DATE(visited_at)';
        }

        $sql = sprintf(
            'SELECT %s AS visit_date, COUNT(*) AS visit_count FROM %s WHERE visited_at BETWEEN :start AND :end GROUP BY visit_date ORDER BY visit_date ASC',
            $expression,
            $table
        );

        return $connection->executeQuery(
            $sql,
            [
                'start' => $start,
                'end'   => $end,
            ],
            [
                'start' => Types::DATETIME_MUTABLE,
                'end'   => Types::DATETIME_MUTABLE,
            ]
        )->fetchAllAssociative();
    }

    public function findVisitsByMonth(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $connection = $this->getEntityManager()->getConnection();
        $platform = $connection->getDatabasePlatform()->getName();
        $table = $this->getTableName($connection);

        if ($platform === 'postgresql') {
            $expression = "TO_CHAR(visited_at, 'YYYY-MM')";
        } elseif ($platform === 'sqlite') {
            $expression = "strftime('%Y-%m', visited_at)";
        } else {
            $expression = "DATE_FORMAT(visited_at, '%Y-%m')";
        }

        $sql = sprintf(
            'SELECT %s AS visit_month, COUNT(*) AS visit_count FROM %s WHERE visited_at BETWEEN :start AND :end GROUP BY visit_month ORDER BY visit_month ASC',
            $expression,
            $table
        );

        return $connection->executeQuery(
            $sql,
            [
                'start' => $start,
                'end'   => $end,
            ],
            [
                'start' => Types::DATETIME_MUTABLE,
                'end'   => Types::DATETIME_MUTABLE,
            ]
        )->fetchAllAssociative();
    }

    /**
     * @return array{data: PageView[], total: int}
     */
    public function searchWithFilters(array $filters, int $page = 1, int $limit = 50): array
    {
        $page = max(1, $page);
        $limit = max(1, min(200, $limit));

        $qb = $this->createQueryBuilder('pv')
            ->leftJoin('pv.user', 'u')
            ->addSelect('u')
            ->orderBy('pv.visitedAt', 'DESC');

        if (!empty($filters['from'])) {
            $qb
                ->andWhere('pv.visitedAt >= :fromDate')
                ->setParameter('fromDate', $filters['from'], Types::DATETIME_IMMUTABLE);
        }

        if (!empty($filters['to'])) {
            $qb
                ->andWhere('pv.visitedAt <= :toDate')
                ->setParameter('toDate', $filters['to'], Types::DATETIME_IMMUTABLE);
        }

        if (!empty($filters['url'])) {
            $qb
                ->andWhere('pv.url LIKE :url')
                ->setParameter('url', '%' . $filters['url'] . '%');
        }

        if (!empty($filters['referer'])) {
            $qb
                ->andWhere('pv.referer LIKE :referer')
                ->setParameter('referer', '%' . $filters['referer'] . '%');
        }

        if (!empty($filters['userEmail'])) {
            $qb
                ->andWhere('u.email LIKE :userEmail')
                ->setParameter('userEmail', '%' . $filters['userEmail'] . '%');
        }

        if (!empty($filters['method'])) {
            $qb
                ->andWhere('pv.method = :method')
                ->setParameter('method', strtoupper($filters['method']));
        }

        if (!empty($filters['ipAddress'])) {
            $qb
                ->andWhere('pv.ipAddress LIKE :ipAddress')
                ->setParameter('ipAddress', '%' . $filters['ipAddress'] . '%');
        }

        if (!empty($filters['sessionId'])) {
            $qb
                ->andWhere('pv.sessionId LIKE :sessionId')
                ->setParameter('sessionId', '%' . $filters['sessionId'] . '%');
        }

        if (!empty($filters['country'])) {
            $qb
                ->andWhere('pv.country LIKE :country')
                ->setParameter('country', '%' . $filters['country'] . '%');
        }

        if (!empty($filters['city'])) {
            $qb
                ->andWhere('pv.city LIKE :city')
                ->setParameter('city', '%' . $filters['city'] . '%');
        }

        if (!empty($filters['type'])) {
            if ($filters['type'] === 'login') {
                $qb->andWhere('pv.url LIKE :loginRoute')
                    ->setParameter('loginRoute', '%login%');
            } elseif ($filters['type'] === 'page') {
                $qb->andWhere('pv.url NOT LIKE :loginRoute')
                    ->setParameter('loginRoute', '%login%');
            }
        }

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(pv.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb);

        return [
            'data' => iterator_to_array($paginator->getIterator(), false),
            'total' => $total,
        ];
    }

    private function getTableName(Connection $connection): string
    {
        return $connection->quoteIdentifier(
            $this->getEntityManager()->getClassMetadata(PageView::class)->getTableName()
        );
    }
}

