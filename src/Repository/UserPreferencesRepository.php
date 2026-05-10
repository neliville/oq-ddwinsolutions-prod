<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserPreferences;
use App\UserPreferences\NotificationFrequency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserPreferences>
 */
class UserPreferencesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPreferences::class);
    }

    public function getOrCreateForUser(User $user): UserPreferences
    {
        $existing = $this->findOneBy(['user' => $user]);
        if ($existing instanceof UserPreferences) {
            return $existing;
        }

        $prefs = new UserPreferences();
        $user->setPreferences($prefs);
        $this->getEntityManager()->persist($prefs);
        $this->getEntityManager()->flush();

        return $prefs;
    }

    /**
     * Utilisateurs éligibles à un digest hebdo (préférences persistées + fréquence hebdomadaire).
     *
     * @return list<User>
     */
    public function findUsersEligibleForWeeklyDigest(): array
    {
        /** @var list<UserPreferences> $prefs */
        $prefs = $this->createQueryBuilder('p')
            ->innerJoin('p.user', 'u')->addSelect('u')
            ->andWhere('p.notifyWeeklyDigest = :digest')
            ->andWhere('p.notificationFrequency = :weekly')
            ->setParameter('digest', true)
            ->setParameter('weekly', NotificationFrequency::WEEKLY)
            ->getQuery()
            ->getResult();

        $users = [];
        foreach ($prefs as $pref) {
            $u = $pref->getUser();
            if ($u instanceof User) {
                $users[] = $u;
            }
        }

        return $users;
    }
}
