<?php
 
namespace App\Form;
 
use App\Entity\Tasks;
use App\Enum\TaskComplexity;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
 
class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('taskName', TextType::class, [
                'label' => 'Nom de la tâche',
                'required' => true,
            ])
            ->add('taskDescription', TextareaType::class, [
                'label' => 'Description de la tâche',
                'required' => true,
            ])
            ->add('taskType', TextType::class, [
                'label' => 'Type de tâche',
                'required' => true,
            ])
            ->add('taskStartDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début',
                'required' => false,
            ])
            ->add('taskEndDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin',
                'required' => false,
            ])
            ->add('taskTargetDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date cible',
                'required' => false,
            ])
            ->add('taskComplexity', EnumType::class, [
                'class' => TaskComplexity::class,
                'choice_label' => fn (TaskComplexity $complexity) => $complexity->value,
                'placeholder' => 'Sélectionnez une complexité',
                'label' => 'Complexité',
                'required' => true,
            ])
            ->add('taskPriority', EnumType::class, [
                'class' => TaskPriority::class,
                'choice_label' => fn (TaskPriority $priority) => $priority->value,
                'placeholder' => 'Sélectionnez une priorité',
                'label' => 'Priorité',
                'required' => true,
            ])
            ->add('taskStatus', EnumType::class, [
                'class' => TaskStatus::class,
                'choice_label' => fn (TaskStatus $status) => $status->getLabel(),
                'placeholder' => 'Sélectionnez un statut',
                'label' => 'Statut de la tâche',
                'required' => true,
            ]);
    }
 
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tasks::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'task_form',
        ]);
    }
}
 
 