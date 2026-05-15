<?php

declare(strict_types=1);

namespace App\Form\Preference;

use App\Dashboard\DashboardLayout;
use App\Entity\UserPreferences;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire sans data_class : hydrate le layout dashboard via {@see DashboardPreferencesService} dans le contrôleur.
 */
final class UserDashboardPreferencesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var UserPreferences $prefs */
        $prefs = $options['preferences'];

        $labels = [
            'deadlines' => 'Urgence et délais (bloc 1)',
            'capa' => 'CAPA — vue synthétique',
            'risks' => 'Risques',
            'audits' => 'Audits',
            'pdca' => 'PDCA',
            'anomalies' => 'Décisions / anomalies à traiter',
            'kpi_stats' => 'KPI et statistiques outils',
            'kpi_ai' => 'Espace assistances IA (placeholder)',
        ];

        foreach (UserPreferences::dashboardWidgetKeys() as $key) {
            $builder->add('dash_'.$key, CheckboxType::class, [
                'label' => $labels[$key] ?? $key,
                'required' => false,
                'mapped' => false,
                'data' => $prefs->isDashboardSectionVisible($key),
            ]);
        }

        $layout = DashboardLayout::fromStored($prefs->getDashboardLayout());
        $builder->add('widget_order', HiddenType::class, [
            'mapped' => false,
            'data' => implode(',', $layout->getOrderedWidgetIds()),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'preferences' => null,
        ]);
        $resolver->setAllowedTypes('preferences', [UserPreferences::class]);
        $resolver->setRequired('preferences');
    }

    public function getBlockPrefix(): string
    {
        return 'user_dashboard_preferences';
    }
}
