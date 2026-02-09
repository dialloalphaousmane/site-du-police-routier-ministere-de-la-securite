<?php

namespace App\Form;

use App\Entity\Configuration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class ConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cle', TextType::class, [
                'label' => 'Clé de configuration',
                'attr' => [
                    'placeholder' => 'Ex: app_nom',
                    'class' => 'form-control',
                    'pattern' => '[a-zA-Z0-9_]+',
                    'title' => 'Utiliser uniquement des lettres, chiffres et underscores'
                ]
            ])
            ->add('valeur', TextType::class, [
                'label' => 'Valeur',
                'attr' => [
                    'placeholder' => 'Valeur de la configuration',
                    'class' => 'form-control'
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de valeur',
                'choices' => [
                    'Chaîne de caractères' => 'string',
                    'Entier' => 'integer',
                    'Nombre décimal' => 'decimal',
                    'Booléen' => 'boolean',
                    'Email' => 'email',
                    'URL' => 'url',
                    'JSON' => 'json'
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Général' => 'général',
                    'Sécurité' => 'sécurité',
                    'Amendes' => 'amendes',
                    'Notifications' => 'notifications',
                    'Export' => 'export',
                    'Rapports' => 'rapports',
                    'Système' => 'système'
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description détaillée de cette configuration',
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Configuration active',
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
            'data_class' => Configuration::class,
        ]);
    }
}
