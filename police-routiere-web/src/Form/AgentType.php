<?php

namespace App\Form;

use App\Entity\Agent;
use App\Entity\Region;
use App\Entity\Brigade;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('matricule', TextType::class, [
                'label' => 'Matricule',
                'attr' => [
                    'placeholder' => 'AG-KIN-0001',
                    'class' => 'form-control'
                ],
                'disabled' => !$options['can_edit_matricule'],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Nom de l\'agent',
                    'class' => 'form-control'
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'placeholder' => 'Prénom de l\'agent',
                    'class' => 'form-control'
                ]
            ])
            ->add('grade', ChoiceType::class, [
                'label' => 'Grade',
                'choices' => [
                    'Officier' => 'Officier',
                    'Sous-officier' => 'Sous-officier',
                    'Adjudant' => 'Adjudant',
                    'Sergent' => 'Sergent',
                    'Caporal' => 'Caporal',
                    'Agent' => 'Agent',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('dateEmbauche', DateType::class, [
                'label' => 'Date d\'embauche',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('region', EntityType::class, [
                'label' => 'Région',
                'class' => Region::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner une région',
                'attr' => ['class' => 'form-select'],
                'disabled' => !$options['can_edit_region'],
            ])
            ->add('brigade', EntityType::class, [
                'label' => 'Brigade',
                'class' => Brigade::class,
                'choice_label' => function (Brigade $brigade) {
                    return $brigade->getLibelle() . ' (' . $brigade->getCode() . ')';
                },
                'placeholder' => 'Sélectionner une brigade',
                'attr' => ['class' => 'form-select'],
                'disabled' => !$options['can_edit_brigade'],
            ])
            ->add('isActif', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => true,
                    'Inactif' => false,
                ],
                'attr' => ['class' => 'form-select'],
                'disabled' => !$options['can_edit_status'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Agent::class,
            'can_edit_matricule' => true,
            'can_edit_region' => true,
            'can_edit_brigade' => true,
            'can_edit_status' => true,
        ]);

        $resolver->setAllowedTypes('can_edit_matricule', 'bool');
        $resolver->setAllowedTypes('can_edit_region', 'bool');
        $resolver->setAllowedTypes('can_edit_brigade', 'bool');
        $resolver->setAllowedTypes('can_edit_status', 'bool');
    }
}
