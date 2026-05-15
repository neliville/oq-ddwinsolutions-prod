<?php

declare(strict_types=1);

namespace App\Service\Export;

use App\Entity\User;
use App\Entity\UserPreferences;
use App\Export\Dto\ExportBrandingView;
use App\Export\Dto\ExportUserBranding;
use App\Repository\UserPreferencesRepository;

/**
 * Fusionne le branding système OUTILS-QUALITÉ avec les préférences d’export utilisateur.
 */
final class ExportBrandingResolver
{
    public function __construct(
        private readonly UserPreferencesRepository $userPreferencesRepository,
    ) {
    }

    public function resolveForUser(?User $user): ExportBrandingView
    {
        if (!$user instanceof User) {
            return new ExportBrandingView(profileDisplayName: null);
        }

        $prefs = $this->userPreferencesRepository->getOrCreateForUser($user);
        $userBranding = $this->buildUserBranding($prefs);

        return new ExportBrandingView(
            user: $userBranding->isEmpty() ? null : $userBranding,
            profileDisplayName: $prefs->getProfileDisplayName(),
        );
    }

    private function buildUserBranding(UserPreferences $prefs): ExportUserBranding
    {
        return new ExportUserBranding(
            displayName: self::normalize($prefs->getExportDisplayName()),
            jobTitle: self::normalize($prefs->getExportJobTitle()),
            companyName: self::normalize($prefs->getExportCompanyName()),
            pdfFooter: self::normalize($prefs->getExportPdfFooter()),
            logoFilename: self::normalize($prefs->getExportLogoFilename()),
        );
    }

    private static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
