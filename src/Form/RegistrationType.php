<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Region;
use App\Entity\Brigade;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Votre adresse email'],
                'constraints' => [
                    new NotBlank(['message' => 'Email obligatoire']),
                ]
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Votre nom'],
                'constraints' => [new NotBlank(['message' => 'Nom obligatoire'])]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Votre prénom'],
                'constraints' => [new NotBlank(['message' => 'Prénom obligatoire'])]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez votre mot de passe'],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Confirmez votre mot de passe'],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints' => [
                    new NotBlank(['message' => 'Mot de passe obligatoire']),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Minimum 8 caractères',
                        'max' => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
                        'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial',
                    ]),
                ],
                'mapped' => false,
            ])
            ->add('roleCode', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Agent Routier' => 'ROLE_AGENT',
                    'Chef de Brigade' => 'ROLE_CHEF_BRIGADE',
                    'Direction Régionale' => 'ROLE_DIRECTION_REGIONALE',
                    'Direction Générale' => 'ROLE_DIRECTION_GENERALE',
                    'Administrateur Système' => 'ROLE_ADMIN',
                ],
                'attr' => ['class' => 'form-control'],
                'mapped' => false,
                'constraints' => [new NotBlank(['message' => 'Rôle obligatoire'])]
            ])
            ->add('region', EntityType::class, [
                'class' => Region::class,
                'choice_label' => 'libelle',
                'label' => 'Région',
                'placeholder' => '-- Sélectionner une région --',
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'mapped' => false,
            ])
            ->add('brigade', EntityType::class, [
                'class' => Brigade::class,
                'choice_label' => 'libelle',
                'label' => 'Brigade',
                'placeholder' => '-- Sélectionner une brigade --',
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'mapped' => false,
            ])
        ;

        // Validation dynamique des champs region/brigade
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            $roleCode = $data['roleCode'] ?? null;

            if (in_array($roleCode, ['ROLE_DIRECTION_REGIONALE', 'ROLE_CHEF_BRIGADE', 'ROLE_AGENT'])) {
                $form->get('region')->setData($data['region'] ?? null);
            }

            if (in_array($roleCode, ['ROLE_CHEF_BRIGADE', 'ROLE_AGENT'])) {
                $form->get('brigade')->setData($data['brigade'] ?? null);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
