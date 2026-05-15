<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\HomepageContentSlot;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class HomepageContentSlotFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'required' => false,
                'attr' => ['rows' => 8, 'class' => 'w-full'],
                'help' => 'Laissez vide pour conserver le texte par défaut codé en dur sur la homepage.',
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Slot actif',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HomepageContentSlot::class,
        ]);
    }
}
