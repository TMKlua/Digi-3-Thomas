<?php

namespace App\Form\Parameter;

use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('searchTerm', TextType::class, [
                'label' => false, // Pas besoin de label pour une barre de recherche
                'attr' => [
                    'placeholder' => 'Rechercher',
                ]
            ])
            ->add('showAll', CheckboxType::class, [
                'label'    => 'Historique',
                'required' => false,
            ])
            ->add('dateSelect', DateType::class, [
                'widget' => 'single_text',  // Permet d'afficher un calendrier
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Choisir une date'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
