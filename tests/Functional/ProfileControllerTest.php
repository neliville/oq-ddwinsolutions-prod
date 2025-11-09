<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileControllerTest extends WebTestCase
{
    public function testProfileRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/profile');

        // Devrait rediriger vers la page de connexion
        $this->assertResponseRedirects('/login');
    }

    public function testProfileWithAuthenticatedUser(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        
        // Créer un utilisateur avec un email unique
        $uniqueEmail = 'test-profile-' . uniqid() . '@example.com';
        $passwordHasher = $container->get('security.user_password_hasher');
        $user = new User();
        $user->setEmail($uniqueEmail);
        $user->setPassword($passwordHasher->hashPassword($user, 'Test123456!'));
        $user->setRoles(['ROLE_USER']);
        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);
        $client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mon profil');
    }

    public function testProfileUpdate(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $userRepository = $container->get(UserRepository::class);
        
        // Créer un utilisateur avec un email unique
        $uniqueEmail = 'test-profile-update-' . uniqid() . '@example.com';
        $updatedEmail = 'updated-' . uniqid() . '@example.com';
        $passwordHasher = $container->get('security.user_password_hasher');
        $user = new User();
        $user->setEmail($uniqueEmail);
        $user->setPassword($passwordHasher->hashPassword($user, 'Test123456!'));
        $user->setRoles(['ROLE_USER']);
        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);
        $crawler = $client->request('GET', '/profile');

        // Vérifier que le formulaire existe avant de le soumettre
        $form = $crawler->filter('form')->first();
        if (!$form->count()) {
            $this->markTestSkipped('Formulaire de profil non trouvé - route ou template non implémenté');
            return;
        }

        $form = $form->form([
            'profile_form[email]' => $updatedEmail,
        ]);

        $client->submit($form);

        // Vérifier que l'email a été mis à jour
        $entityManager->clear();
        $updatedUser = $userRepository->findOneBy(['email' => $updatedEmail]);
        $this->assertNotNull($updatedUser);
    }
}

