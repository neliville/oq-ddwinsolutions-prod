<?php

namespace App\Tests\Functional;

use App\Entity\AmdecAnalysis;
use App\Entity\EightDAnalysis;
use App\Entity\FiveWhyAnalysis;
use App\Entity\IshikawaAnalysis;
use App\Entity\ParetoAnalysis;
use App\Entity\QqoqccpAnalysis;
use App\Entity\User;
use App\Entity\UserPreferences;
use App\Repository\UserPreferencesRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;

class DashboardControllerTest extends WebTestCaseWithDatabase
{
    public function testDashboardRequiresAuthentication(): void
    {
        $this->client->request('GET', '/dashboard');

        // Devrait rediriger vers la page de connexion
        $this->assertResponseRedirects('/login');
    }

    public function testDashboardWithAuthenticatedUser(): void
    {
        // Créer un utilisateur avec un email unique
        $uniqueEmail = 'test-dashboard-' . uniqid() . '@example.com';
        $user = new User();
        $user->setEmail($uniqueEmail);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Test123456!'));
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Créer quelques analyses de test avec les nouvelles entités
        for ($i = 1; $i <= 3; $i++) {
            $analysis = new IshikawaAnalysis();
            $analysis->setTitle("Diagramme Ishikawa {$i}");
            $analysis->setProblem("Problème {$i}");
            $analysis->setData(json_encode([
                'categories' => [
                    ['name' => 'Méthode', 'causes' => []],
                    ['name' => 'Matériel', 'causes' => []],
                ],
            ]));
            $analysis->setUser($user);
            $this->entityManager->persist($analysis);
        }

        for ($i = 1; $i <= 2; $i++) {
            $analysis = new FiveWhyAnalysis();
            $analysis->setTitle("Analyse 5 Pourquoi {$i}");
            $analysis->setProblem('Problème test');
            $analysis->setData(json_encode([
                'problem' => 'Problème test',
                'questions' => [],
            ]));
            $analysis->setUser($user);
            $this->entityManager->persist($analysis);
        }

        $qqoqccp = new QqoqccpAnalysis();
        $qqoqccp->setTitle('Analyse QQOQCCP');
        $qqoqccp->setSubject('Sujet test');
        $qqoqccp->setData(json_encode(['qui' => 'Equipe A']));
        $qqoqccp->setUser($user);
        $this->entityManager->persist($qqoqccp);

        $amdec = new AmdecAnalysis();
        $amdec->setTitle('Analyse AMDEC');
        $amdec->setSubject('Processus critique');
        $amdec->setData(json_encode(['entries' => []]));
        $amdec->setUser($user);
        $this->entityManager->persist($amdec);

        $pareto = new ParetoAnalysis();
        $pareto->setTitle('Analyse Pareto');
        $pareto->setDescription('Répartition causes');
        $pareto->setData(json_encode(['entries' => []]));
        $pareto->setUser($user);
        $this->entityManager->persist($pareto);

        $eightD = new EightDAnalysis();
        $eightD->setTitle('Rapport 8D');
        $eightD->setDescription('Résolution test');
        $eightD->setData(json_encode(['disciplines' => []]));
        $eightD->setUser($user);
        $this->entityManager->persist($eightD);

        $this->entityManager->flush();

        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(true);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Tableau de bord');
        $this->assertSelectorTextContains('body', 'Qu’est-ce qui nécessite mon attention aujourd’hui');
        $this->assertSelectorTextContains('body', 'CAPA en retard');
        $this->assertSelectorExists('[data-feature="ai-placeholder"]#dashboard-ai-suggestions-slot');
        $this->assertGreaterThan(0, $this->client->getCrawler()->filter('[data-slot="button"]')->count(), 'Au moins un bouton toolkit (ex. Nouvelle analyse)');
        $this->assertGreaterThan(5, $this->client->getCrawler()->filter('.rounded-lg.border.bg-card.text-card-foreground')->count(), 'Cartes cockpit / KPI en composant Card');
        $this->assertSelectorTextContains('body', 'Pilotage :');
    }

