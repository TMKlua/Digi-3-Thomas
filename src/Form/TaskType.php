<?php

namespace App\Form;

use App\Entity\Tasks;
use App\Entity\User;
use App\Enum\TaskStatus;
use App\Enum\TaskPriority;
use App\Enum\TaskComplexity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('taskName', TextType::class, [
                'label' => 'Nom de la tâche',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('taskDescription', TextareaType::class, [
                'label' => 'Description de la tâche',
                'attr' => ['class' => 'form-control', 'rows' => 4],
                'required' => false,
            ])
            ->add('taskStatus', EnumType::class, [
                'class' => TaskStatus::class,
                'label' => 'Statut',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('taskPriority', EnumType::class, [
                'class' => TaskPriority::class,
                'label' => 'Priorité',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('taskComplexity', EnumType::class, [
                'class' => TaskComplexity::class,
                'label' => 'Complexité',
                'attr' => ['class' => 'form-select'],
                'required' => false,
            ])
            ->add('taskAssignedTo', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getUserFirstName() . ' ' . $user->getUserLastName();
                },
                'label' => 'Assigné à',
                'attr' => ['class' => 'form-select'],
                'required' => false,
            ])
            ->add('taskStartDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('taskTargetDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date cible',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('taskEndDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin',
                'attr' => ['class' => 'form-control'],
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
