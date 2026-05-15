<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\HomepageTestimonial;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class HomepageTestimonialFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Nom affiché',
                'constraints' => [new Assert\NotBlank()],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Claire D.'],
            ])
            ->add('jobTitle', TextType::class, [
                'label' => 'Fonction',
                'constraints' => [new Assert\NotBlank()],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Responsable QSE'],
            ])
            ->add('company', TextType::class, [
                'label' => 'Entreprise / secteur',
                'constraints' => [new Assert\NotBlank()],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Industrie manufacturière'],
            ])
            ->add('quote', TextareaType::class, [
                'label' => 'Citation',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 20)],
                'attr' => ['class' => 'form-control', 'rows' => 5],
            ])
            ->add('initials', TextType::class, [
                'label' => 'Initiales (avatar)',
                'help' => '1 à 3 caractères affichés dans l’avatar rond.',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 10)],
                'attr' => ['class' => 'form-control', 'maxlength' => 10, 'placeholder' => 'CD'],
            ])
            ->add('rating', IntegerType::class, [
                'label' => 'Note (étoiles)',
                'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 5],
            ])
            ->add('displayOrder', IntegerType::class, [
                'label' => 'Ordre d’affichage',
                'attr' => ['class' => 'form-control', 'min' => 0],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Actif sur la homepage',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HomepageTestimonial::class,
        ]);
    }
}
