<?php

declare(strict_types=1);

namespace App\Form\Qse;

use App\Entity\Qse\RiskMatrixEntry;
use App\Qse\Enum\RiskCategory;
use App\Qse\Enum\RiskEntryStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

final class RiskMatrixEntryFormType extends AbstractType
{
    private const INPUT_CLASS = 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50';

    private const TEXTAREA_CLASS = 'flex min-h-[5rem] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50';

    private const SELECT_CLASS = 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('identified_risk', TextType::class, [
                'label' => 'Risque identifié',
                'property_path' => 'identifiedRisk',
                'constraints' => [
                    new NotBlank(message: 'Veuillez nommer le risque identifié.'),
                ],
                'attr' => [
                    'class' => self::INPUT_CLASS,
                    'autocomplete' => 'off',
                    'maxlength' => 255,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => self::TEXTAREA_CLASS,
                    'rows' => 3,
                ],
            ])
            ->add('concerned_process', TextType::class, [
                'label' => 'Processus concerné',
                'property_path' => 'concernedProcess',
                'required' => false,
                'attr' => [
                    'class' => self::INPUT_CLASS,
                    'placeholder' => 'Ex. production, logistique…',
                ],
            ])
            ->add('risk_category', EnumType::class, [
                'label' => 'Catégorie',
                'class' => RiskCategory::class,
                'property_path' => 'riskCategory',
                'choice_label' => static fn (RiskCategory $c): string => match ($c) {
                    RiskCategory::QUALITY => 'Qualité (Q)',
                    RiskCategory::SAFETY => 'Sécurité (S)',
                    RiskCategory::ENVIRONMENT => 'Environnement (E)',
                    RiskCategory::COMPLIANCE => 'Conformité',
                    RiskCategory::CUSTOMER => 'Client',
                    RiskCategory::SUPPLIER => 'Fournisseur',
                },
                'attr' => ['class' => self::SELECT_CLASS],
            ])
            ->add('severity', IntegerType::class, [
                'label' => 'Gravité',
                'required' => false,
                'empty_data' => null,
                'constraints' => [new Range(min: 1, max: 10)],
                'attr' => [
                    'class' => 'risk-scale-input',
                    'min' => 1,
                    'max' => 10,
                    'step' => 1,
                    'data-risk-criticality-target' => 'severity',
                ],
            ])
            ->add('probability', IntegerType::class, [
                'label' => 'Probabilité',
                'required' => false,
                'empty_data' => null,
                'constraints' => [new Range(min: 1, max: 10)],
                'attr' => [
                    'class' => 'risk-scale-input',
                    'min' => 1,
                    'max' => 10,
                    'step' => 1,
                    'data-risk-criticality-target' => 'probability',
                ],
            ])
            ->add('detection', IntegerType::class, [
                'label' => 'Détection',
                'required' => false,
                'empty_data' => null,
                'constraints' => [new Range(min: 1, max: 10)],
                'attr' => [
                    'class' => 'risk-scale-input',
                    'min' => 1,
                    'max' => 10,
                    'step' => 1,
                    'data-risk-criticality-target' => 'detection',
                ],
            ])
            ->add('existing_actions', TextareaType::class, [
                'label' => 'Actions existantes',
                'property_path' => 'existingActions',
                'required' => false,
                'attr' => [
                    'class' => self::TEXTAREA_CLASS,
                    'rows' => 3,
                    'placeholder' => 'Mesures de maîtrise déjà en place…',
                ],
            ])
            ->add('responsible', TextType::class, [
                'label' => 'Responsable',
                'required' => false,
                'attr' => [
                    'class' => self::INPUT_CLASS,
                    'placeholder' => 'Nom, rôle ou équipe',
                ],
            ])
            ->add('review_at', DateType::class, [
                'label' => 'Prochaine revue',
                'property_path' => 'reviewAt',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => ['class' => self::INPUT_CLASS],
            ])
            ->add('status', EnumType::class, [
                'label' => 'Statut',
                'class' => RiskEntryStatus::class,
                'choice_label' => static fn (RiskEntryStatus $s): string => match ($s) {
                    RiskEntryStatus::IDENTIFIE => 'Identifié',
                    RiskEntryStatus::EN_ANALYSE => 'En analyse',
                    RiskEntryStatus::MAITRISE => 'Maîtrisé',
                    RiskEntryStatus::SOUS_SURVEILLANCE => 'Sous surveillance',
                    RiskEntryStatus::CRITIQUE => 'Critique',
                    RiskEntryStatus::CLOTURE => 'Clôturé',
                },
                'attr' => [
                    'class' => self::SELECT_CLASS,
                    'data-risk-criticality-target' => 'status',
                ],
            ])
            ->add('action', HiddenType::class, [
                'mapped' => false,
                'data' => 'save',
            ])
            ->add('origin', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RiskMatrixEntry::class,
            'csrf_token_id' => 'qse_risk_new',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
