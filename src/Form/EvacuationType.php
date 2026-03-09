<?php

namespace App\Form;

use App\Entity\Evacuation;
use App\Entity\Accident;
use App\Entity\Region;
use App\Entity\Brigade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EvacuationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference', null, [
                'required' => false,
                'disabled' => true,
            ])
            ->add('dateEvacuation', null, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('typeEvacuation', 'choice', [
                'choices' => [
                    'AMBULANCE' => 'Ambulance',
                    'HELI' => 'Hélicoptère',
                    'VEHICULE_PERSONNEL' => 'Véhicule personnel',
                    'CAMION' => 'Camion',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('status', 'choice', [
                'choices' => [
                    'EN_COURS' => 'En cours',
                    'TERMINE' => 'Terminé',
                    'ANNULE' => 'Annulé',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('urgence', 'choice', [
                'choices' => [
                    'BASSE' => 'Basse',
                    'MOYENNE' => 'Moyenne',
                    'HAUTE' => 'Haute',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('hopitalDestination', null, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Hôpital de destination'],
            ])
            ->add('accident', null, [
                'class' => 'form-control',
                'placeholder' => 'Accident associé',
            ])
            ->add('region', null, [
                'class' => 'form-control',
                'placeholder' => 'Région',
            ])
            ->add('brigade', null, [
                'class' => 'form-control',
                'placeholder' => 'Brigade',
            ])
            ->add('dateArrivee', null, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evacuation::class,
        ]);
    }
}
