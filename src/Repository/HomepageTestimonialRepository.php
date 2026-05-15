<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\HomepageTestimonial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HomepageTestimonial>
 */
class HomepageTestimonialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HomepageTestimonial::class);
    }

    /**
     * @return list<HomepageTestimonial>
     */
    public function findActiveForHomepage(int $limit = 2): array
    {
        /** @var list<HomepageTestimonial> $results */
        $results = $this->createQueryBuilder('t')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.displayOrder', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();

        return $results;
    }

    /**
     * @return list<HomepageTestimonial>
     */
    public function findAllOrdered(): array
    {
        /** @var list<HomepageTestimonial> $results */
        $results = $this->createQueryBuilder('t')
            ->orderBy('t.displayOrder', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $results;
    }
}
