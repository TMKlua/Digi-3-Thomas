<?php

namespace App\Form\Parameter;

use App\Entity\Parameters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
class AppFormParameterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('paramKey', TextType::class, [
                'label' => '',
            ])
            ->add('paramValue', TextType::class, [
                'label' => '',
            ])
            ->add('paramDateFrom', DateTimeType::class, [
                'widget' => 'single_text',
                'input'  => 'datetime',  // Assurez-vous que cela correspond au type attendu par votre entité
                'html5'  => true,
            ])
            ->add('paramDateTo', DateTimeType::class, [
                'widget' => 'single_text',
                'input'  => 'datetime',  // Assurez-vous que cela correspond au type attendu par votre entité
                'html5'  => true,
            ]); 
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Parameters::class,
        ]);
    }
}
