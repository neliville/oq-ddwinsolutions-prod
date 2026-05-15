<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\User;
use App\Entity\UserPreferences;
use App\Repository\UserPreferencesRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;
use Symfony\Component\HttpFoundation\Response;

final class DashboardLayoutControllerTest extends WebTestCaseWithDatabase
{
    public function testPatchDashboardLayoutRequiresAuthentication(): void
    {
        $this->client->request(
            'PATCH',
            '/api/preferences/dashboard-layout',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['widgets' => [['id' => 'capa', 'visible' => true]]], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    public function testPatchDashboardLayoutUpdatesOrderAndVisibility(): void
    {
        $email = 'dash-layout-api-' . uniqid() . '@example.com';
        $user = $this->createTestUser($email, 'Test123456!');
        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(true);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
        $csrf = $this->client->getCrawler()
            ->filter('[data-dashboard-personalize-csrf-token-value]')
            ->attr('data-dashboard-personalize-csrf-token-value');
        $this->assertNotEmpty($csrf);

        $this->client->request(
            'PATCH',
            '/api/preferences/dashboard-layout',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CSRF_TOKEN' => $csrf,
            ],
            json_encode([
                'widgets' => [
                    ['id' => 'pdca', 'visible' => true],
                    ['id' => 'deadlines', 'visible' => true],
                    ['id' => 'capa', 'visible' => false],
                    ['id' => 'risks', 'visible' => true],
                    ['id' => 'audits', 'visible' => true],
                    ['id' => 'anomalies', 'visible' => true],
                    ['id' => 'kpi_stats', 'visible' => true],
                    ['id' => 'kpi_ai', 'visible' => true],
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['ok'] ?? false);

        $this->entityManager->clear();
        $reloaded = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertInstanceOf(User::class, $reloaded);
        $prefs = $prefsRepo->findOneBy(['user' => $reloaded]);
        $this->assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertFalse($prefs->isDashboardSectionVisible('capa'));
        $this->assertSame('pdca', $prefs->getDashboardLayout()['widgets'][0]['id'] ?? null);
    }
}
