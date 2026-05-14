<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\IshikawaAnalysis;
use App\Entity\User;
use App\Entity\UserPreferences;
use App\Tests\TestCase\WebTestCaseWithDatabase;
use Symfony\Component\HttpFoundation\Response;

final class IshikawaOnboardingDraftControllerTest extends WebTestCaseWithDatabase
{
    public function testNewDraftRequiresAuthentication(): void
    {
        $this->client->request('POST', '/ishikawa/onboarding-draft', [
            '_token' => 'invalid',
        ]);

        $this->assertResponseRedirects('/login');
    }

    public function testNewDraftRejectsInvalidCsrf(): void
    {
        $user = $this->createTestUser('ishikawa-draft-csrf-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');

        $this->client->request('POST', '/ishikawa/onboarding-draft', [
            '_token' => 'invalid',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testNewDraftPersistsAnalysisAndRedirectsToDashboard(): void
    {
        $user = $this->createTestUser('ishikawa-draft-ok-' . uniqid() . '@example.com', 'Test123456!');
        $csrf = $this->fetchIshikawaNewDraftCsrfToken($user);
        $this->assertNotEmpty($csrf);

        $this->client->request('POST', '/ishikawa/onboarding-draft', [
            '_token' => $csrf,
        ]);

        $this->assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/dashboard', $location);
        $this->assertStringContainsString('activation=ishikawa_created', $location);

        $this->entityManager->clear();
        $userReloaded = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        $this->assertNotNull($userReloaded);

        $analysis = $this->entityManager->getRepository(IshikawaAnalysis::class)->findOneBy(['user' => $userReloaded]);
        $this->assertInstanceOf(IshikawaAnalysis::class, $analysis);
        $this->assertSame('Nouvelle analyse des causes', $analysis->getTitle());
        $this->assertSame('{"categories":[]}', $analysis->getData());

        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $userReloaded]);
        $this->assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertNotEmpty($prefs->getActivationState()['first_action_completed_at'] ?? null);
    }

    private function fetchIshikawaNewDraftCsrfToken(User $user): string
    {
        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');
        $csrfCrawler = $this->client->getCrawler()->filter('[data-onboarding-wizard-ishikawa-new-draft-csrf-value]');
        self::assertGreaterThan(0, $csrfCrawler->count(), 'Jeton CSRF Ishikawa brouillon présent sur le dashboard.');
        $csrfToken = $csrfCrawler->first()->attr('data-onboarding-wizard-ishikawa-new-draft-csrf-value');
        self::assertNotEmpty($csrfToken);

        return $csrfToken;
    }
}
