<?php

namespace App\Form\Parameter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmailUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Nouvelle adresse e-mail',
                'attr' => [
                    'autocomplete' => 'email',
                    'id' => 'email_form_email'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer une adresse e-mail']),
                    new Email(['message' => 'Veuillez entrer une adresse e-mail valide'])
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'attr' => [
                    'autocomplete' => 'current-password',
                    'id' => 'email_form_password'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre mot de passe actuel'])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_field_name' => 'email_token',
            'csrf_token_id' => 'email_form_token',
            'attr' => ['id' => 'email_form']
        ]);
    }
}
