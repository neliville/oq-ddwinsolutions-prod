<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findByFilter(string $filter = 'all', int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        switch ($filter) {
            case 'admin':
                $qb->andWhere('u.roles LIKE :adminRole')
                    ->setParameter('adminRole', '%ROLE_ADMIN%');
                break;
            case 'user':
                $qb->andWhere('u.roles NOT LIKE :adminRole OR u.roles IS NULL')
                    ->setParameter('adminRole', '%ROLE_ADMIN%');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    public function countByFilter(string $filter = 'all'): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)');

        switch ($filter) {
            case 'admin':
                $qb->andWhere('u.roles LIKE :adminRole')
                    ->setParameter('adminRole', '%ROLE_ADMIN%');
                break;
            case 'user':
                $qb->andWhere('u.roles NOT LIKE :adminRole OR u.roles IS NULL')
                    ->setParameter('adminRole', '%ROLE_ADMIN%');
                break;
        }

        return $qb->getQuery()->getSingleScalarResult() ?? 0;
    }
}
