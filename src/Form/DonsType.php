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

class DonsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant', MoneyType::class, [
                'label' => 'Montant du Don',
                'currency' => 'TND',
                'attr' => ['placeholder' => 'Entrez le montant du don']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
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
                'placeholder' => 'Sélectionnez un type de don'
            ])
            ->add('dateDon', DateType::class, [  // Utilisez le nom exact de la propriété
                'label' => 'Date du Don',
                'widget' => 'single_text',
                'attr' => ['type' => 'date']
            ])
            ->add('idEvent', EntityType::class, [  // Utilisez le nom exact de la propriété
                'class' => Events::class,
                'choice_label' => 'nom',
                'label' => 'Événement Associé',
                'placeholder' => 'Sélectionnez un événement',
                'required' => false
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dons::class,
        ]);
    }
}