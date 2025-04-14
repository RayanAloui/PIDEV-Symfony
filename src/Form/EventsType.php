<?php

namespace App\Form;

use App\Entity\Events;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EventsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'événement',
                'attr' => [
                    'placeholder' => 'Entrez le nom de l\'événement',
                    'minlength' => 3,
                    'maxlength' => 100
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nom de l\'événement est obligatoire'
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z0-9À-ÿ\s\-\']+$/',
                        'message' => 'Le nom ne peut contenir que des lettres, chiffres et espaces'
                    ])
                ]
            ])
            ->add('date_event', DateType::class, [
                'label' => 'Date de l\'événement',
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['type' => 'date'],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La date de l\'événement est obligatoire'
                    ]),
                    new Assert\GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date de l\'événement doit être aujourd\'hui ou dans le futur'
                    ])
                ]
            ])
            
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'attr' => [
                    'placeholder' => 'Entrez le lieu de l\'événement',
                    'minlength' => 3,
                    'maxlength' => 100
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le lieu est obligatoire'
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'Le lieu doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le lieu ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ajoutez une description(facultatif) ',
                    'minlength' => 10,
                    'maxlength' => 1000,
                    'rows' => 5
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Events::class,
        ]);

    }
}