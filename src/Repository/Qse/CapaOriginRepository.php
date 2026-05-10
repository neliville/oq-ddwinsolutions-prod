<?php

declare(strict_types=1);

namespace App\Repository\Qse;

use App\Entity\Qse\CapaOrigin;
use App\Entity\User;
use App\Qse\Enum\CapaOriginKind;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CapaOrigin>
 */
class CapaOriginRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CapaOrigin::class);
    }

    public function findOneSystemBySlug(string $slug): ?CapaOrigin
    {
        return $this->createQueryBuilder('o')
            ->where('o.slug = :slug')
            ->andWhere('o.kind = :system')
            ->andWhere('o.owner IS NULL')
            ->setParameter('slug', $slug)
            ->setParameter('system', CapaOriginKind::SYSTEM)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<CapaOrigin>
     */
    public function findSystemOrigins(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.kind = :system')
            ->andWhere('o.owner IS NULL')
            ->andWhere('o.active = true')
            ->setParameter('system', CapaOriginKind::SYSTEM)
            ->orderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<CapaOrigin>
     */
    public function findByOwnerCustom(User $owner): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.owner = :owner')
            ->andWhere('o.kind = :custom')
            ->andWhere('o.active = true')
            ->setParameter('owner', $owner)
            ->setParameter('custom', CapaOriginKind::CUSTOM)
            ->orderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<CapaOrigin>
     */
    public function findSelectableOriginsForUser(User $user): array
    {
        return array_merge($this->findSystemOrigins(), $this->findByOwnerCustom($user));
    }

    public function slugExistsGlobally(string $slug): bool
    {
        $c = (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getSingleScalarResult();

        return $c > 0;
    }
}
