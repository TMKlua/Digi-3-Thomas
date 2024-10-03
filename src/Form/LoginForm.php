<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class LoginForm extends AbstractType
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
        ->add('remember_me', CheckboxType::class, [
            'label' => 'Rester connecté',
            'required' => false, // La case à cocher n'est pas obligatoire
        ])
        ->add('login', SubmitType::class, [
            'label' => 'Se connecter'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
