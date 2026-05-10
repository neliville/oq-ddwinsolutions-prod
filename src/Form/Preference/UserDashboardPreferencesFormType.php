<?php

declare(strict_types=1);

namespace App\Form\Preference;

use App\Entity\UserPreferences;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire sans data_class : hydrate {@see UserPreferences::dashboardVisibility} dans le contrôleur.
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
            'kpi' => 'KPI et statistiques outils',
        ];

        foreach (UserPreferences::dashboardSectionKeys() as $key) {
            $builder->add('dash_'.$key, CheckboxType::class, [
                'label' => $labels[$key] ?? $key,
                'required' => false,
                'mapped' => false,
                'data' => $prefs->isDashboardSectionVisible($key),
            ]);
        }
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
