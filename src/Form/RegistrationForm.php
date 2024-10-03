<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class RegistrationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('email', TextType::class, [
            'label' => 'E-mail',
            'attr' => [
                'placeholder' => 'Entrez votre e-mail' // Ajoute un placeholder ici
            ]
        ])
        ->add('password', PasswordType::class, [
            'label' => 'Mot de passe',
            'attr' => [
                'placeholder' => 'Entrez votre mot de passe' // Ajoute un placeholder ici
            ]
        ])
        ->add('passwordConfirm', PasswordType::class, [
            'label' => 'Confirmez votre mot de passe',
            'attr' => [
                'placeholder' => 'Veuillez confirmer votre mot de passe' // Ajoute un placeholder ici
            ]
        ])
        ->add('register', SubmitType::class, [
            'label' => 'S’inscrire'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
