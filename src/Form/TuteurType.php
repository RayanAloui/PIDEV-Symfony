<?php

namespace App\Form;

use App\Entity\Tuteur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;

class TuteurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('cinT', TextType::class, [
            'label' => 'CIN',
            'attr' => [
                'maxlength' => 8,
                'pattern' => '^\d{8}$',
                'title' => 'Le CIN doit contenir exactement 8 chiffres.'
            ]
        ])
            ->add('nomT', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s-]+$/',
                        'message' => 'Le nom ne doit contenir que des lettres.'
                    ])
                ]
            ])
            ->add('prenomT', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s-]+$/',
                        'message' => 'Le prénom ne doit contenir que des lettres.'
                    ])
                ]
            ])
            ->add('telephoneT', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'maxlength' => 8,
                    'pattern' => '^\d{8}$',
                    'title' => 'Le numéro de téléphone doit contenir exactement 8 chiffres.'
                ]
            ])
            ->add('adresseT', TextType::class, [
                'label' => 'Adresse',
                'required' => false
            ])
            ->add('disponibilite', ChoiceType::class, [
                'label' => 'Disponibilité',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => false, // true = boutons radio, false = select
                'multiple' => false,
                'required' => true,
                'data' => $options['data']->getDisponibilite() ?? 'oui', // Valeur par défaut
            ])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('save', SubmitType::class, ['label' => 'Enregistrer']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tuteur::class,
        ]);
    }
}
