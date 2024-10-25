<?php

namespace App\Form;

use App\Entity\User; 
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class EmailUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('new_email', EmailType::class, [
                'label' => 'Adresse e-mail actuelle',
                'attr' => ['readonly' => true],
                'mapped' => false,
                'data' => $options['data']->getEmail() 
            ])
            ->add('email', EmailType::class, [
                'label' => 'Nouvelle adresse e-mail',
                'data' => []
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe actuel'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            
            'data_class' => User::class, 
        ]);
    }
}
