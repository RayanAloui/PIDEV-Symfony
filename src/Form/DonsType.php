<?php

namespace App\Form;

use App\Entity\Dons;
use App\Entity\Events;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DonsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant', MoneyType::class, [
                'label' => 'Montant du Don',
                'currency' => 'TND',
                'empty_data' => '0',
                'attr' => ['placeholder' => 'Entrez le montant du don'],
                'constraints' => [
                new Assert\NotBlank(['message' => 'Le montant du don est obligatoire.']),
                new Assert\Positive(['message' => 'Le montant doit être un nombre positif.']),
    ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'empty_data' => '',
                'attr' => ['placeholder' => 'Ajoutez une description (facultatif)']
            ])
            ->add('typeDon', ChoiceType::class, [  // Utilisez le nom exact de la propriété
                'label' => 'Type de Don',
                'choices' => [
                    'Argent' => 'Argent',
                    'Vêtements' => 'Vêtements',
                    'Nourriture' => 'Nourriture',
                    'Autre' => 'Autre',
                    
                ],
                //'placeholder' => 'Sélectionnez un type de don',
                'constraints' => [new Assert\NotBlank(message: "Le type du don est obligatoire.")],
                
            ])
            ->add('dateDon', DateType::class, [  // Utilisez le nom exact de la propriété
                'label' => 'Date du Don',
                'widget' => 'single_text',
                'attr' => ['type' => 'date'],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: "La date du don est obligatoire."),
                    new Assert\LessThanOrEqual("today", message: "La date du don ne peut pas être dans le futur.")
    ]
            ])
            ->add('idEvent', EntityType::class, [  // Utilisez le nom exact de la propriété
                'class' => Events::class,
                'choice_label' => 'nom',
                'label' => 'Événement Associé',
                'placeholder' => 'Sélectionnez un événement',
                'required' => false
            ]);

            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dons::class,
        ]);
    }
}