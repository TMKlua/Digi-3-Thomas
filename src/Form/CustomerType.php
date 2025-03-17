<?php

namespace App\Form;

use App\Entity\Customers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('customerName', TextType::class, [
                'label' => 'Nom du client',
                'attr' => ['placeholder' => 'Nom du client']
            ])
            ->add('customerEmail', EmailType::class, [
                'label' => 'Email du client',
                'attr' => ['placeholder' => 'Email']
            ])
            ->add('customerPhone', TelType::class, [
                'label' => 'Téléphone du client',
                'attr' => ['placeholder' => 'Téléphone']
            ])
            ->add('customerAddressStreet', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['placeholder' => 'Adresse'],
                'required' => false,
            ])
            ->add('customerAddressZipcode', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['placeholder' => 'Code postal'],
                'required' => false,
            ])
            ->add('customerAddressCity', TextType::class, [
                'label' => 'Ville',
                'attr' => ['placeholder' => 'Ville'],
                'required' => false,
            ])
            ->add('customerAddressCountry', TextType::class, [
                'label' => 'Pays',
                'attr' => ['placeholder' => 'Pays'],
                'required' => false,
            ])
            ->add('customerVAT', TextType::class, [
                'label' => 'Numéro de TVA',
                'attr' => ['placeholder' => 'Numéro de TVA'],
                'required' => false,
            ])
            ->add('customerSIREN', TextType::class, [
                'label' => 'Numéro SIREN',
                'attr' => ['placeholder' => 'Numéro SIREN'],
                'required' => false,
            ])
            ->add('customerReference', TextType::class, [
                'label' => 'Référence client',
                'attr' => ['placeholder' => 'Référence client'],
                'required' => false,
            ])
            ->add('customerDateFrom', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('customerDateTo', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Customers::class,
        ]);
    }
}
