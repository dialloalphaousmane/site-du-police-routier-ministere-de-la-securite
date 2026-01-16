<?php

namespace App\Form;

use App\Entity\Region;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class RegionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextType::class, [
                'label' => 'Nom de la région',
                'attr' => [
                    'placeholder' => 'Ex: Conakry',
                    'class' => 'form-control'
                ]
            ])
            ->add('code', TextType::class, [
                'label' => 'Code région',
                'attr' => [
                    'placeholder' => 'Ex: CKY',
                    'class' => 'form-control'
                ]
            ])
            ->add('directeur', TextType::class, [
                'label' => 'Directeur régional',
                'attr' => [
                    'placeholder' => 'Ex: Col. Mamadou Bah',
                    'class' => 'form-control'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email de la direction',
                'attr' => [
                    'placeholder' => 'direction@region.gn',
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
            ->add('adresse', TextareaType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'placeholder' => 'Adresse complète de la direction régionale',
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description de la région',
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Région active',
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
            'data_class' => Region::class,
        ]);
    }
}
