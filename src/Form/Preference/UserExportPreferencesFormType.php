<?php

declare(strict_types=1);

namespace App\Form\Preference;

use App\Entity\UserPreferences;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

final class UserExportPreferencesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('exportDisplayName', TextType::class, [
                'label' => 'Nom affiché sur les exports',
                'required' => false,
                'constraints' => [new Length(max: 255)],
                'attr' => ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('exportJobTitle', TextType::class, [
                'label' => 'Fonction affichée',
                'required' => false,
                'constraints' => [new Length(max: 255)],
                'attr' => ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('exportCompanyName', TextType::class, [
                'label' => 'Nom entreprise (export)',
                'required' => false,
                'constraints' => [new Length(max: 255)],
                'attr' => ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('exportPdfFooter', TextareaType::class, [
                'label' => 'Pied de page PDF (ex. « Document interne QHSE »)',
                'required' => false,
                'constraints' => [new Length(max: 500)],
                'attr' => ['rows' => 3, 'class' => 'flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm'],
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
        return 'user_export_preferences';
    }
}
