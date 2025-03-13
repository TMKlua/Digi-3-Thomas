<?php

namespace App\Form;

use App\Entity\ManagerProject;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ManagerProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du projet',
                'required' => true, // Le champ est requis
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'required' => false, // Le champ description n'est pas requis
            ])
            ->add('startDateForecast', DateType::class, [
                'label' => 'Date de début prévue',
                'widget' => 'single_text',
                'required' => true, // Le champ est requis
                'attr' => [
                    'min' => (new \DateTime())->format('Y-m-d'), // Fixe la date minimale à aujourd'hui
                ],
            ])
            ->add('endDateForecast', DateType::class, [
                'label' => 'Date de fin prévue',
                'widget' => 'single_text',
                'required' => false, // Le champ date de fin n'est pas requis
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ManagerProject::class,
        ]);
    }
}
