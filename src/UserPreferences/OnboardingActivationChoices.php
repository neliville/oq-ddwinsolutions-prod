<?php

declare(strict_types=1);

namespace App\UserPreferences;

/**
 * Étapes et listes du parcours d’activation onboarding.
 *
 * @phpstan-type option array{value: string, label: string}
 */
final class OnboardingActivationChoices
{
    public const STEP_CONTEXT = 'context';
    public const STEP_GOAL = 'goal';
    public const STEP_GUIDED_ACTION = 'guided_action';

    /**
     * @return list<string>
     */
    public static function stepKeys(): array
    {
        return [
            self::STEP_CONTEXT,
            self::STEP_GOAL,
            self::STEP_GUIDED_ACTION,
        ];
    }

    public static function isValidStep(string $step): bool
    {
        return in_array($step, self::stepKeys(), true);
    }

    /**
     * @return list<array{step: string, title: string, fields: list<string>}>
     */
    public static function steps(): array
    {
        return [
            [
                'step' => self::STEP_CONTEXT,
                'title' => 'Précisez votre contexte QHSE',
                'fields' => ['job_function', 'company_size', 'main_activity'],
            ],
            [
                'step' => self::STEP_GOAL,
                'title' => 'Choisissez votre priorité de pilotage',
                'fields' => ['piloting_focus', 'primary_standard'],
            ],
            [
                'step' => self::STEP_GUIDED_ACTION,
                'title' => 'Lancez votre première action utile',
                'fields' => [],
            ],
        ];
    }

    /**
     * @return array{job_function: list<option>, company_size: list<option>, main_activity: list<option>}
     */
    public static function contextOptions(): array
    {
        return [
            'job_function' => self::mapEnum(JobFunction::cases()),
            'company_size' => self::mapCompanySizesForWizard(),
            'main_activity' => self::mapEnum(MainActivity::cases()),
        ];
    }

    /**
     * @return array{piloting_focus: list<option>, primary_standard: list<option>}
     */
    public static function goalOptions(): array
    {
        return [
            'piloting_focus' => self::mapPilotingForWizard(),
            'primary_standard' => self::mapEnum(PrimaryStandard::cases()),
        ];
    }

    /**
     * @param list<\BackedEnum&object{label(): string}> $cases
     * @return list<option>
     */
    private static function mapEnum(array $cases): array
    {
        $out = [];
        foreach ($cases as $case) {
            if (!$case instanceof \BackedEnum) {
                continue;
            }
            /** @var object{label(): string} $labeled */
            $labeled = $case;
            $out[] = ['value' => $case->value, 'label' => $labeled->label()];
        }

        return $out;
    }

    /**
     * @return list<option>
     */
    private static function mapCompanySizesForWizard(): array
    {
        $allowed = [
            CompanySize::SOLO,
            CompanySize::P2_10,
            CompanySize::P11_50,
            CompanySize::P51_250,
            CompanySize::P251_1000,
            CompanySize::P1000_PLUS,
        ];
        $out = [];
        foreach ($allowed as $case) {
            $out[] = ['value' => $case->value, 'label' => $case->label()];
        }

        return $out;
    }

    /**
     * @return list<option>
     */
    private static function mapPilotingForWizard(): array
    {
        $allowed = [
            PilotingFocus::AUDIT,
            PilotingFocus::CAPA,
            PilotingFocus::RISK,
            PilotingFocus::COMPLIANCE,
            PilotingFocus::CERTIFICATION_PREP,
            PilotingFocus::GLOBAL_PILOTING,
            PilotingFocus::CONTINUOUS_IMPROVEMENT,
        ];
        $out = [];
        foreach ($allowed as $case) {
            $out[] = ['value' => $case->value, 'label' => $case->label()];
        }

        return $out;
    }
}
