<?php

declare(strict_types=1);

namespace App\Service;

use App\Dashboard\DashboardLayout;
use App\Dashboard\DashboardWidgetId;
use App\Entity\UserPreferences;
use Symfony\Component\Form\FormInterface;

/**
 * Lecture / écriture du layout dashboard (widgets cockpit QSE).
 */
final class DashboardPreferencesService
{
    public function getDashboardLayout(UserPreferences $preferences): DashboardLayout
    {
        return DashboardLayout::fromStored($preferences->getDashboardLayout());
    }

    public function applyDashboardLayout(UserPreferences $preferences, DashboardLayout $layout): void
    {
        $preferences->setDashboardLayout($layout->toStorage());
    }

    /**
     * @return list<string>
     */
    public function getOrderedVisibleWidgets(UserPreferences $preferences): array
    {
        return $this->getDashboardLayout($preferences)->getOrderedVisibleWidgetIds();
    }

    /**
     * Groupe les widgets visibles par zone de rendu (structure HTML du dashboard).
     *
     * @return array{
     *     before_grid: list<string>,
     *     grid: list<string>,
     *     after_grid_attention: list<string>,
     *     stats_section: list<string>
     * }
     */
    public function getVisibleWidgetsByPlacementZone(UserPreferences $preferences): array
    {
        return $this->getDashboardLayout($preferences)->getVisibleWidgetsByPlacementZone();
    }

    public function isSectionVisible(UserPreferences $preferences, string $key): bool
    {
        return $this->getDashboardLayout($preferences)->isWidgetVisible($key);
    }

    /**
     * @param FormInterface<mixed> $form
     */
    public function applyVisibilityFromSubmittedForm(UserPreferences $preferences, FormInterface $form): void
    {
        $visibility = [];
        foreach (UserPreferences::dashboardWidgetKeys() as $key) {
            $field = 'dash_'.$key;
            $control = $form->get($field);
            $visibility[$key] = $control->isSubmitted() && (bool) $control->getData();
        }

        $this->applyDashboardLayout($preferences, DashboardLayout::fromVisibilityMap($visibility));
    }

    /**
     * Compatibilité tests / code legacy utilisant un map booléen.
     *
     * @param array<string, bool> $visibility
     */
    public function applyLegacyVisibilityMap(UserPreferences $preferences, array $visibility): void
    {
        $this->applyDashboardLayout($preferences, DashboardLayout::fromLegacyVisibilityMap($visibility));
    }

    /**
     * @deprecated Utiliser {@see applyVisibilityFromSubmittedForm()} ou {@see applyDashboardLayout()}
     *
     * @param array<string, bool> $visibility
     */
    public function applyVisibility(UserPreferences $preferences, array $visibility): void
    {
        $this->applyLegacyVisibilityMap($preferences, $visibility);
    }

    /**
     * @deprecated Utiliser {@see isSectionVisible()}
     */
    public function isDashboardSectionVisible(UserPreferences $preferences, string $key): bool
    {
        return $this->isSectionVisible($preferences, $key);
    }

    /**
     * @deprecated Utiliser {@see applyVisibilityFromSubmittedForm()}
     *
     * @param FormInterface<mixed> $form
     *
     * @return array<string, bool>
     */
    public function resolveVisibilityFromSubmittedForm(FormInterface $form): array
    {
        $visibility = [];
        foreach (UserPreferences::dashboardWidgetKeys() as $key) {
            $field = 'dash_'.$key;
            $control = $form->get($field);
            $visibility[$key] = $control->isSubmitted() && (bool) $control->getData();
        }

        return $visibility;
    }
}
