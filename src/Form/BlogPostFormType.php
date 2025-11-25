<?php

namespace App\Form;

use App\Entity\BlogPost;
use App\Entity\Category;
use App\Entity\Tag;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class BlogPostFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Titre de l\'article',
                ],
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug (URL)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'slug-automatique-ou-personnalise',
                    'help' => 'Laissé vide pour génération automatique depuis le titre',
                ],
            ])
            ->add('excerpt', TextareaType::class, [
                'label' => 'Extrait',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Court résumé de l\'article...',
                ],
            ])
            ->add('featuredImage', FileType::class, [
                'label' => 'Image à la une (JPG ou WEBP)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/jpeg,image/webp',
                    'data-controller' => 'image-upload',
                    'data-image-upload-preview-target' => 'input',
                ],
                'constraints' => [
                    new Image([
                        'maxSize' => '4M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image au format JPG ou WEBP.',
                        'minWidth' => 400,
                        'minHeight' => 300,
                        'maxWidth' => 4000,
                        'maxHeight' => 3000,
                        'minWidthMessage' => 'L\'image doit faire au moins {{ min_width }}px de large (actuellement {{ width }}px).',
                        'minHeightMessage' => 'L\'image doit faire au moins {{ min_height }}px de haut (actuellement {{ height }}px).',
                        'maxWidthMessage' => 'L\'image ne doit pas dépasser {{ max_width }}px de large (actuellement {{ width }}px).',
                        'maxHeightMessage' => 'L\'image ne doit pas dépasser {{ max_height }}px de haut (actuellement {{ height }}px).',
                    ]),
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 15,
                    'placeholder' => 'Contenu complet de l\'article...',
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'label' => 'Catégorie',
                'choice_label' => 'name',
                'required' => true,
                'placeholder' => 'Sélectionner une catégorie',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'label' => 'Tags',
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('publishedAt', DateTimeType::class, [
                'label' => 'Date de publication',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('readTime', TextType::class, [
                'label' => 'Temps de lecture (ex: 5 min)',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '5 min',
                ],
            ])
            ->add('featured', CheckboxType::class, [
                'label' => 'Article mis en avant',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BlogPost::class,
        ]);
    }
}

