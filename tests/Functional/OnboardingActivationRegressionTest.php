<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Qse\AuditStandard;
use App\Entity\User;
use App\Entity\UserPreferences;
use App\Repository\UserPreferencesRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;

final class OnboardingActivationRegressionTest extends WebTestCaseWithDatabase
{
    public function testAuditOnboardingPathClosesOnDashboardAha(): void
    {
        $user = $this->createActivationUser('onb-e2e-audit-' . uniqid() . '@example.com');
        $this->skipOnboardingModal($user);

        $standard = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        $this->assertInstanceOf(AuditStandard::class, $standard);
        $standardId = (int) $standard->getId();

        $crawler = $this->client->request('GET', '/dashboard/qse/audit/new?standard=' . $standardId . '&origin=onboarding');
        $this->assertResponseIsSuccessful();
        $csrf = $crawler->filter('input[name="_token"]')->attr('value');
        $this->assertNotEmpty($csrf);

        $this->client->request('POST', '/dashboard/qse/audit/new?standard=' . $standardId . '&origin=onboarding', [
            '_token' => $csrf,
            'audit_standard_id' => (string) $standardId,
            'companyName' => 'ACME',
            'mainAuditor' => 'Alice',
            'auditedAt' => '2026-05-01',
            'auditVersion' => '1.0',
        ]);
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-activation-onboarding-banner="aha"]');

        $this->completeAhaFromDashboard();
        $this->assertActivationClosed($user);
    }

    public function testRiskOnboardingPathClosesOnDashboardAha(): void
    {
        $user = $this->createActivationUser('onb-e2e-risk-' . uniqid() . '@example.com');
        $this->skipOnboardingModal($user);

        $crawler = $this->client->request('GET', '/dashboard/qse/risque/new?origin=onboarding');
        $this->assertResponseIsSuccessful();
        $csrf = $crawler->filter('input[name="_token"]')->attr('value');
        $this->assertNotEmpty($csrf);

        $this->client->request('POST', '/dashboard/qse/risque/new?origin=onboarding', [
            '_token' => $csrf,
            'identified_risk' => 'Risque onboarding e2e',
            'risk_category' => 'quality',
            'status' => 'identifie',
        ]);
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-activation-onboarding-banner="aha"]');

        $this->completeAhaFromDashboard();
        $this->assertActivationClosed($user);
    }

    public function testCapaDraftOnboardingPathClosesOnDashboardAha(): void
    {
        $user = $this->createActivationUser('onb-e2e-capa-' . uniqid() . '@example.com');

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
        $csrf = $this->client->getCrawler()->filter('[data-onboarding-wizard-capa-new-draft-csrf-value]')->attr('data-onboarding-wizard-capa-new-draft-csrf-value');
        $this->assertNotEmpty($csrf);

        $this->client->request('POST', '/dashboard/qse/capa/new-draft', [
            '_token' => $csrf,
        ]);
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-activation-onboarding-banner="aha"]');

        $this->completeAhaFromDashboard();
        $this->assertActivationClosed($user);
    }

    private function createActivationUser(string $email): User
    {
        $user = $this->createTestUser($email, 'Test123456!');
        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(false);
        $this->entityManager->flush();

        return $user;
    }

    private function skipOnboardingModal(User $user): void
    {
        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');
        $csrf = $this->client->getCrawler()->filter('[data-onboarding-wizard-csrf-value]')->attr('data-onboarding-wizard-csrf-value');
        $this->assertNotEmpty($csrf);

        $this->client->request('POST', '/preferences/onboarding/skip', [
            '_token' => $csrf,
        ]);
        $this->assertResponseIsSuccessful();
    }

    private function completeAhaFromDashboard(): void
    {
        $csrf = $this->client->getCrawler()->filter('[data-activation-onboarding-aha-csrf-value]')->attr('data-activation-onboarding-aha-csrf-value');
        $this->assertNotEmpty($csrf);

        $this->client->request('POST', '/dashboard', [
            '_token' => $csrf,
            'activation_action' => 'complete_aha',
        ]);
        $this->assertResponseRedirects('/dashboard');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('[data-activation-onboarding-banner]');
    }

    private function assertActivationClosed(User $user): void
    {
        $this->entityManager->clear();
        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $user]);
        $this->assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertTrue($prefs->isProfileOnboardingCompleted());
        $this->assertSame('completed', $prefs->getActivationState()['status'] ?? null);
        $this->assertNotEmpty($prefs->getActivationState()['aha_seen_at'] ?? null);
    }
}
