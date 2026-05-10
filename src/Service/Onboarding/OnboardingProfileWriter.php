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
use App\UserPreferences\PilotingFocus;
use App\UserPreferences\PrimaryStandard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class OnboardingProfileWriter
{
    public function __construct(
        private readonly UserPreferencesRepository $userPreferencesRepository,
        private readonly EntityManagerInterface $entityManager,
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
     * Ferme l’assistant d’onboarding sans renseigner les étapes : les valeurs par défaut / existantes restent en place.
     * L’utilisateur peut compléter plus tard via Préférences (Profil, QHSE).
     */
    public function skipForLater(User $user): UserPreferences
    {
        $prefs = $this->userPreferencesRepository->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(true);
        $prefs->touchUpdatedAt();
        $this->entityManager->flush();

        return $prefs;
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
