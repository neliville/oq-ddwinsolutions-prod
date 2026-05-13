<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Onboarding;

use App\Entity\UserPreferences;
use App\Service\Onboarding\OnboardingActivationService;
use App\UserPreferences\CompanySize;
use App\UserPreferences\JobFunction;
use App\UserPreferences\MainActivity;
use App\UserPreferences\PilotingFocus;
use App\UserPreferences\PrimaryStandard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OnboardingActivationServiceTest extends TestCase
{
    private OnboardingActivationService $service;

    protected function setUp(): void
    {
        $this->service = new OnboardingActivationService();
    }

    public function testLegacyOnboardedUserNeverShowsActivationUi(): void
    {
        $prefs = $this->makePreferences(profileCompleted: true, activationState: null);

        $this->assertFalse($this->service->shouldShowModal($prefs, false));
        $this->assertFalse($this->service->shouldShowNudgeBanner($prefs, false));
        $this->assertFalse($this->service->shouldShowAhaBanner($prefs, 'audit_created'));
    }

    public function testAdminNeverShowsActivationUi(): void
    {
        $prefs = $this->makePreferences(profileCompleted: false, activationState: null);

        $this->assertFalse($this->service->shouldShowModal($prefs, true));
        $this->assertFalse($this->service->shouldShowNudgeBanner($prefs, true));
        $this->assertFalse($this->service->shouldShowAhaBanner($prefs, 'audit_created', isAdmin: true));
    }

    public function testLegacyPartialUserCanStartActivationFlow(): void
    {
        $prefs = $this->makePreferences(profileCompleted: false, activationState: null);
        $now = new \DateTimeImmutable('2026-05-13T10:00:00+00:00');

        $this->service->start($prefs, $now);

        $state = $prefs->getActivationState();
        $this->assertSame(1, $state['version']);
        $this->assertSame('in_progress', $state['status']);
        $this->assertSame('context', $state['current_step']);
        $this->assertSame($now->format(\DateTimeInterface::ATOM), $state['started_at']);
        $this->assertFalse($prefs->isProfileOnboardingCompleted());
    }

    public function testStartInfersGoalStepWhenContextAlreadyStored(): void
    {
        $prefs = $this->makePreferences(profileCompleted: false, activationState: null);
        $prefs->setJobFunction(JobFunction::QSE_LEAD);
        $prefs->setCompanySize(CompanySize::P2_10);
        $prefs->setMainActivity(MainActivity::INDUSTRY);

        $this->service->start($prefs);

        $this->assertSame('goal', $prefs->getActivationState()['current_step']);
    }

    public function testStartInfersGuidedActionWhenContextAndGoalAlreadyStored(): void
    {
        $prefs = $this->makePreferences(profileCompleted: false, activationState: null);
        $prefs->setJobFunction(JobFunction::QSE_LEAD);
        $prefs->setCompanySize(CompanySize::P2_10);
        $prefs->setMainActivity(MainActivity::INDUSTRY);
        $prefs->setPilotingFocus(PilotingFocus::AUDIT);

        $this->service->start($prefs);

        $this->assertSame('guided_action', $prefs->getActivationState()['current_step']);
        $this->assertSame('audit', $prefs->getActivationState()['goal']);
        $this->assertSame('start_audit', $prefs->getActivationState()['recommended_action']);
    }

    public function testStartThrowsForLegacyOnboardedUser(): void
    {
        $prefs = $this->makePreferences(profileCompleted: true, activationState: null);

        $this->expectException(\LogicException::class);
        $this->service->start($prefs);
    }

    public function testApplyContextPersistsProfileFieldsAndAdvancesStep(): void
    {
        $prefs = $this->makePreferences(profileCompleted: false, activationState: null);
        $this->service->start($prefs);

        $this->service->applyContext(
            $prefs,
            JobFunction::QSE_LEAD,
            CompanySize::P2_10,
            MainActivity::INDUSTRY,
        );

        $this->assertSame(JobFunction::QSE_LEAD, $prefs->getJobFunction());
        $this->assertSame(CompanySize::P2_10, $prefs->getCompanySize());
        $this->assertSame(MainActivity::INDUSTRY, $prefs->getMainActivity());
        $this->assertSame('goal', $prefs->getActivationState()['current_step']);
        $this->assertSame('in_progress', $prefs->getActivationState()['status']);
    }

    public function testApplyGoalPersistsFocusAndRecommendedAction(): void
    {
        $prefs = $this->makePreferences(profileCompleted: false, activationState: null);
        $this->service->start($prefs);
        $this->service->applyContext(
            $prefs,
            JobFunction::QSE_LEAD,
            CompanySize::P2_10,
            MainActivity::INDUSTRY,
        );

        $this->service->applyGoal($prefs, PilotingFocus::RISK, PrimaryStandard::ISO_45001);

        $this->assertSame(PilotingFocus::RISK, $prefs->getPilotingFocus());
        $this->assertSame(PrimaryStandard::ISO_45001, $prefs->getPrimaryStandard());
        $state = $prefs->getActivationState();
        $this->assertSame('guided_action', $state['current_step']);
        $this->assertSame('risk', $state['goal']);
        $this->assertSame('create_risk', $state['recommended_action']);
    }

    public function testSkipFromModalMovesToActionPendingWithoutCompletingProfile(): void
    {
        $prefs = $this->makePreferences(profileCompleted: false, activationState: null);
        $this->service->start($prefs);
        $now = new \DateTimeImmutable('2026-05-13T11:00:00+00:00');

        $this->service->skip($prefs, 'modal', $now);

        $state = $prefs->getActivationState();
        $this->assertSame('action_pending', $state['status']);
        $this->assertSame('guided_action', $state['current_step']);
        $this->assertSame($now->format(\DateTimeInterface::ATOM), $state['skipped_at']);
        $this->assertFalse($prefs->isProfileOnboardingCompleted());
        $this->assertFalse($this->service->shouldShowModal($prefs, false));
        $this->assertTrue($this->service->shouldShowNudgeBanner($prefs, false, $now));
    }

    public function testSkipFromBannerSetsCooldownWithoutCompletingProfile(): void
    {
        $prefs = $this->makePreferences(profileCompleted: false, activationState: [
            'version' => 1,
            'status' => 'action_pending',
            'current_step' => 'guided_action',
            'goal' => 'audit',
            'recommended_action' => 'start_audit',
        ]);
        $now = new \DateTimeImmutable('2026-05-13T12:00:00+00:00');

        $this->service->skip($prefs, 'banner', $now);

        $state = $prefs->getActivationState();
        $this->assertSame('action_pending', $state['status']);
        $this->assertSame(
            $now->add(new \DateInterval('P7D'))->format(\DateTimeInterface::ATOM),
            $state['nudge_dismissed_until'],
        );
        $this->assertFalse($prefs->isProfileOnboardingCompleted());
        $this->assertFalse($this->service->shouldShowNudgeBanner($prefs, false, $now));
    }

    public function testMarkFirstActionCompletedRecordsTimestampWithoutCompletingProfile(): void
    {
        $prefs = $this->makePreferences(profileCompleted: false, activationState: [
            'version' => 1,
            'status' => 'in_progress',
            'current_step' => 'guided_action',
            'goal' => 'audit',
            'recommended_action' => 'start_audit',
        ]);
        $now = new \DateTimeImmutable('2026-05-13T13:00:00+00:00');

        $this->service->markFirstActionCompleted($prefs, $now);

        $state = $prefs->getActivationState();
        $this->assertSame($now->format(\DateTimeInterface::ATOM), $state['first_action_completed_at']);
        $this->assertSame('aha', $state['current_step']);
        $this->assertFalse($prefs->isProfileOnboardingCompleted());
    }

    public function testMarkAhaSeenCompletesActivationAndProfile(): void
    {
        $prefs = $this->makePreferences(profileCompleted: false, activationState: [
            'version' => 1,
            'status' => 'in_progress',
            'current_step' => 'aha',
            'goal' => 'audit',
            'recommended_action' => 'start_audit',
            'first_action_completed_at' => '2026-05-13T13:00:00+00:00',
        ]);
        $now = new \DateTimeImmutable('2026-05-13T14:00:00+00:00');

        $this->service->markAhaSeen($prefs, $now);

        $state = $prefs->getActivationState();
        $this->assertSame($now->format(\DateTimeInterface::ATOM), $state['aha_seen_at']);
        $this->assertSame('completed', $state['status']);
        $this->assertTrue($prefs->isProfileOnboardingCompleted());
        $this->assertFalse($this->service->shouldShowModal($prefs, false));
        $this->assertFalse($this->service->shouldShowNudgeBanner($prefs, false, $now));
    }

    public function testMarkAhaSeenRequiresFirstActionCompleted(): void
    {
        $prefs = $this->makePreferences(profileCompleted: false, activationState: [
            'version' => 1,
            'status' => 'in_progress',
            'current_step' => 'guided_action',
        ]);

        $this->expectException(\LogicException::class);
        $this->service->markAhaSeen($prefs);
    }

    #[DataProvider('provideActivationNoticeValues')]
    public function testShouldShowAhaBannerForActivationNoticeOrPendingAha(?string $activationNotice, ?array $activationState, bool $expected): void
    {
        $prefs = $this->makePreferences(profileCompleted: false, activationState: $activationState);

        $this->assertSame($expected, $this->service->shouldShowAhaBanner($prefs, $activationNotice));
    }

    /**
     * @return iterable<string, array{0: ?string, 1: ?array<string, mixed>, 2: bool}>
     */
    public static function provideActivationNoticeValues(): iterable
    {
        yield 'audit created notice' => ['audit_created', null, true];
        yield 'risk created notice' => ['risk_created', null, true];
        yield 'capa created notice' => ['capa_created', null, true];
        yield 'pending aha state' => [null, [
            'version' => 1,
            'status' => 'in_progress',
            'first_action_completed_at' => '2026-05-13T13:00:00+00:00',
        ], true];
        yield 'aha already seen' => [null, [
            'version' => 1,
            'status' => 'completed',
            'first_action_completed_at' => '2026-05-13T13:00:00+00:00',
            'aha_seen_at' => '2026-05-13T14:00:00+00:00',
        ], false];
    }

    #[DataProvider('provideRecommendedActionMappings')]
    public function testResolveRecommendedActionFromGoal(string $goal, string $expected): void
    {
        $prefs = $this->makePreferences(profileCompleted: false, activationState: [
            'version' => 1,
            'status' => 'in_progress',
            'goal' => $goal,
        ]);

        $this->assertSame($expected, $this->service->resolveRecommendedAction($prefs));
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function provideRecommendedActionMappings(): iterable
    {
        yield 'audit' => ['audit', 'start_audit'];
        yield 'compliance' => ['compliance', 'start_audit'];
        yield 'certification prep' => ['certification_prep', 'start_audit'];
        yield 'capa' => ['capa', 'create_capa_draft'];
        yield 'risk' => ['risk', 'create_risk'];
        yield 'global piloting' => ['global_piloting', 'open_cockpit'];
    }

    public function testMutatingCompletedActivationThrows(): void
    {
        $prefs = $this->makePreferences(profileCompleted: true, activationState: [
            'version' => 1,
            'status' => 'completed',
        ]);

        $this->expectException(\LogicException::class);
        $this->service->applyContext(
            $prefs,
            JobFunction::QSE_LEAD,
            CompanySize::P2_10,
            MainActivity::INDUSTRY,
        );
    }

    /**
     * @param array<string, mixed>|null $activationState
     */
    private function makePreferences(bool $profileCompleted, ?array $activationState): UserPreferences
    {
        $prefs = new UserPreferences();
        $prefs->setProfileOnboardingCompleted($profileCompleted);
        $prefs->setActivationState($activationState);

        return $prefs;
    }
}
