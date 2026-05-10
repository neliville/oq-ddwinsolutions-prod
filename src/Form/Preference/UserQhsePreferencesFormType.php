<?php

declare(strict_types=1);

namespace App\Form\Preference;

use App\Entity\UserPreferences;
use App\UserPreferences\PilotingFocus;
use App\UserPreferences\PrimaryStandard;
use App\UserPreferences\QhsePriority;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserQhsePreferencesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('primaryStandard', EnumType::class, [
                'label' => 'Référentiel principal',
                'class' => PrimaryStandard::class,
                'choice_label' => static fn (PrimaryStandard $s): string => $s->label(),
                'attr' => ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('qhsePriority', EnumType::class, [
                'label' => 'Priorité principale',
                'class' => QhsePriority::class,
                'choice_label' => static fn (QhsePriority $p): string => match ($p) {
                    QhsePriority::QUALITY => 'Qualité',
                    QhsePriority::SAFETY => 'Sécurité',
                    QhsePriority::ENVIRONMENT => 'Environnement',
                    QhsePriority::COMPLIANCE => 'Conformité',
                },
                'attr' => ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('pilotingFocus', EnumType::class, [
                'label' => 'Priorité de pilotage',
                'class' => PilotingFocus::class,
                'choice_label' => static fn (PilotingFocus $f): string => $f->label(),
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
        return 'user_qhse_preferences';
    }
}
