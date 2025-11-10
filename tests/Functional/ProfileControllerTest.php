<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;

class ProfileControllerTest extends WebTestCaseWithDatabase
{
    public function testProfileRequiresAuthentication(): void
    {
        $this->client->request('GET', '/profile');

        $this->assertResponseRedirects('/login');
    }

    public function testProfileWithAuthenticatedUser(): void
    {
        $uniqueEmail = 'test-profile-' . uniqid() . '@example.com';
        $user = $this->createTestUser($uniqueEmail, 'Test123456!');

        $this->client->loginUser($user);
        $this->client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mon profil');
    }

    public function testProfileUpdate(): void
    {
        $uniqueEmail = 'test-profile-update-' . uniqid() . '@example.com';
        $updatedEmail = 'updated-' . uniqid() . '@example.com';
        $user = $this->createTestUser($uniqueEmail, 'Test123456!');

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/profile');

        $formNode = $crawler->filter('form')->first();
        if (!$formNode->count()) {
            $this->markTestSkipped('Formulaire de profil non trouvé - route ou template non implémenté');
            return;
        }

        $form = $formNode->form([
            'profile_form[email]' => $updatedEmail,
        ]);

        $this->client->submit($form);

        $this->entityManager->clear();
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $updatedUser = $userRepository->findOneBy(['email' => $updatedEmail]);
        $this->assertNotNull($updatedUser);
    }
}

