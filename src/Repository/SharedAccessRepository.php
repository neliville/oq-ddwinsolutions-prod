<?php

declare(strict_types=1);

namespace App\Repository;

use App\Collaboration\CollaborationToken;
use App\Collaboration\SharedAccessStatus;
use App\Collaboration\SharedResourceType;
use App\Entity\SharedAccess;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SharedAccess>
 */
class SharedAccessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SharedAccess::class);
    }

    public function findActiveByPlainToken(string $plainToken): ?SharedAccess
    {
        $hash = CollaborationToken::hashPlain($plainToken);

        $row = $this->createQueryBuilder('s')
            ->where('s.tokenHash = :h')
            ->andWhere('s.status = :st')
            ->setParameter('h', $hash)
            ->setParameter('st', SharedAccessStatus::ACTIF)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $row instanceof SharedAccess ? $row : null;
    }

    /**
     * @return list<SharedAccess>
     */
    public function findActiveForOwner(User $owner): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.owner = :o')
            ->andWhere('s.status = :st')
            ->andWhere('s.expiresAt > :now')
            ->setParameter('o', $owner)
            ->setParameter('st', SharedAccessStatus::ACTIF)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countActiveForOwner(User $owner): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.owner = :o')
            ->andWhere('s.status = :st')
            ->andWhere('s.expiresAt > :now')
            ->setParameter('o', $owner)
            ->setParameter('st', SharedAccessStatus::ACTIF)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<SharedAccess>
     */
    public function findActiveByOwnerAndTarget(User $owner, SharedResourceType $type, int $targetId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.owner = :o')
            ->andWhere('s.targetType = :t')
            ->andWhere('s.targetId = :tid')
            ->andWhere('s.status = :st')
            ->setParameter('o', $owner)
            ->setParameter('t', $type)
            ->setParameter('tid', $targetId)
            ->setParameter('st', SharedAccessStatus::ACTIF)
            ->getQuery()
            ->getResult();
    }
}
