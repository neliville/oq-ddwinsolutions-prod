<?php

declare(strict_types=1);

namespace App\UserPreferences;

/**
 * Listes du wizard d’onboarding (mêmes valeurs que les enums persistés).
 *
 * @phpstan-type option array{value: string, label: string}
 */
final class OnboardingWizardChoices
{
    /**
     * @return list<array{step: int, title: string, options: list<option>}>
     */
    public static function steps(): array
    {
        return [
            [
                'step' => 1,
                'title' => 'Quelle est votre fonction ?',
                'options' => self::mapEnum(JobFunction::cases()),
            ],
            [
                'step' => 2,
                'title' => 'Quelle est la taille de votre entreprise ?',
                'options' => self::mapCompanySizesForWizard(),
            ],
            [
                'step' => 3,
                'title' => 'Quel est votre secteur d’activité ?',
                'options' => self::mapEnum(MainActivity::cases()),
            ],
            [
                'step' => 4,
                'title' => 'Quel est votre référentiel principal ?',
                'options' => self::mapEnum(PrimaryStandard::cases()),
            ],
            [
                'step' => 5,
                'title' => 'Quelle est votre priorité actuelle ?',
                'options' => self::mapPilotingForWizard(),
            ],
            [
                'step' => 6,
                'title' => 'Comment avez-vous connu Outils-Qualite.com ?',
                'options' => self::mapEnum(AcquisitionSource::cases()),
            ],
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
