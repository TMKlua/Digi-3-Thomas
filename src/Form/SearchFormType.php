<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
                    'placeholder' => 'Rechercher un paramètre',
                ]
            ])
            ->add('showAll', CheckboxType::class, [
                'label'    => 'Historique',
                'required' => false,
            ])
            ->add('date_select', ChoiceType::class, [
                'label' => false, // Pas de label pour le select
                'choices' => [
                    'Date' => null,  // Option par défaut vide
                    'Aujourd\'hui' => 'today',
                    'Cette semaine' => 'this_week',
                    'Ce mois' => 'this_month',
                    'Cette année' => 'this_year',
                    // Ajoutez d'autres options si nécessaire
                ],
                'placeholder' => 'Choisir une date', // Placeholder
                'required' => false, // Permettre à l'utilisateur de ne pas sélectionner de date
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
