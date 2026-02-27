<?php

declare(strict_types=1);

namespace App\Newsletter\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire d'inscription à la newsletter (Mautic).
 */
class NewsletterSubscriptionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Votre adresse email',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir votre adresse email.']),
                    new Email(['message' => 'Veuillez saisir une adresse email valide.']),
                ],
            ])
            ->add('firstname', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Prénom (optionnel)',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'attr' => [
                'id' => 'newsletter-form',
                'data-turbo' => 'false',
                'class' => 'newsletter-form',
            ],
        ]);
    }
}
