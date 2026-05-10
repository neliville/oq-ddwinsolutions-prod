<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\UserPreferences;
use App\Tests\TestCase\WebTestCaseWithDatabase;
use Symfony\Component\HttpFoundation\Response;

final class PreferencesControllerTest extends WebTestCaseWithDatabase
{
    public function testPreferencesRequiresAuthentication(): void
    {
        $this->client->followRedirects(false);
        $this->client->request('GET', '/preferences');

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertStringContainsString('/login', $this->client->getResponse()->headers->get('Location') ?? '');
    }

    public function testPreferencesPageLoadsForAuthenticatedUser(): void
    {
        $user = $this->createTestUser('prefs-page-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($user);
        $this->client->request('GET', '/preferences');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Préférences');
    }

    public function testProfessionalPreferencesSave(): void
    {
        $email = 'prefs-save-' . uniqid() . '@example.com';
        $user = $this->createTestUser($email, 'Test123456!');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/preferences');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form();
        $form['user_professional_preferences[firstName]'] = 'Camille';
        $form['user_professional_preferences[lastName]'] = 'Martin';

        $this->client->submit($form);
        $this->assertResponseRedirects('/preferences?tab=profile');

        $this->entityManager->clear();
        $repo = $this->entityManager->getRepository(UserPreferences::class);
        $reloaded = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertInstanceOf(User::class, $reloaded);
        $prefs = $repo->findOneBy(['user' => $reloaded]);
        $this->assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertSame('Camille', $prefs->getFirstName());
        $this->assertSame('Martin', $prefs->getLastName());
    }

    public function testRequestPasswordResetRequiresAuthentication(): void
    {
        $this->client->followRedirects(false);
        $this->client->request('POST', '/preferences/request-password-reset', [
            '_token' => 'invalid',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    public function testRequestPasswordResetRejectsInvalidCsrf(): void
    {
        $user = $this->createTestUser('prefs-reset-csrf-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($user);
        $this->client->request('POST', '/preferences/request-password-reset', [
            '_token' => 'invalid-token',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testRequestPasswordResetSendsEmailWhenValid(): void
    {
        $email = 'prefs-reset-ok-' . uniqid() . '@example.com';
        $user = $this->createTestUser($email, 'Test123456!');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/preferences?tab=security');
        $this->assertResponseIsSuccessful();

        $csrfNode = $crawler->filter('#preferences-password-reset-csrf');
        $this->assertGreaterThan(0, $csrfNode->count());
        $token = $csrfNode->attr('value');
        $this->assertNotEmpty($token);

        $this->client->request('POST', '/preferences/request-password-reset', [
            '_token' => $token,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['ok'] ?? false);
        $this->assertEmailCount(1);
    }
}
