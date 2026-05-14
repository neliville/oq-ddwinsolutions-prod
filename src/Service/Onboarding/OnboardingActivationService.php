<?php

declare(strict_types=1);

namespace App\Service\Onboarding;

use App\Entity\UserPreferences;
use App\UserPreferences\CompanySize;
use App\UserPreferences\JobFunction;
use App\UserPreferences\MainActivity;
use App\UserPreferences\PilotingFocus;
use App\UserPreferences\PrimaryStandard;

final class OnboardingActivationService
{
    private const VERSION = 1;
    private const NUDGE_COOLDOWN = 'P7D';

  /** @var list<string> */
    private const AHA_NOTICES = ['audit_created', 'risk_created', 'capa_created', 'ishikawa_created'];

    public function start(UserPreferences $preferences, ?\DateTimeImmutable $now = null): void
    {
        $this->assertNotLegacyOnboarded($preferences);
        $this->assertActivationNotCompleted($preferences);

        $state = $preferences->getActivationState();
        if ($state !== null && ($state['status'] ?? null) === 'in_progress') {
            return;
        }

        $now ??= new \DateTimeImmutable();
        $goal = $this->inferGoalFromPreferences($preferences);
        $recommendedAction = $goal !== null ? $this->recommendedActionForGoal($goal) : null;

        $this->writeState($preferences, [
            'version' => self::VERSION,
            'status' => 'in_progress',
            'current_step' => $this->inferInitialStep($preferences),
            'goal' => $goal,
            'recommended_action' => $recommendedAction,
            'started_at' => $now->format(\DateTimeInterface::ATOM),
            'skipped_at' => null,
            'first_action_started_at' => null,
            'first_action_completed_at' => null,
            'aha_seen_at' => null,
            'last_nudge_at' => null,
            'nudge_dismissed_until' => null,
        ]);
    }

    public function applyContext(
        UserPreferences $preferences,
        JobFunction $jobFunction,
        CompanySize $companySize,
        MainActivity $mainActivity,
        ?\DateTimeImmutable $now = null,
    ): void {
        $this->assertActivationMutable($preferences);

        $preferences->setJobFunction($jobFunction);
        $preferences->setCompanySize($companySize);
        $preferences->setMainActivity($mainActivity);

        $this->writeState($preferences, [
            'status' => 'in_progress',
            'current_step' => 'goal',
        ]);
    }

    public function applyGoal(
        UserPreferences $preferences,
        PilotingFocus $pilotingFocus,
        ?PrimaryStandard $primaryStandard = null,
        ?\DateTimeImmutable $now = null,
    ): void {
        $this->assertActivationMutable($preferences);

        $preferences->setPilotingFocus($pilotingFocus);
        if ($primaryStandard !== null) {
            $preferences->setPrimaryStandard($primaryStandard);
        }

        $goal = $this->goalForPilotingFocus($pilotingFocus);

        $this->writeState($preferences, [
            'status' => 'in_progress',
            'current_step' => 'guided_action',
            'goal' => $goal,
            'recommended_action' => $this->recommendedActionForGoal($goal),
        ]);
    }

    public function skip(UserPreferences $preferences, string $source = 'modal', ?\DateTimeImmutable $now = null): void
    {
        $now ??= new \DateTimeImmutable();

        if ($source === 'banner') {
            $this->assertActivationMutable($preferences);
            if (!$preferences->hasActivationPendingAction()) {
                throw new \LogicException('La relance bannière ne s’applique qu’à un parcours en attente d’action.');
            }

            $this->writeState($preferences, [
                'nudge_dismissed_until' => $now->add(new \DateInterval(self::NUDGE_COOLDOWN))->format(\DateTimeInterface::ATOM),
            ]);

            return;
        }

        if ($source !== 'modal') {
            throw new \InvalidArgumentException('Source de skip onboarding non reconnue.');
        }

        $this->assertActivationMutable($preferences);

        $this->writeState($preferences, [
            'status' => 'action_pending',
            'current_step' => 'guided_action',
            'skipped_at' => $now->format(\DateTimeInterface::ATOM),
        ]);
    }

    public function markFirstActionCompleted(UserPreferences $preferences, ?\DateTimeImmutable $now = null): void
    {
        $this->assertActivationMutable($preferences);

        $now ??= new \DateTimeImmutable();

        $this->writeState($preferences, [
            'first_action_completed_at' => $now->format(\DateTimeInterface::ATOM),
            'current_step' => 'aha',
        ]);
    }

    public function markAhaSeen(UserPreferences $preferences, ?\DateTimeImmutable $now = null): void
    {
        $this->assertActivationMutable($preferences);

        $state = $preferences->getActivationState();
        if (!is_array($state) || ($state['first_action_completed_at'] ?? null) === null) {
            throw new \LogicException('Le moment aha ne peut être validé sans première action complétée.');
        }

        $now ??= new \DateTimeImmutable();

        $this->writeState($preferences, [
            'aha_seen_at' => $now->format(\DateTimeInterface::ATOM),
            'status' => 'completed',
        ]);
        $preferences->setProfileOnboardingCompleted(true);
    }