    public function testDashboardShowsActivationOnboardingWizardForEligibleUser(): void
    {
        $user = $this->createDashboardUser('test-dashboard-onb-' . uniqid() . '@example.com');
        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(false);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-controller="onboarding-wizard"]');
        $this->assertSelectorExists('[data-onboarding-wizard-csrf-value]');
        $this->assertSelectorTextContains('[data-controller="onboarding-wizard"]', 'Lancez votre première action utile');
        $this->assertSelectorTextNotContains('[data-controller="onboarding-wizard"]', 'Personnalisez votre espace QHSE');
        $this->assertSelectorExists('[data-onboarding-wizard-total-steps-value="3"]');
        $this->assertSelectorExists('[data-onboarding-field="job_function"]');
        $this->assertSelectorExists('[data-onboarding-field="piloting_focus"]');
        $this->assertSelectorExists('[data-onboarding-guided-action]');
        $this->assertGreaterThan(1, $this->client->getCrawler()->filter('#onboarding-job-function option')->count());
        $this->assertGreaterThan(1, $this->client->getCrawler()->filter('#onboarding-company-size option')->count());
        $this->assertGreaterThan(1, $this->client->getCrawler()->filter('#onboarding-main-activity option')->count());
    }

    public function testDashboardDoesNotShowActivationWizardForLegacyOnboardedUser(): void
    {
        $user = $this->createDashboardUser('test-dashboard-legacy-' . uniqid() . '@example.com');
        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(true);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('[data-controller="onboarding-wizard"]');
    }

    public function testDashboardDoesNotShowActivationWizardForAdmin(): void
    {
        $user = $this->createDashboardUser('test-dashboard-admin-' . uniqid() . '@example.com', ['ROLE_ADMIN']);
        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(false);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('[data-controller="onboarding-wizard"]');
    }

    public function testDashboardHidesCapaBlockWhenPreferenceDisabled(): void
    {
        $uniqueEmail = 'test-dashboard-capa-' . uniqid() . '@example.com';
        $user = new User();
        $user->setEmail($uniqueEmail);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Test123456!'));
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(true);
        $prefs->setDashboardVisibility(['capa' => false]);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('2. CAPA (vue synthétique)', $this->client->getResponse()->getContent());
    }

    public function testDashboardDoesNotShowActivationBannersForLegacyOnboardedUser(): void
    {
        $user = $this->createDashboardUser('test-dashboard-legacy-banner-' . uniqid() . '@example.com');
        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(true);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard?activation=audit_created');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('[data-activation-onboarding-banner]');
        $this->assertSelectorNotExists('[data-controller="onboarding-wizard"]');
    }

    public function testDashboardDoesNotShowActivationUiForCompletedUser(): void
    {
        $user = $this->createDashboardUser('test-dashboard-completed-' . uniqid() . '@example.com');
        $this->setActivationState($user, [
            'version' => 1,
            'status' => 'completed',
            'current_step' => 'aha',
            'goal' => 'audit',
            'recommended_action' => 'start_audit',
            'first_action_completed_at' => '2026-05-13T13:00:00+00:00',
            'aha_seen_at' => '2026-05-13T14:00:00+00:00',
        ]);
        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(true);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard?activation=audit_created');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('[data-activation-onboarding-banner]');
        $this->assertSelectorNotExists('[data-controller="onboarding-wizard"]');
    }

    public function testDashboardDoesNotShowActivationBannersForAdmin(): void
    {
        $user = $this->createDashboardUser('test-dashboard-admin-banner-' . uniqid() . '@example.com', ['ROLE_ADMIN']);
        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(false);
        $prefs->setActivationState([
            'version' => 1,
            'status' => 'action_pending',
            'current_step' => 'guided_action',
            'goal' => 'audit',
            'recommended_action' => 'start_audit',
        ]);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('[data-activation-onboarding-banner]');
    }

    public function testDashboardShowsNudgeBannerWhenActionPendingWithoutFirstAction(): void
    {
        $user = $this->createDashboardUser('test-dashboard-nudge-' . uniqid() . '@example.com');
        $this->setActivationState($user, [
            'version' => 1,
            'status' => 'action_pending',
            'current_step' => 'guided_action',
            'goal' => 'audit',
            'recommended_action' => 'start_audit',
        ]);

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-activation-onboarding-banner="nudge"]');
        $this->assertSelectorNotExists('[data-activation-onboarding-banner="aha"]');
    }

    public function testDashboardHidesNudgeBannerDuringCooldownAfterBannerSkip(): void
    {
        $user = $this->createDashboardUser('test-dashboard-nudge-cooldown-' . uniqid() . '@example.com');
        $this->setActivationState($user, [
            'version' => 1,
            'status' => 'action_pending',
            'current_step' => 'guided_action',
            'goal' => 'audit',
            'recommended_action' => 'start_audit',
            'nudge_dismissed_until' => (new \DateTimeImmutable('+7 days'))->format(\DateTimeInterface::ATOM),
        ]);

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('[data-activation-onboarding-banner="nudge"]');
    }

    public function testDashboardShowsAhaBannerAfterAuditCreationNotice(): void
    {
        $user = $this->createDashboardUser('test-dashboard-aha-audit-' . uniqid() . '@example.com');
        $this->setActivationState($user, [
            'version' => 1,
            'status' => 'in_progress',
            'current_step' => 'aha',
            'goal' => 'audit',
            'recommended_action' => 'start_audit',
            'first_action_completed_at' => '2026-05-13T13:00:00+00:00',
        ]);

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard?activation=audit_created');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-activation-onboarding-banner="aha"]');
        $this->assertSelectorNotExists('[data-activation-onboarding-banner="nudge"]');
        $this->assertSelectorExists('[data-activation-highlight-target="audit"]');
    }

    public function testDashboardShowsAhaBannerAfterRiskCreationNotice(): void
    {
        $user = $this->createDashboardUser('test-dashboard-aha-risk-' . uniqid() . '@example.com');
        $this->setActivationState($user, [
            'version' => 1,
            'status' => 'in_progress',
            'current_step' => 'aha',
            'goal' => 'risk',
            'recommended_action' => 'create_risk',
            'first_action_completed_at' => '2026-05-13T13:00:00+00:00',
        ]);

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard?activation=risk_created');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-activation-onboarding-banner="aha"]');
        $this->assertSelectorExists('[data-activation-highlight-target="risk"]');
    }

    public function testDashboardShowsAhaBannerAfterCapaCreationNotice(): void
    {
        $user = $this->createDashboardUser('test-dashboard-aha-capa-' . uniqid() . '@example.com');
        $this->setActivationState($user, [
            'version' => 1,
            'status' => 'in_progress',
            'current_step' => 'aha',
            'goal' => 'capa',
            'recommended_action' => 'create_capa_draft',
            'first_action_completed_at' => '2026-05-13T13:00:00+00:00',
        ]);

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard?activation=capa_created');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-activation-onboarding-banner="aha"]');
        $this->assertSelectorExists('[data-activation-highlight-target="capa"]');
    }

    public function testDashboardPrefersAhaBannerOverNudgeWhenActivationNoticePresent(): void
    {
        $user = $this->createDashboardUser('test-dashboard-aha-priority-' . uniqid() . '@example.com');
        $this->setActivationState($user, [
            'version' => 1,
            'status' => 'action_pending',
            'current_step' => 'guided_action',
            'goal' => 'audit',
            'recommended_action' => 'start_audit',
        ]);

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard?activation=audit_created');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-activation-onboarding-banner="aha"]');
        $this->assertSelectorNotExists('[data-activation-onboarding-banner="nudge"]');
        $this->assertSame(1, $this->client->getCrawler()->filter('[data-activation-onboarding-banner]')->count());
    }

    public function testDashboardCompletesActivationOnAhaAcknowledgement(): void
    {
        $user = $this->createDashboardUser('test-dashboard-aha-complete-' . uniqid() . '@example.com');
        $this->setActivationState($user, [
            'version' => 1,
            'status' => 'in_progress',
            'current_step' => 'aha',
            'goal' => 'audit',
            'recommended_action' => 'start_audit',
            'first_action_completed_at' => '2026-05-13T13:00:00+00:00',
        ]);

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/dashboard?activation=audit_created');
        $this->assertResponseIsSuccessful();
        $csrf = $crawler->filter('[data-activation-onboarding-aha-csrf-value]')->attr('data-activation-onboarding-aha-csrf-value');
        $this->assertNotEmpty($csrf);

        $this->client->request('POST', '/dashboard', [
            '_token' => $csrf,
            'activation_action' => 'complete_aha',
        ]);

        $this->assertResponseRedirects('/dashboard');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('[data-activation-onboarding-banner]');

        $this->entityManager->clear();
        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $user]);
        $this->assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertTrue($prefs->isProfileOnboardingCompleted());
        $this->assertSame('completed', $prefs->getActivationState()['status'] ?? null);
        $this->assertNotEmpty($prefs->getActivationState()['aha_seen_at'] ?? null);
    }

    public function testDashboardNudgeDismissUsesBannerSkipWithoutCompletingProfile(): void
    {
        $user = $this->createDashboardUser('test-dashboard-nudge-dismiss-' . uniqid() . '@example.com');
        $this->setActivationState($user, [
            'version' => 1,
            'status' => 'action_pending',
            'current_step' => 'guided_action',
            'goal' => 'audit',
            'recommended_action' => 'start_audit',
        ]);

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-activation-onboarding-banner="nudge"]');
        $csrf = $crawler->filter('[data-activation-onboarding-nudge-csrf-value]')->attr('data-activation-onboarding-nudge-csrf-value');
        $this->assertNotEmpty($csrf);

        $this->client->request('POST', '/preferences/onboarding/skip', [
            '_token' => $csrf,
            'source' => 'banner',
        ]);
        $this->assertResponseIsSuccessful();

        $this->entityManager->clear();
        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $user]);
        $this->assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertFalse($prefs->isProfileOnboardingCompleted());
        $this->assertNotEmpty($prefs->getActivationState()['nudge_dismissed_until'] ?? null);

        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('[data-activation-onboarding-banner="nudge"]');
    }

    /**
     * @param array<string, mixed> $state
     */
    private function setActivationState(User $user, array $state): void
    {
        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(false);
        $prefs->setActivationState($state);
        $this->entityManager->flush();
    }

    /**
     * @param list<string> $roles
     */
    private function createDashboardUser(string $email, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Test123456!'));
        $user->setRoles($roles);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}

