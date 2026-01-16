<?php

namespace App\Form;

use App\Entity\Brigade;
use App\Entity\Region;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class BrigadeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextType::class, [
                'label' => 'Nom de la brigade',
                'attr' => [
                    'placeholder' => 'Ex: Brigade Centre',
                    'class' => 'form-control'
                ]
            ])
            ->add('code', TextType::class, [
                'label' => 'Code brigade',
                'attr' => [
                    'placeholder' => 'Ex: BRG-CEN',
                    'class' => 'form-control'
                ]
            ])
            ->add('chef', TextType::class, [
                'label' => 'Chef de brigade',
                'attr' => [
                    'placeholder' => 'Ex: Chef Mamadou Diallo',
                    'class' => 'form-control'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email de la brigade',
                'attr' => [
                    'placeholder' => 'brigade@police-routiere.gn',
                    'class' => 'form-control'
                ]
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => [
                    'placeholder' => 'Ex: +224 622 12 34 56',
                    'class' => 'form-control'
                ]
            ])
            ->add('localite', TextType::class, [
                'label' => 'Localité',
                'attr' => [
                    'placeholder' => 'Ex: Centre-ville',
                    'class' => 'form-control'
                ]
            ])
            ->add('adresse', TextareaType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'placeholder' => 'Adresse complète de la brigade',
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ])
            ->add('zoneCouverture', TextareaType::class, [
                'label' => 'Zone de couverture',
                'attr' => [
                    'placeholder' => 'Zone géographique couverte par la brigade',
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description de la brigade',
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ])
            ->add('region', EntityType::class, [
                'label' => 'Région',
                'class' => Region::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionnez une région',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Brigade active',
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
            'data_class' => Brigade::class,
        ]);
    }
}
