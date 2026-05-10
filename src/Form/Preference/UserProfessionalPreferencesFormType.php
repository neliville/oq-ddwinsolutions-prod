<?php

declare(strict_types=1);

namespace App\Form\Preference;

use App\Entity\UserPreferences;
use App\UserPreferences\AcquisitionSource;
use App\UserPreferences\CompanySize;
use App\UserPreferences\JobFunction;
use App\UserPreferences\MainActivity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

final class UserProfessionalPreferencesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'constraints' => [new Length(max: 120)],
                'attr' => ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'constraints' => [new Length(max: 120)],
                'attr' => ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('companyName', TextType::class, [
                'label' => 'Entreprise',
                'required' => false,
                'constraints' => [new Length(max: 255)],
                'attr' => ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('jobFunction', EnumType::class, [
                'label' => 'Fonction / poste',
                'class' => JobFunction::class,
                'required' => false,
                'placeholder' => '—',
                'choice_label' => static fn (JobFunction $j): string => $j->label(),
                'attr' => ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('sector', TextType::class, [
                'label' => 'Secteur (complément libre)',
                'required' => false,
                'constraints' => [new Length(max: 120)],
                'attr' => ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('companySize', EnumType::class, [
                'label' => 'Taille de l’entreprise',
                'class' => CompanySize::class,
                'required' => false,
                'placeholder' => '—',
                'choice_label' => static fn (CompanySize $c): string => $c->label(),
                'attr' => ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('mainActivity', EnumType::class, [
                'label' => 'Secteur d’activité',
                'class' => MainActivity::class,
                'required' => false,
                'placeholder' => '—',
                'choice_label' => static fn (MainActivity $a): string => $a->label(),
                'attr' => ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('acquisitionSource', EnumType::class, [
                'label' => 'Comment nous avez-vous connus ?',
                'class' => AcquisitionSource::class,
                'required' => false,
                'placeholder' => '—',
                'choice_label' => static fn (AcquisitionSource $s): string => $s->label(),
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
        return 'user_professional_preferences';
    }
}
