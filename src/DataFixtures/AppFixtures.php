<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Créer un utilisateur de test normal
        $user = new User();
        $user->setEmail('test@outils-qualite.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'test123'));
        $user->setRoles(['ROLE_USER']);
        $manager->persist($user);

        // Créer un utilisateur admin
        $admin = new User();
        $admin->setEmail('contact@outils-qualite.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $manager->persist($admin);

        $manager->flush();
    }
}
