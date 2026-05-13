<?php

declare(strict_types=1);

namespace App\Service\Onboarding;

use App\Entity\User;
use App\Entity\UserPreferences;
use App\Repository\UserPreferencesRepository;
use App\UserPreferences\AcquisitionSource;
use App\UserPreferences\CompanySize;
use App\UserPreferences\JobFunction;
use App\UserPreferences\MainActivity;
use App\UserPreferences\OnboardingActivationChoices;
use App\UserPreferences\PilotingFocus;
use App\UserPreferences\PrimaryStandard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class OnboardingProfileWriter
{
    public function __construct(
        private readonly UserPreferencesRepository $userPreferencesRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly OnboardingActivationService $onboardingActivationService,
    ) {
    }

    public function applyStep(User $user, int $step, string $value): UserPreferences
    {
        if ($step < 1 || $step > 6) {
            throw new BadRequestHttpException('Étape invalide.');
        }

        $value = trim($value);
        if ($value === '') {
            throw new BadRequestHttpException('Valeur manquante.');
        }

        $prefs = $this->userPreferencesRepository->getOrCreateForUser($user);
        if ($this->isLegacyOnboarded($prefs)) {
            return $prefs;
        }

        match ($step) {
            1 => $this->applyJobFunction($prefs, $value),
            2 => $this->applyCompanySize($prefs, $value),
            3 => $this->applyMainActivity($prefs, $value),
            4 => $this->applyPrimaryStandard($prefs, $value),
            5 => $this->applyPilotingFocus($prefs, $value),
            6 => $this->applyAcquisitionAndComplete($prefs, $value),
            default => throw new BadRequestHttpException('Étape invalide.'),
        };

        $prefs->touchUpdatedAt();
        $this->entityManager->flush();

        return $prefs;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function applyActivationStep(User $user, string $step, array $payload): UserPreferences
    {
        if (!OnboardingActivationChoices::isValidStep($step)) {
            throw new BadRequestHttpException('Étape d’activation invalide.');
        }

        $prefs = $this->userPreferencesRepository->getOrCreateForUser($user);
        if ($this->isLegacyOnboarded($prefs)) {
            return $prefs;
        }

        $this->onboardingActivationService->start($prefs);

        match ($step) {
            OnboardingActivationChoices::STEP_CONTEXT => $this->applyActivationContext($prefs, $payload),
            OnboardingActivationChoices::STEP_GOAL => $this->applyActivationGoal($prefs, $payload),
            OnboardingActivationChoices::STEP_GUIDED_ACTION => $this->applyActivationGuidedAction($prefs),
            default => throw new BadRequestHttpException('Étape d’activation invalide.'),
        };

        $this->entityManager->flush();

        return $prefs;
    }

    public function skip(User $user, string $source = 'modal'): UserPreferences
    {
        $prefs = $this->userPreferencesRepository->getOrCreateForUser($user);
        if ($this->isLegacyOnboarded($prefs)) {
            return $prefs;
        }

        try {
            if ($prefs->getActivationState() === null) {
                $this->onboardingActivationService->start($prefs);
            }
            $this->onboardingActivationService->skip($prefs, $source);
        } catch (\InvalidArgumentException|\LogicException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        $this->entityManager->flush();

        return $prefs;
    }

    private function isLegacyOnboarded(UserPreferences $prefs): bool
    {
        return $prefs->isProfileOnboardingCompleted() && $prefs->getActivationState() === null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function applyActivationContext(UserPreferences $prefs, array $payload): void
    {
        $jobFunction = $this->parseEnum($payload, 'job_function', JobFunction::class, 'Fonction non reconnue.');
        $companySize = $this->parseEnum($payload, 'company_size', CompanySize::class, 'Taille d’entreprise non reconnue.');
        $mainActivity = $this->parseEnum($payload, 'main_activity', MainActivity::class, 'Secteur non reconnu.');

        $this->onboardingActivationService->applyContext($prefs, $jobFunction, $companySize, $mainActivity);
        $prefs->setSector($mainActivity->label());
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function applyActivationGoal(UserPreferences $prefs, array $payload): void
    {
        $pilotingFocus = $this->parseEnum($payload, 'piloting_focus', PilotingFocus::class, 'Priorité non reconnue.');
        $primaryStandard = $this->parseOptionalEnum($payload, 'primary_standard', PrimaryStandard::class, 'Référentiel non reconnu.');

        $this->onboardingActivationService->applyGoal($prefs, $pilotingFocus, $primaryStandard);
    }

    private function applyActivationGuidedAction(UserPreferences $prefs): void
    {
        $state = $prefs->getActivationState();
        if (!is_array($state) || ($state['current_step'] ?? null) !== OnboardingActivationChoices::STEP_GUIDED_ACTION) {
            throw new BadRequestHttpException('L’étape action guidée n’est pas disponible.');
        }

        $state['first_action_started_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
        $prefs->setActivationState($state);
        $prefs->touchUpdatedAt();
    }

    /**
     * @template T of \BackedEnum
     * @param class-string<T> $enumClass
     * @return T
     */
    private function parseEnum(array $payload, string $field, string $enumClass, string $errorMessage): \BackedEnum
    {
        $value = trim((string) ($payload[$field] ?? ''));
        if ($value === '') {
            throw new BadRequestHttpException('Valeur manquante.');
        }

        $enum = $enumClass::tryFrom($value);
        if ($enum === null) {
            throw new BadRequestHttpException($errorMessage);
        }

        return $enum;
    }

    /**
     * @template T of \BackedEnum
     * @param class-string<T> $enumClass
     * @return T|null
     */
    private function parseOptionalEnum(array $payload, string $field, string $enumClass, string $errorMessage): ?\BackedEnum
    {
        if (!array_key_exists($field, $payload)) {
            return null;
        }

        $value = trim((string) $payload[$field]);
        if ($value === '') {
            return null;
        }

        $enum = $enumClass::tryFrom($value);
        if ($enum === null) {
            throw new BadRequestHttpException($errorMessage);
        }

        return $enum;
    }

    private function applyJobFunction(UserPreferences $prefs, string $value): void
    {
        $enum = JobFunction::tryFrom($value);
        if ($enum === null) {
            throw new BadRequestHttpException('Fonction non reconnue.');
        }
        $prefs->setJobFunction($enum);
    }

    private function applyCompanySize(UserPreferences $prefs, string $value): void
    {
        $enum = CompanySize::tryFrom($value);
        if ($enum === null) {
            throw new BadRequestHttpException('Taille d’entreprise non reconnue.');
        }
        $prefs->setCompanySize($enum);
    }

    private function applyMainActivity(UserPreferences $prefs, string $value): void
    {
        $enum = MainActivity::tryFrom($value);
        if ($enum === null) {
            throw new BadRequestHttpException('Secteur non reconnu.');
        }
        $prefs->setMainActivity($enum);
        $prefs->setSector($enum->label());
    }

    private function applyPrimaryStandard(UserPreferences $prefs, string $value): void
    {
        $enum = PrimaryStandard::tryFrom($value);
        if ($enum === null) {
            throw new BadRequestHttpException('Référentiel non reconnu.');
        }
        $prefs->setPrimaryStandard($enum);
    }

    private function applyPilotingFocus(UserPreferences $prefs, string $value): void
    {
        $enum = PilotingFocus::tryFrom($value);
        if ($enum === null) {
            throw new BadRequestHttpException('Priorité non reconnue.');
        }
        $prefs->setPilotingFocus($enum);
    }

    private function applyAcquisitionAndComplete(UserPreferences $prefs, string $value): void
    {
        $enum = AcquisitionSource::tryFrom($value);
        if ($enum === null) {
            throw new BadRequestHttpException('Source non reconnue.');
        }
        $prefs->setAcquisitionSource($enum);
        $prefs->setProfileOnboardingCompleted(true);
    }
}
