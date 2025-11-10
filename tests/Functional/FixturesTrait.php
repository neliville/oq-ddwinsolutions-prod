<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

trait FixturesTrait
{
    private const ADMIN_EMAIL = 'admin@test.local';
    private const ADMIN_PASSWORD = 'Admin123!';
    private const USER_PASSWORD = 'User123!';

    protected function loadAdminUser(EntityManagerInterface $entityManager): void
    {
        $admin = $entityManager->getRepository(User::class)->findOneBy(['email' => self::ADMIN_EMAIL]);

        if ($admin instanceof User) {
            return;
        }

        $admin = new User();
        $admin->setEmail(self::ADMIN_EMAIL);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, self::ADMIN_PASSWORD));

        $entityManager->persist($admin);
        $entityManager->flush();
    }

    protected function getAdminUser(EntityManagerInterface $entityManager): User
    {
        $admin = $entityManager->getRepository(User::class)->findOneBy(['email' => self::ADMIN_EMAIL]);

        if (!$admin instanceof User) {
            $this->loadAdminUser($entityManager);
            $admin = $entityManager->getRepository(User::class)->findOneBy(['email' => self::ADMIN_EMAIL]);
        }

        if (!$admin instanceof User) {
            throw new \RuntimeException('Admin user should exist after loadAdminUser call.');
        }

        return $admin;
    }

    protected function loadSampleUsers(EntityManagerInterface $entityManager, int $count = 3): void
    {
        $repository = $entityManager->getRepository(User::class);
        $created = false;

        for ($i = 1; $i <= $count; $i++) {
            $email = sprintf('user%d@test.local', $i);
            if ($repository->findOneBy(['email' => $email]) instanceof User) {
                continue;
            }

            $user = new User();
            $user->setEmail($email);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, self::USER_PASSWORD));

            $entityManager->persist($user);
            $created = true;
        }

        if ($created) {
            $entityManager->flush();
        }
    }
}
