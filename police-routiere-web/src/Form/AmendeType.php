<?php

namespace App\Form;

use App\Entity\Amende;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AmendeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'attr' => [
                    'placeholder' => 'AMD-2025-XXXX',
                    'class' => 'form-control'
                ],
                'disabled' => true,
                'help' => 'Généré automatiquement'
            ])
            ->add('montant', NumberType::class, [
                'label' => 'Montant payé (GNF)',
                'scale' => 2,
                'attr' => [
                    'placeholder' => '50000',
                    'min' => 0,
                    'step' => '1000',
                    'class' => 'form-control'
                ]
            ])
            ->add('datePaiement', DateType::class, [
                'label' => 'Date de paiement',
                'widget' => 'single_text',
                'data' => new \DateTimeImmutable(),
                'attr' => ['class' => 'form-control']
            ])
            ->add('modePaiement', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices' => [
                    'Espèces' => 'ESPECES',
                    'Carte bancaire' => 'CARTE_BANCAIRE',
                    'Mobile Money' => 'MOBILE_MONEY',
                    'Virement bancaire' => 'VIREMENT_BANCAIRE',
                    'Chèque' => 'CHEQUE',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut du paiement',
                'choices' => [
                    'En attente' => 'EN_ATTENTE',
                    'Validé' => 'VALIDE',
                    'Annulé' => 'ANNULE',
                    'Remboursé' => 'REMBOURSE',
                ],
                'attr' => ['class' => 'form-select'],
                'disabled' => !$options['can_edit_statut'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Amende::class,
            'can_edit_statut' => true,
        ]);

        $resolver->setAllowedTypes('can_edit_statut', 'bool');
    }
}
