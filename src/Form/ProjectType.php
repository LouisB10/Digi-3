<?php

namespace App\Form;

use App\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('projectName', TextType::class, [
                'label' => 'Nom du projet',
            ])
            ->add('projectDescription', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('projectStartDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text', // Utilise un champ de type HTML5 date
                'required' => false,
            ])
            ->add('projectTargetDate', DateType::class, [
                'label' => 'Date de fin prévue',
                'widget' => 'single_text',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'project_form',
        ]);
    }
} 