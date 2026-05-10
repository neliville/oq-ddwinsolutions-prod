<?php

declare(strict_types=1);

namespace App\Form\Preference;

use App\Entity\UserPreferences;
use App\UserPreferences\NotificationFrequency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserNotificationPreferencesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('notifyOverdueActions', CheckboxType::class, [
                'label' => 'Actions en retard (CAPA, plans d’audit)',
                'required' => false,
            ])
            ->add('notifyAuditsToPrepare', CheckboxType::class, [
                'label' => 'Audits à préparer',
                'required' => false,
            ])
            ->add('notifyCapaVerification', CheckboxType::class, [
                'label' => 'CAPA à vérifier',
                'required' => false,
            ])
            ->add('notifyCriticalRisks', CheckboxType::class, [
                'label' => 'Risques critiques',
                'required' => false,
            ])
            ->add('notifyWeeklyDigest', CheckboxType::class, [
                'label' => 'Synthèse hebdomadaire QHSE (email)',
                'required' => false,
            ])
            ->add('notificationFrequency', EnumType::class, [
                'label' => 'Fréquence des rappels',
                'class' => NotificationFrequency::class,
                'choice_label' => static fn (NotificationFrequency $f): string => match ($f) {
                    NotificationFrequency::IMMEDIATE => 'Immédiat (dès que possible)',
                    NotificationFrequency::WEEKLY => 'Hebdomadaire',
                },
                'attr' => ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserPreferences::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'user_notification_preferences';
    }
}
