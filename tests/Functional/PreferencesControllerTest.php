<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\UserPreferences;
use App\Repository\UserPreferencesRepository;
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

    public function testDashboardPreferencesSaveUnchecksSections(): void
    {
        $email = 'prefs-dash-' . uniqid() . '@example.com';
        $user = $this->createTestUser($email, 'Test123456!');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/preferences?tab=dashboard');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="user_dashboard_preferences"]')->form();
        foreach (UserPreferences::dashboardSectionKeys() as $key) {
            if ($key === 'audits' || $key === 'pdca') {
                unset($form['user_dashboard_preferences[dash_'.$key.']']);
                continue;
            }
            $form['user_dashboard_preferences[dash_'.$key.']'] = true;
        }

        $this->client->submit($form);
        $this->assertResponseRedirects('/preferences?tab=dashboard');

        $this->entityManager->clear();
        $reloaded = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertInstanceOf(User::class, $reloaded);
        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $reloaded]);
        $this->assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertFalse($prefs->isDashboardSectionVisible('audits'));
        $this->assertFalse($prefs->isDashboardSectionVisible('pdca'));
        $this->assertTrue($prefs->isDashboardSectionVisible('deadlines'));

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $checkbox = $this->client->getCrawler()->filter('input[name="user_dashboard_preferences[dash_audits]"]');
        $this->assertGreaterThan(0, $checkbox->count());
        $this->assertNull($checkbox->attr('checked'));
    }

    public function testDashboardPreferencesSuccessUsesToastBridgeNotAlert(): void
    {
        $user = $this->createTestUser('prefs-dash-toast-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/preferences?tab=dashboard');
        $form = $crawler->filter('form[name="user_dashboard_preferences"]')->form();
        $this->client->submit($form);
        $this->client->followRedirect();

        $crawler = $this->client->getCrawler();
        $flashRegion = $crawler->filter('.flash-messages-global');
        $this->assertGreaterThan(0, $flashRegion->count());
        $this->assertStringContainsString(
            'data-controller="flash-toast"',
            $flashRegion->html(),
        );
        $this->assertStringContainsString(
            'Affichage du tableau de bord',
            $flashRegion->html(),
        );
        $this->assertSame(0, $flashRegion->filter('[data-slot="alert"]')->count());
    }

    public function testDashboardHidesAuditsAndPdcaAfterPreferencesSave(): void
    {
        $email = 'prefs-dash-hide-' . uniqid() . '@example.com';
        $user = $this->createTestUser($email, 'Test123456!');
        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(true);
        $prefs->setDashboardVisibility([
            'deadlines' => true,
            'capa' => true,
            'risks' => true,
            'audits' => false,
            'pdca' => false,
            'anomalies' => true,
            'kpi' => true,
        ]);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertStringNotContainsString('4. Audits', $content);
        $this->assertStringNotContainsString('5. PDCA (synthèse)', $content);
    }
}
