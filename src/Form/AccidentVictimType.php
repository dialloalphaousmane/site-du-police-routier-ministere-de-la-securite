<?php

namespace App\Form;

use App\Entity\AccidentVictim;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccidentVictimType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', null, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nom de la victime'],
            ])
            ->add('prenom', null, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Prénom de la victime'],
            ])
            ->add('age', null, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Âge de la victime'],
            ])
            ->add('sexe', 'choice', [
                'choices' => [
                    'MASCULIN' => 'Masculin',
                    'FEMININ' => 'Féminin',
                    'AUTRE' => 'Autre',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('typeVictime', 'choice', [
                'choices' => [
                    'CONDUCTEUR' => 'Conducteur',
                    'PASSAGER' => 'Passager',
                    'PIETON' => 'Piéton',
                    'AUTRE' => 'Autre',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('gravite', 'choice', [
                'choices' => [
                    'INDENNE' => 'Indemne',
                    'BLESSE_LEGER' => 'Blessé léger',
                    'BLESSE_GRAVE' => 'Blessé grave',
                    'MORTEL' => 'Mortel',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', null, [
                'attr' => ['rows' => 3, 'class' => 'form-control', 'placeholder' => 'Description des blessures'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccidentVictim::class,
        ]);
    }
}
