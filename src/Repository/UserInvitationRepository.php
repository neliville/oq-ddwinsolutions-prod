<?php

declare(strict_types=1);

namespace App\Repository;

use App\Collaboration\CollaborationToken;
use App\Collaboration\InvitationStatus;
use App\Entity\User;
use App\Entity\UserInvitation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserInvitation>
 */
class UserInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserInvitation::class);
    }

    public function findByTokenHash(string $tokenHash): ?UserInvitation
    {
        return $this->createQueryBuilder('i')
            ->where('i.tokenHash = :h')
            ->setParameter('h', $tokenHash)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPendingByPlainToken(string $plainToken): ?UserInvitation
    {
        $hash = CollaborationToken::hashPlain($plainToken);

        return $this->createQueryBuilder('i')
            ->where('i.tokenHash = :h')
            ->andWhere('i.status = :s')
            ->setParameter('h', $hash)
            ->setParameter('s', InvitationStatus::ENVOYEE)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<UserInvitation>
     */
    public function findPendingForOwner(User $owner): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.owner = :o')
            ->andWhere('i.status = :s')
            ->setParameter('o', $owner)
            ->setParameter('s', InvitationStatus::ENVOYEE)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByOwnerAndStatus(User $owner, InvitationStatus $status): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.owner = :o')
            ->andWhere('i.status = :s')
            ->setParameter('o', $owner)
            ->setParameter('s', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