    public function shouldShowModal(UserPreferences $preferences, bool $isAdmin): bool
    {
        if ($isAdmin || $this->isLegacyOnboarded($preferences) || $preferences->isActivationCompleted()) {
            return false;
        }

        $state = $preferences->getActivationState();
        if ($state === null) {
            return !$preferences->isProfileOnboardingCompleted();
        }

        return ($state['status'] ?? null) === 'in_progress';
    }

    public function shouldShowNudgeBanner(UserPreferences $preferences, bool $isAdmin, ?\DateTimeImmutable $now = null): bool
    {
        if ($isAdmin || $this->isLegacyOnboarded($preferences) || $preferences->isActivationCompleted()) {
            return false;
        }

        if (!$preferences->hasActivationPendingAction()) {
            return false;
        }

        $state = $preferences->getActivationState();
        if (!is_array($state) || ($state['first_action_completed_at'] ?? null) !== null) {
            return false;
        }

        $dismissedUntil = $state['nudge_dismissed_until'] ?? null;
        if (!is_string($dismissedUntil) || $dismissedUntil === '') {
            return true;
        }

        $now ??= new \DateTimeImmutable();

        return $now >= new \DateTimeImmutable($dismissedUntil);
    }

    public function shouldShowAhaBanner(
        UserPreferences $preferences,
        ?string $activationNotice = null,
        bool $isAdmin = false,
        ?\DateTimeImmutable $now = null,
    ): bool {
        if ($isAdmin || $this->isLegacyOnboarded($preferences) || $preferences->isActivationCompleted()) {
            return false;
        }

        if ($activationNotice !== null && in_array($activationNotice, self::AHA_NOTICES, true)) {
            return true;
        }

        $state = $preferences->getActivationState();
        if (!is_array($state)) {
            return false;
        }

        if (($state['first_action_completed_at'] ?? null) === null) {
            return false;
        }

        return ($state['aha_seen_at'] ?? null) === null;
    }

    public function resolveRecommendedAction(UserPreferences $preferences): ?string
    {
        $state = $preferences->getActivationState();
        if (!is_array($state)) {
            return null;
        }

        $goal = $state['goal'] ?? null;
        if (is_string($goal) && $goal !== '') {
            return $this->recommendedActionForGoal($goal);
        }

        $recommendedAction = $state['recommended_action'] ?? null;

        return is_string($recommendedAction) && $recommendedAction !== '' ? $recommendedAction : null;
    }

    private function isLegacyOnboarded(UserPreferences $preferences): bool
    {
        return $preferences->isProfileOnboardingCompleted() && $preferences->getActivationState() === null;
    }

    private function assertNotLegacyOnboarded(UserPreferences $preferences): void
    {
        if ($this->isLegacyOnboarded($preferences)) {
            throw new \LogicException('Le parcours d’activation ne s’applique pas à un utilisateur déjà onboardé en legacy.');
        }
    }

    private function assertActivationNotCompleted(UserPreferences $preferences): void
    {
        if ($preferences->isActivationCompleted()) {
            throw new \LogicException('Le parcours d’activation est déjà terminé.');
        }
    }

    private function assertActivationMutable(UserPreferences $preferences): void
    {
        $this->assertNotLegacyOnboarded($preferences);
        $this->assertActivationNotCompleted($preferences);
    }

  /**
   * @param array<string, mixed> $patch
   */
    private function writeState(UserPreferences $preferences, array $patch): void
    {
        $current = $preferences->getActivationState() ?? ['version' => self::VERSION];
        $preferences->setActivationState(array_replace($current, $patch));
        $preferences->touchUpdatedAt();
    }

    private function inferInitialStep(UserPreferences $preferences): string
    {
        if (!$this->hasContext($preferences)) {
            return 'context';
        }

        if ($this->inferGoalFromPreferences($preferences) === null) {
            return 'goal';
        }

        return 'guided_action';
    }

    private function hasContext(UserPreferences $preferences): bool
    {
        return $preferences->getJobFunction() !== null
            && $preferences->getCompanySize() !== null
            && $preferences->getMainActivity() !== null;
    }

    private function inferGoalFromPreferences(UserPreferences $preferences): ?string
    {
        if (!$this->hasContext($preferences)) {
            return null;
        }

        $pilotingFocus = $preferences->getPilotingFocus();
        if ($pilotingFocus === PilotingFocus::PDCA) {
            return null;
        }

        return $this->goalForPilotingFocus($pilotingFocus);
    }

    private function goalForPilotingFocus(PilotingFocus $pilotingFocus): string
    {
        return match ($pilotingFocus) {
            PilotingFocus::AUDIT => 'audit',
            PilotingFocus::CAPA => 'capa',
            PilotingFocus::RISK => 'risk',
            PilotingFocus::COMPLIANCE => 'compliance',
            PilotingFocus::CERTIFICATION_PREP => 'certification_prep',
            PilotingFocus::GLOBAL_PILOTING => 'global_piloting',
            PilotingFocus::CONTINUOUS_IMPROVEMENT => 'capa',
            PilotingFocus::PDCA => 'global_piloting',
        };
    }

    private function recommendedActionForGoal(string $goal): string
    {
        return match ($goal) {
            'audit', 'compliance', 'certification_prep' => 'start_audit',
            'capa' => 'create_capa_draft',
            'risk' => 'create_risk',
            'global_piloting' => 'open_cockpit',
            default => throw new \InvalidArgumentException(sprintf('Objectif d’activation non reconnu : %s.', $goal)),
        };
    }
}
