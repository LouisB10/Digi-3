<?php

namespace App\Form;

use App\Entity\Parameter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppFormParameterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('paramKey')  
            ->add('paramValue')
            ->add('paramDateFrom', DateTimeType::class, [
                'widget' => 'single_text',
                'input'  => 'datetime',
            ])
            ->add('paramDateTo', DateTimeType::class, [
                'widget' => 'single_text',
                'input'  => 'datetime',
            ]); 
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Parameter::class,
        ]);
    }
}
