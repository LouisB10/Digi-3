<?php
 
namespace App\Form;
 
use App\Entity\Tasks;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
 
class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('taskName', TextType::class, [
                'label' => 'Nom de la tâche',
            ])
            ->add('taskDescription', TextType::class, [
                'label' => 'Description de la tâche',
            ])
            ->add('taskType', ChoiceType::class, [
                'choices' => array_flip([
                    'Bug' => Tasks::TASK_TYPE_BUG,
                    'Feature' => Tasks::TASK_TYPE_FEATURE,
                    'Hightest' => Tasks::TASK_TYPE_HIGHTEST,
                ]),
                'placeholder' => 'Sélectionnez un type',
                'required' => true,
                'label' => 'Type de tâche',
            ])
            ->add('taskDateFrom', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début',
            ])
            ->add('taskDateTo', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin',
            ])
            ->add('taskStatus', ChoiceType::class, [
                'choices' => [
                    'En cours' => 'InProgress',
                    'Terminé' => 'Completed',
                    'Annulé' => 'Cancelled',
                ],
                'placeholder' => 'Sélectionnez un statut',
                'label' => 'Statut de la tâche',
            ])
            ->add('taskCategory', ChoiceType::class, [
                'choices' => [
                    'Développement' => 'Development',
                    'Testing' => 'Testing',
                    'Documentation' => 'Documentation',
                ],
                'placeholder' => 'Sélectionnez une catégorie',
                'label' => 'Catégorie de la tâche',
            ])
            ->add('taskAttachments', FileType::class, [
                'label' => 'Ajouter des fichiers',
                'multiple' => true,
                'mapped' => false,
                'required' => false,
            ]);
    }
 
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tasks::class,
        ]);
    }
}
 
 