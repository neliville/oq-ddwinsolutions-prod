<?php

namespace App\DataFixtures;

use App\Entity\HomepageTestimonial;
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

        if ($manager->getRepository(HomepageTestimonial::class)->count([]) === 0) {
            $manager->persist(
                (new HomepageTestimonial())
                    ->setFullName('Claire D.')
                    ->setJobTitle('Responsable QSE')
                    ->setCompany('Industrie manufacturière')
                    ->setQuote('L\'Ishikawa en 10 minutes avant ma revue d\'écart : simple, lisible, et l\'export PDF part directement dans le dossier audit.')
                    ->setRating(5)
                    ->setInitials('C')
                    ->setDisplayOrder(1)
                    ->setIsActive(true)
            );
            $manager->persist(
                (new HomepageTestimonial())
                    ->setFullName('Marc L.')
                    ->setJobTitle('Chef de projet QSE')
                    ->setCompany('PME services')
                    ->setQuote('On commence par les outils gratuits, puis le cockpit quand il faut relier CAPA, risques et audits dans une même vue.')
                    ->setRating(5)
                    ->setInitials('M')
                    ->setDisplayOrder(2)
                    ->setIsActive(true)
            );
        }

        $manager->flush();
    }
}
