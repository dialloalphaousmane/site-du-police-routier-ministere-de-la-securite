<?php

namespace App\Form;

use App\Entity\Infraction;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InfractionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code infraction',
                'attr' => [
                    'placeholder' => 'Ex: VIT-001',
                    'class' => 'form-control'
                ]
            ])
            ->add('libelle', TextType::class, [
                'label' => 'Libellé de l\'infraction',
                'attr' => [
                    'placeholder' => 'Ex: Excès de vitesse',
                    'class' => 'form-control'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Description détaillée de l\'infraction...',
                    'class' => 'form-control'
                ]
            ])
            ->add('montantAmende', NumberType::class, [
                'label' => 'Montant de l\'amende (GNF)',
                'scale' => 2,
                'attr' => [
                    'placeholder' => '50000',
                    'min' => 0,
                    'step' => '1000',
                    'class' => 'form-control'
                ]
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Non payée' => 'NON_PAYEE',
                    'Partiellement payée' => 'PARTIELLEMENT_PAYEE',
                    'Payée' => 'PAYEE',
                ],
                'attr' => ['class' => 'form-select'],
                'disabled' => !$options['can_edit_statut'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Infraction::class,
            'can_edit_statut' => true,
        ]);

        $resolver->setAllowedTypes('can_edit_statut', 'bool');
    }
}
