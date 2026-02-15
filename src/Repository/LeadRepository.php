<?php

namespace App\Repository;

use App\Entity\Lead;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lead>
 */
class LeadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lead::class);
    }

    /**
     * Trouve un lead par email
     */
    public function findByEmail(string $email): ?Lead
    {
        return $this->createQueryBuilder('l')
            ->where('l.email = :email')
            ->setParameter('email', $email)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte les leads par source
     */
    public function countBySource(string $source): int
    {
        return $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.source = :source')
            ->setParameter('source', $source)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les leads par outil
     */
    public function countByTool(string $tool): int
    {
        return $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.tool = :tool')
            ->setParameter('tool', $tool)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les leads créés dans une période
     */
    public function countByPeriod(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.createdAt >= :from')
            ->andWhere('l.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les leads récents
     */
    public function findRecent(int $limit = 50): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

