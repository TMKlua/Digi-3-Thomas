<?php

namespace App\Form;

use App\Entity\AppEntityParameter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType; // Importez le type HiddenType 
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppFormParameterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', HiddenType::class) // Champ caché pour l'ID
            ->add('cle')
            ->add('valeur')

            ->add('DateDebut', DateTimeType::class, [
                'widget' => 'single_text', // Utiliser un champ de texte unique
                'input'  => 'datetime',    // Le format d'entrée doit être un objet DateTime
            ])
            ->add('DateFin', DateTimeType::class, [
                'widget' => 'single_text',  
                'input'  => 'datetime',
            ])
            ->add('utilisateur')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AppEntityParameter::class,
        ]);
    }
}
