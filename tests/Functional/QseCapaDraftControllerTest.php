<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Qse\CAPAAction;
use App\Entity\User;
use App\Entity\UserPreferences;
use App\Tests\TestCase\WebTestCaseWithDatabase;
use Symfony\Component\HttpFoundation\Response;

final class QseCapaDraftControllerTest extends WebTestCaseWithDatabase
{
    public function testNewDraftRequiresAuthentication(): void
    {
        $this->client->request('POST', '/dashboard/qse/capa/new-draft', [
            '_token' => 'invalid',
        ]);

        $this->assertResponseRedirects('/login');
    }

    public function testNewDraftRejectsInvalidCsrf(): void
    {
        $user = $this->createTestUser('capa-draft-csrf-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard/qse/risque/new');

        $this->client->request('POST', '/dashboard/qse/capa/new-draft', [
            '_token' => 'invalid',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testNewDraftPersistsCapaAndRedirectsToDashboard(): void
    {
        $user = $this->createTestUser('capa-draft-ok-' . uniqid() . '@example.com', 'Test123456!');
        $csrf = $this->fetchCapaNewDraftCsrfToken($user);
        $this->assertNotEmpty($csrf);

        $this->client->request('POST', '/dashboard/qse/capa/new-draft', [
            '_token' => $csrf,
        ]);

        $this->assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/dashboard', $location);
        $this->assertStringContainsString('activation=capa_created', $location);

        $this->entityManager->clear();
        $userReloaded = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        $this->assertNotNull($userReloaded);

        $capa = $this->entityManager->getRepository(CAPAAction::class)->findOneBy(['owner' => $userReloaded]);
        $this->assertInstanceOf(CAPAAction::class, $capa);
        $this->assertSame('Nouvelle action corrective', $capa->getTitle());
        $this->assertSame('onboarding-cockpit', $capa->getOrigin()?->getSlug());
        $this->assertSame(['_schema' => 1, 'source' => 'activation_onboarding'], $capa->getMetadata());

        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $userReloaded]);
        $this->assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertNotEmpty($prefs->getActivationState()['first_action_completed_at'] ?? null);
    }

    private function fetchCapaNewDraftCsrfToken(User $user): string
    {
        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');
        $csrfCrawler = $this->client->getCrawler()->filter('[data-onboarding-wizard-capa-new-draft-csrf-value]');
        self::assertGreaterThan(0, $csrfCrawler->count(), 'Jeton CSRF CAPA brouillon présent sur le dashboard.');
        $csrfToken = $csrfCrawler->first()->attr('data-onboarding-wizard-capa-new-draft-csrf-value');
        self::assertNotEmpty($csrfToken);

        return $csrfToken;
    }
}
