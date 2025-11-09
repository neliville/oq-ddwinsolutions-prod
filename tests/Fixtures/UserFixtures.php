<?php

namespace App\Tests\Fixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fixtures pour créer des utilisateurs de test
 */
class UserFixtures extends Fixture
{
    public const USER_EMAIL = 'test@outils-qualite.com';
    public const USER_PASSWORD = 'test123';
    public const ADMIN_EMAIL = 'admin@outils-qualite.com';
    public const ADMIN_PASSWORD = 'admin123';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Utilisateur normal
        $user = new User();
        $user->setEmail(self::USER_EMAIL);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::USER_PASSWORD));
        $user->setRoles(['ROLE_USER']);
        $manager->persist($user);
        $this->addReference('user', $user);

        // Utilisateur admin
        $admin = new User();
        $admin->setEmail(self::ADMIN_EMAIL);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, self::ADMIN_PASSWORD));
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $manager->persist($admin);
        $this->addReference('admin', $admin);

        $manager->flush();
    }
}

