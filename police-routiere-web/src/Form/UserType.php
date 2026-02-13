<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Role;
use App\Entity\Region;
use App\Entity\Brigade;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'email@police-routiere.gn',
                    'class' => 'form-control'
                ]
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Nom de l\'utilisateur',
                    'class' => 'form-control'
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'placeholder' => 'Prénom de l\'utilisateur',
                    'class' => 'form-control'
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'required' => $options['require_password'],
                'attr' => [
                    'placeholder' => 'Laisser vide pour ne pas modifier',
                    'class' => 'form-control'
                ],
                'help' => 'Minimum 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial'
            ])
            ->add('roleCode', EntityType::class, [
                'label' => 'Rôle',
                'class' => Role::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner un rôle',
                'attr' => ['class' => 'form-select'],
                'mapped' => false,
                'data' => null,
            ])
            ->add('region', EntityType::class, [
                'label' => 'Région',
                'class' => Region::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner une région',
                'attr' => ['class' => 'form-select'],
                'required' => false,
                'mapped' => false,
                'data' => $options['data']?->getRegion(),
            ])
            ->add('brigade', EntityType::class, [
                'label' => 'Brigade',
                'class' => Brigade::class,
                'choice_label' => function (Brigade $brigade) {
                    return $brigade->getLibelle() . ' (' . $brigade->getCode() . ')';
                },
                'placeholder' => 'Sélectionner une brigade',
                'attr' => ['class' => 'form-select'],
                'required' => false,
                'mapped' => false,
                'data' => $options['data']?->getBrigade(),
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Compte actif',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'require_password' => true,
        ]);

        $resolver->setAllowedTypes('require_password', 'bool');
    }
}
