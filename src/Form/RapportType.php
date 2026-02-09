<?php

namespace App\Form;

use App\Entity\Rapport;
use App\Entity\Region;
use App\Entity\Brigade;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class RapportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du rapport',
                'attr' => [
                    'placeholder' => 'Ex: Rapport mensuel des contrôles',
                    'class' => 'form-control'
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de rapport',
                'choices' => [
                    'Rapport mensuel' => 'MENSUEL',
                    'Rapport hebdomadaire' => 'HEBDOMADAIRE',
                    'Rapport journalier' => 'JOURNALIER',
                    'Rapport d\'incident' => 'INCIDENT',
                    'Rapport statistique' => 'STATISTIQUE',
                    'Autre' => 'AUTRE'
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu du rapport',
                'attr' => [
                    'placeholder' => 'Contenu détaillé du rapport...',
                    'class' => 'form-control',
                    'rows' => 10
                ]
            ])
            ->add('region', EntityType::class, [
                'label' => 'Région',
                'class' => Region::class,
                'choice_label' => 'libelle',
                'required' => false,
                'placeholder' => 'Sélectionnez une région',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('brigade', EntityType::class, [
                'label' => 'Brigade',
                'class' => Brigade::class,
                'choice_label' => 'libelle',
                'required' => false,
                'placeholder' => 'Sélectionnez une brigade',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('observations', TextareaType::class, [
                'label' => 'Observations',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Observations supplémentaires...',
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Rapport actif',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rapport::class,
        ]);
    }
}
