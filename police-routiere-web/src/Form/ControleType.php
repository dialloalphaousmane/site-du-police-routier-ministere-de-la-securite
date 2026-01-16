<?php

namespace App\Form;

use App\Entity\Controle;
use App\Entity\Agent;
use App\Entity\Brigade;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ControleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateControle', DateType::class, [
                'label' => 'Date du contrôle',
                'widget' => 'single_text',
                'data' => new \DateTimeImmutable(),
            ])
            ->add('lieuControle', TextType::class, [
                'label' => 'Lieu du contrôle',
                'attr' => [
                    'placeholder' => 'Ex: Avenue Patrice Lumumba, Kinshasa',
                    'class' => 'form-control'
                ]
            ])
            ->add('marqueVehicule', TextType::class, [
                'label' => 'Marque du véhicule',
                'attr' => [
                    'placeholder' => 'Ex: Toyota, Renault, BMW',
                    'class' => 'form-control'
                ]
            ])
            ->add('immatriculation', TextType::class, [
                'label' => 'Immatriculation',
                'attr' => [
                    'placeholder' => 'Ex: CG-123-AB',
                    'class' => 'form-control'
                ]
            ])
            ->add('nomConducteur', TextType::class, [
                'label' => 'Nom du conducteur',
                'attr' => [
                    'placeholder' => 'Nom du conducteur',
                    'class' => 'form-control'
                ]
            ])
            ->add('prenomConducteur', TextType::class, [
                'label' => 'Prénom du conducteur',
                'attr' => [
                    'placeholder' => 'Prénom du conducteur',
                    'class' => 'form-control'
                ]
            ])
            ->add('noConducteur', TextType::class, [
                'label' => 'Numéro de permis',
                'attr' => [
                    'placeholder' => 'Numéro du permis de conduire',
                    'class' => 'form-control'
                ]
            ])
            ->add('observation', TextareaType::class, [
                'label' => 'Observations',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Observations supplémentaires...',
                    'class' => 'form-control'
                ]
            ])
            ->add('agent', EntityType::class, [
                'label' => 'Agent',
                'class' => Agent::class,
                'choice_label' => function (Agent $agent) {
                    return $agent->getNom() . ' ' . $agent->getPrenom() . ' (' . $agent->getMatricule() . ')';
                },
                'attr' => ['class' => 'form-select'],
                'disabled' => !$options['can_edit_agent'],
            ])
            ->add('brigade', EntityType::class, [
                'label' => 'Brigade',
                'class' => Brigade::class,
                'choice_label' => function (Brigade $brigade) {
                    return $brigade->getLibelle() . ' (' . $brigade->getCode() . ')';
                },
                'attr' => ['class' => 'form-select'],
                'disabled' => !$options['can_edit_brigade'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Controle::class,
            'can_edit_agent' => true,
            'can_edit_brigade' => true,
        ]);

        $resolver->setAllowedTypes('can_edit_agent', 'bool');
        $resolver->setAllowedTypes('can_edit_brigade', 'bool');
    }
}
