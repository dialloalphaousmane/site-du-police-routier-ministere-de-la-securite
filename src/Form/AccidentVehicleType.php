<?php

namespace App\Form;

use App\Entity\AccidentVehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccidentVehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('marque', null, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Marque du véhicule'],
            ])
            ->add('modele', null, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Modèle du véhicule'],
            ])
            ->add('immatriculation', null, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Immatriculation'],
            ])
            ->add('couleur', null, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Couleur du véhicule'],
            ])
            ->add('typeVehicule', 'choice', [
                'choices' => [
                    'VOITURE' => 'Voiture',
                    'MOTO' => 'Moto',
                    'CAMION' => 'Camion',
                    'BUS' => 'Bus',
                    'VTU' => 'VTU',
                    'PL' => 'PL',
                    'VL' => 'VL',
                    'AUTRE' => 'Autre',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dommage', 'choice', [
                'choices' => [
                    'AUCUN' => 'Aucun',
                    'LEGER' => 'Léger',
                    'MODERE' => 'Modéré',
                    'GRAVE' => 'Grave',
                    'DETRUIT' => 'Détruit',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', null, [
                'attr' => ['rows' => 3, 'class' => 'form-control', 'placeholder' => 'Description des dommages'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccidentVehicle::class,
        ]);
    }
}
