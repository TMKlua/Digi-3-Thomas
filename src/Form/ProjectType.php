<?php

namespace App\Form;

use App\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
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
            ->add('projectDescription', TextType::class, [
                'label' => 'Description',
            ])
            ->add('project_start_date', DateType::class, [
                'label' => 'Date de début prévue',
                'widget' => 'single_text', // Utilise un champ de type HTML5 date
                'required' => false,
            ])
            ->add('project_end_date', DateType::class, [
                'label' => 'Date de fin prévue',
                'widget' => 'single_text',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
