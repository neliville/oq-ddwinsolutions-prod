<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\UserPreferences;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UserPreferencesActivationStateTest extends TestCase
{
    public function testActivationStateIsNullByDefault(): void
    {
        $prefs = new UserPreferences();

        $this->assertNull($prefs->getActivationState());
        $this->assertFalse($prefs->isActivationCompleted());
        $this->assertFalse($prefs->hasActivationPendingAction());
    }

    public function testActivationStateCanBeSetAndRead(): void
    {
        $prefs = new UserPreferences();
        $state = [
            'version' => 1,
            'status' => 'in_progress',
            'current_step' => 'context',
            'goal' => null,
            'recommended_action' => null,
            'started_at' => '2026-05-13T10:00:00+00:00',
        ];

        $prefs->setActivationState($state);

        $this->assertSame($state, $prefs->getActivationState());
    }

    public function testActivationStateCanBeCleared(): void
    {
        $prefs = new UserPreferences();
        $prefs->setActivationState(['version' => 1, 'status' => 'in_progress']);

        $prefs->setActivationState(null);

        $this->assertNull($prefs->getActivationState());
    }

    public function testIsActivationCompletedIsTrueOnlyForCompletedStatus(): void
    {
        $prefs = new UserPreferences();
        $prefs->setActivationState(['version' => 1, 'status' => 'completed']);

        $this->assertTrue($prefs->isActivationCompleted());
    }

    #[DataProvider('provideNonCompletedActivationStatuses')]
    public function testIsActivationCompletedIsFalseForOtherStatuses(?array $state): void
    {
        $prefs = new UserPreferences();
        $prefs->setActivationState($state);

        $this->assertFalse($prefs->isActivationCompleted());
    }

    /**
     * @return iterable<string, array{0: ?array<string, mixed>}>
     */
    public static function provideNonCompletedActivationStatuses(): iterable
    {
        yield 'null state' => [null];
        yield 'in progress' => [['version' => 1, 'status' => 'in_progress']];
        yield 'action pending' => [['version' => 1, 'status' => 'action_pending']];
        yield 'missing status' => [['version' => 1]];
    }

    public function testHasActivationPendingActionIsTrueOnlyForActionPendingStatus(): void
    {
        $prefs = new UserPreferences();
        $prefs->setActivationState(['version' => 1, 'status' => 'action_pending']);

        $this->assertTrue($prefs->hasActivationPendingAction());
    }

    #[DataProvider('provideNonPendingActivationStatuses')]
    public function testHasActivationPendingActionIsFalseForOtherStatuses(?array $state): void
    {
        $prefs = new UserPreferences();
        $prefs->setActivationState($state);

        $this->assertFalse($prefs->hasActivationPendingAction());
    }

    /**
     * @return iterable<string, array{0: ?array<string, mixed>}>
     */
    public static function provideNonPendingActivationStatuses(): iterable
    {
        yield 'null state' => [null];
        yield 'in progress' => [['version' => 1, 'status' => 'in_progress']];
        yield 'completed' => [['version' => 1, 'status' => 'completed']];
        yield 'missing status' => [['version' => 1]];
    }

    public function testSettingActivationStateDoesNotChangeProfileOnboardingCompleted(): void
    {
        $prefs = new UserPreferences();
        $prefs->setProfileOnboardingCompleted(true);

        $prefs->setActivationState(['version' => 1, 'status' => 'in_progress']);

        $this->assertTrue($prefs->isProfileOnboardingCompleted());
    }

    public function testTouchUpdatedAtStillUpdatesTimestamp(): void
    {
        $prefs = new UserPreferences();
        $before = new \DateTimeImmutable('2020-01-01 00:00:00');
        $prefs->setUpdatedAt($before);

        $prefs->touchUpdatedAt();

        $this->assertGreaterThan($before, $prefs->getUpdatedAt());
    }
}
