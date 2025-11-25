<?php

namespace App\Form;

use App\Dto\UnsubscribeReasonDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UnsubscribeReasonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reasons', ChoiceType::class, [
                'label' => 'Pourquoi vous désabonnez-vous ?',
                'expanded' => true,   // cases à cocher
                'multiple' => true,
                'required' => false,
                'choices' => [
                    'Je reçois trop d\'emails' => 'too_many',
                    'Le contenu ne correspond plus à mes besoins' => 'not_relevant',
                    'Je suis déjà bien équipé en outils QSE' => 'already_equipped',
                    'Je ne me souviens pas m\'être inscrit(e)' => 'dont_remember',
                    'Autre raison' => 'other',
                ],
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Un commentaire à nous partager ? (facultatif)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Votre commentaire...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UnsubscribeReasonDto::class,
        ]);
    }
}

