<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\UserPreferences;
use Symfony\Component\Form\FormInterface;

/**
 * Centralise la lecture/écriture des préférences d'affichage du tableau de bord.
 */
final class DashboardPreferencesService
{
    public function isSectionVisible(UserPreferences $preferences, string $key): bool
    {
        return $preferences->isDashboardSectionVisible($key);
    }

    /**
     * Extrait la visibilité depuis un formulaire dashboard soumis.
     *
     * Les cases non cochées ne sont pas envoyées en POST : pour les champs non mappés,
     * {@see FormInterface::getData()} conserve la valeur initiale — il faut s'appuyer sur
     * {@see FormInterface::isSubmitted()} pour chaque case.
     *
     * @return array<string, bool>
     */
    public function resolveVisibilityFromSubmittedForm(FormInterface $form): array
    {
        $visibility = [];

        foreach (UserPreferences::dashboardSectionKeys() as $key) {
            $field = 'dash_'.$key;
            $control = $form->get($field);
            $visibility[$key] = $control->isSubmitted() && (bool) $control->getData();
        }

        return $visibility;
    }

    /**
     * @param array<string, bool> $visibility
     */
    public function applyVisibility(UserPreferences $preferences, array $visibility): void
    {
        $normalized = [];
        foreach (UserPreferences::dashboardSectionKeys() as $key) {
            $normalized[$key] = (bool) ($visibility[$key] ?? false);
        }

        $preferences->setDashboardVisibility($normalized);
    }
}
