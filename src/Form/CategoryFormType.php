<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom de la catégorie',
                ],
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug (URL)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'slug-automatique-ou-personnalise',
                    'help' => 'Laissé vide pour génération automatique depuis le nom',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Description de la catégorie...',
                ],
            ])
            ->add('color', ColorType::class, [
                'label' => 'Couleur',
                'required' => true,
                'attr' => [
                    'class' => 'form-control form-control-color',
                ],
            ])
            ->add('icon', TextType::class, [
                'label' => 'Icône (Lucide)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'home',
                    'help' => 'Nom de l\'icône Lucide (ex: home, file-text...). Laissez vide pour aucune icône.',
                ],
            ])
            ->add('order', IntegerType::class, [
                'label' => 'Ordre d\'affichage',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}

