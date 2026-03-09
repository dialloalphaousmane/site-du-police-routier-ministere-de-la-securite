<?php

namespace App\Form;

use App\Entity\Accident;
use App\Entity\Region;
use App\Entity\Brigade;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccidentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference', null, [
                'required' => false,
                'disabled' => true,
            ])
            ->add('dateAccident', null, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('localisation', null, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Localisation de l\'accident'],
            ])
            ->add('ville', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ville'],
            ])
            ->add('commune', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Commune'],
            ])
            ->add('route', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Route'],
            ])
            ->add('carrefour', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Carrefour'],
            ])
            ->add('meteo', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Conditions météo'],
            ])
            ->add('latitude', NumberType::class, [
                'required' => false,
                'scale' => 8,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Latitude (ex: 9.5370)'],
            ])
            ->add('longitude', NumberType::class, [
                'required' => false,
                'scale' => 8,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Longitude (ex: -13.6773)'],
            ])
            ->add('description', null, [
                'attr' => ['rows' => 4, 'class' => 'form-control', 'placeholder' => 'Description détaillée de l\'accident'],
            ])
            ->add('gravite', ChoiceType::class, [
                'choices' => [
                    'Léger' => Accident::GRAVITY_LEGER,
                    'Urgent' => Accident::GRAVITY_URGENT,
                    'Grave' => Accident::GRAVITY_GRAVE,
                    'Mortel' => Accident::GRAVITY_MORTEL,
                ],
                'placeholder' => 'Sélectionner la gravité',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('causePrincipale', ChoiceType::class, [
                'choices' => array_flip(Accident::CAUSES),
                'placeholder' => 'Sélectionner la cause principale',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('causesSecondaires', ChoiceType::class, [
                'choices' => array_flip(Accident::CAUSES),
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'En cours' => Accident::STATUS_EN_COURS,
                    'Traité' => Accident::STATUS_TRAITE,
                    'Archivé' => Accident::STATUS_ARCHIVE,
                    'Évacuation' => Accident::STATUS_EVACUATION,
                ],
                'placeholder' => 'Sélectionner le statut',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('region', EntityType::class, [
                'class' => Region::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner une région',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('brigade', EntityType::class, [
                'class' => Brigade::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner une brigade',
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Accident::class,
        ]);
    }
}
