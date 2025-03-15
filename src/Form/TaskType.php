<?php

namespace App\Form;

use App\Entity\Tasks;
use App\Enum\TaskPriority;
use App\Enum\TaskComplexity;
use App\Enum\TaskStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('taskName', TextType::class, [
                'label' => 'Nom de la tâche',
            ])
            ->add('taskDescription', TextareaType::class, [
                'label' => 'Description de la tâche',
                'required' => false,
            ])
            ->add('taskType', TextType::class, [
                'label' => 'Type de tâche',
            ])
            ->add('taskStartDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début',
                'required' => false,
            ])
            ->add('taskTargetDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date cible',
                'required' => false,
            ])
            ->add('taskStatus', EnumType::class, [
                'class' => TaskStatus::class,
                'label' => 'Statut',
                'choice_label' => function ($choice) {
                    return match($choice) {
                        TaskStatus::NEW => 'Nouvelle',
                        TaskStatus::IN_PROGRESS => 'En cours',
                        TaskStatus::REVIEW => 'En revue',
                        TaskStatus::COMPLETED => 'Terminée',
                        TaskStatus::BLOCKED => 'Bloquée',
                        default => $choice->name,
                    };
                },
            ])
            ->add('taskPriority', EnumType::class, [
                'class' => TaskPriority::class,
                'label' => 'Priorité',
                'choice_label' => function ($choice) {
                    return match($choice) {
                        TaskPriority::LOW => 'Basse',
                        TaskPriority::MEDIUM => 'Moyenne',
                        TaskPriority::HIGH => 'Haute',
                        TaskPriority::URGENT => 'Urgente',
                        default => $choice->name,
                    };
                },
            ])
            ->add('taskComplexity', EnumType::class, [
                'class' => TaskComplexity::class,
                'label' => 'Complexité',
                'choice_label' => function ($choice) {
                    return match($choice) {
                        TaskComplexity::SIMPLE => 'Simple',
                        TaskComplexity::MODERATE => 'Modérée',
                        TaskComplexity::COMPLEX => 'Complexe',
                        TaskComplexity::VERY_COMPLEX => 'Très complexe',
                        default => $choice->name,
                    };
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tasks::class,
        ]);
    }
}
