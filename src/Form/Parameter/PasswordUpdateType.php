<?php

namespace App\Form\Parameter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\NotBlank;

class PasswordUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('actual_password', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'attr' => [
                    'autocomplete' => 'current-password',
                    'id' => 'password_form_actual_password'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre mot de passe actuel'])
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Nouveau mot de passe',
                'attr' => [
                    'autocomplete' => 'new-password',
                    'id' => 'password_form_password'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un nouveau mot de passe'])
                ]
            ])
            ->add('confirm_password', PasswordType::class, [
                'label' => 'Confirmer le mot de passe',
                'attr' => [
                    'autocomplete' => 'new-password',
                    'id' => 'password_form_confirm_password'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez confirmer votre nouveau mot de passe'])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_field_name' => 'password_token',
            'csrf_token_id' => 'password_form_token',
            'attr' => ['id' => 'password_form']
        ]);
    }
}
