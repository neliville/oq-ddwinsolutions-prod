<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\UserPreferences;
use App\Repository\UserPreferencesRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;
use App\UserPreferences\CompanySize;
use App\UserPreferences\JobFunction;
use App\UserPreferences\MainActivity;
use App\UserPreferences\PilotingFocus;
use App\UserPreferences\PrimaryStandard;
use Symfony\Component\HttpFoundation\Response;

final class OnboardingControllerTest extends WebTestCaseWithDatabase
{
    public function testOnboardingStepRequiresAuthentication(): void
    {
        $this->client->request('POST', '/preferences/onboarding/step', [
            '_token' => 'invalid',
            'step' => '1',
            'value' => 'qse_lead',
        ]);
        $this->assertResponseRedirects('/login');
    }

    public function testOnboardingStepRejectsInvalidCsrf(): void
    {
        $user = $this->createUser('onb-csrf-' . uniqid() . '@example.com');
        $this->client->loginUser($user);
        $this->client->request('POST', '/preferences/onboarding/step', [
            '_token' => 'bad-token',
            'step' => '1',
            'value' => 'qse_lead',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testOnboardingStepSavesJobFunctionAndCompletesAfterStep6(): void
    {
        $user = $this->createUser('onb-flow-' . uniqid() . '@example.com');
        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(false);
        $this->entityManager->flush();

        $csrfToken = $this->fetchOnboardingCsrfToken($user);

        $steps = [
            1 => 'qse_lead',
            2 => 'solo',
            3 => 'industry',
            4 => 'iso_9001',
            5 => 'audit',
            6 => 'search',
        ];

        foreach ($steps as $step => $value) {
            $this->client->request('POST', '/preferences/onboarding/step', [
                '_token' => $csrfToken,
                'step' => (string) $step,
                'value' => $value,
            ]);
            $this->assertResponseIsSuccessful();
            $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertTrue($data['ok']);
            if ($step === 6) {
                $this->assertTrue($data['completed']);
            } else {
                $this->assertFalse($data['completed']);
            }
        }

        $this->entityManager->clear();
        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $user]);
        self::assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertTrue($prefs->isProfileOnboardingCompleted());
    }

    public function testActivationContextStepPersistsProfileFields(): void
    {
        $user = $this->createUser('onb-ctx-' . uniqid() . '@example.com');
        $this->prepareIncompleteOnboarding($user);
        $csrfToken = $this->fetchOnboardingCsrfToken($user);

        $this->client->request('POST', '/preferences/onboarding/step', [
            '_token' => $csrfToken,
            'activation_step' => 'context',
            'job_function' => JobFunction::QSE_LEAD->value,
            'company_size' => CompanySize::P2_10->value,
            'main_activity' => MainActivity::INDUSTRY->value,
        ]);
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['ok']);
        $this->assertFalse($data['completed']);
        $this->assertSame('goal', $data['current_step']);

        $this->entityManager->clear();
        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $user]);
        self::assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertSame(JobFunction::QSE_LEAD, $prefs->getJobFunction());
        $this->assertSame(CompanySize::P2_10, $prefs->getCompanySize());
        $this->assertSame(MainActivity::INDUSTRY, $prefs->getMainActivity());
        $this->assertSame('in_progress', $prefs->getActivationState()['status'] ?? null);
    }

    public function testActivationGoalStepPersistsPilotingFocusAndPrimaryStandard(): void
    {
        $user = $this->createUser('onb-goal-' . uniqid() . '@example.com');
        $this->prepareIncompleteOnboarding($user);
        $csrfToken = $this->fetchOnboardingCsrfToken($user);

        $this->client->request('POST', '/preferences/onboarding/step', [
            '_token' => $csrfToken,
            'activation_step' => 'context',
            'job_function' => JobFunction::QSE_LEAD->value,
            'company_size' => CompanySize::P2_10->value,
            'main_activity' => MainActivity::INDUSTRY->value,
        ]);
        $this->assertResponseIsSuccessful();

        $this->client->request('POST', '/preferences/onboarding/step', [
            '_token' => $csrfToken,
            'activation_step' => 'goal',
            'piloting_focus' => PilotingFocus::RISK->value,
            'primary_standard' => PrimaryStandard::ISO_45001->value,
        ]);
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['ok']);
        $this->assertFalse($data['completed']);
        $this->assertSame('guided_action', $data['current_step']);
        $this->assertSame('create_risk', $data['recommended_action']);

        $this->entityManager->clear();
        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $user]);
        self::assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertSame(PilotingFocus::RISK, $prefs->getPilotingFocus());
        $this->assertSame(PrimaryStandard::ISO_45001, $prefs->getPrimaryStandard());
        $this->assertSame('risk', $prefs->getActivationState()['goal'] ?? null);
    }

    public function testActivationGuidedActionStepReturnsRecommendedAction(): void
    {
        $user = $this->createUser('onb-guided-' . uniqid() . '@example.com');
        $this->prepareIncompleteOnboarding($user);
        $csrfToken = $this->fetchOnboardingCsrfToken($user);

        $this->client->request('POST', '/preferences/onboarding/step', [
            '_token' => $csrfToken,
            'activation_step' => 'context',
            'job_function' => JobFunction::QSE_LEAD->value,
            'company_size' => CompanySize::P2_10->value,
            'main_activity' => MainActivity::INDUSTRY->value,
        ]);
        $this->client->request('POST', '/preferences/onboarding/step', [
            '_token' => $csrfToken,
            'activation_step' => 'goal',
            'piloting_focus' => PilotingFocus::AUDIT->value,
        ]);
        $this->assertResponseIsSuccessful();

        $this->client->request('POST', '/preferences/onboarding/step', [
            '_token' => $csrfToken,
            'activation_step' => 'guided_action',
        ]);
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['ok']);
        $this->assertFalse($data['completed']);
        $this->assertSame('guided_action', $data['current_step']);
        $this->assertSame('start_audit', $data['recommended_action']);

        $this->entityManager->clear();
        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $user]);
        self::assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertNotEmpty($prefs->getActivationState()['first_action_started_at'] ?? null);
    }

    public function testSkipOnboardingModalDoesNotCompleteProfile(): void
    {
        $user = $this->createUser('onb-skip-' . uniqid() . '@example.com');
        $this->prepareIncompleteOnboarding($user);
        $csrfToken = $this->fetchOnboardingCsrfToken($user);

        $this->client->request('POST', '/preferences/onboarding/skip', [
            '_token' => $csrfToken,
        ]);
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['ok']);
        $this->assertFalse($data['completed']);

        $this->entityManager->clear();
        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $user]);
        self::assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertFalse($prefs->isProfileOnboardingCompleted());
        $this->assertSame('action_pending', $prefs->getActivationState()['status'] ?? null);
    }

    public function testSkipOnboardingBannerSetsNudgeCooldown(): void
    {
        $user = $this->createUser('onb-skip-banner-' . uniqid() . '@example.com');
        $this->prepareIncompleteOnboarding($user);
        $csrfToken = $this->fetchOnboardingCsrfToken($user);

        $this->client->request('POST', '/preferences/onboarding/skip', [
            '_token' => $csrfToken,
        ]);
        $this->assertResponseIsSuccessful();

        $this->client->request('POST', '/preferences/onboarding/skip', [
            '_token' => $csrfToken,
            'source' => 'banner',
        ]);
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['ok']);
        $this->assertFalse($data['completed']);

        $this->entityManager->clear();
        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $user]);
        self::assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertFalse($prefs->isProfileOnboardingCompleted());
        $this->assertNotEmpty($prefs->getActivationState()['nudge_dismissed_until'] ?? null);
    }

    public function testLegacyOnboardedActivationContextStepDoesNotMutatePreferences(): void
    {
        $user = $this->createUser('onb-legacy-step-' . uniqid() . '@example.com');
        $this->prepareIncompleteOnboarding($user);
        $csrfToken = $this->fetchOnboardingCsrfToken($user);

        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(true);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->client->request('POST', '/preferences/onboarding/step', [
            '_token' => $csrfToken,
            'activation_step' => 'context',
            'job_function' => JobFunction::QSE_LEAD->value,
            'company_size' => CompanySize::P2_10->value,
            'main_activity' => MainActivity::INDUSTRY->value,
        ]);
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['ok']);
        $this->assertTrue($data['completed']);

        $this->entityManager->clear();
        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $user]);
        self::assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertTrue($prefs->isProfileOnboardingCompleted());
        $this->assertNull($prefs->getActivationState());
    }

    public function testLegacyOnboardedUserSkipRemainsCompleted(): void
    {
        $user = $this->createUser('onb-legacy-' . uniqid() . '@example.com');
        $this->prepareIncompleteOnboarding($user);
        $csrfToken = $this->fetchOnboardingCsrfToken($user);

        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(true);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->client->request('POST', '/preferences/onboarding/skip', [
            '_token' => $csrfToken,
        ]);
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['ok']);
        $this->assertTrue($data['completed']);

        $this->entityManager->clear();
        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $user]);
        self::assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertTrue($prefs->isProfileOnboardingCompleted());
        $this->assertNull($prefs->getActivationState());
    }

    public function testSkipOnboardingRejectsInvalidCsrf(): void
    {
        $user = $this->createUser('onb-skip-csrf-' . uniqid() . '@example.com');
        $this->client->loginUser($user);
        $this->client->request('POST', '/preferences/onboarding/skip', [
            '_token' => 'invalid',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    private function prepareIncompleteOnboarding(User $user): void
    {
        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(false);
        $this->entityManager->flush();
    }

    private function fetchOnboardingCsrfToken(User $user): string
    {
        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');
        $csrfCrawler = $this->client->getCrawler()->filter('[data-onboarding-wizard-csrf-value]');
        self::assertGreaterThan(0, $csrfCrawler->count(), 'Jeton CSRF onboarding présent sur le dashboard.');
        $csrfToken = $csrfCrawler->first()->attr('data-onboarding-wizard-csrf-value');
        self::assertNotEmpty($csrfToken);

        return $csrfToken;
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Test123456!'));
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
