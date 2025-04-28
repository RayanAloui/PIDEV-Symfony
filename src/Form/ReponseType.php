<?php

namespace App\Form;

use App\Entity\Reponse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class ReponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'minlength' => 10,
                    'maxlength' => 1000,
                    'rows' => 5
                ],
                'label' => 'Description',
                'help' => '10 à 1000 caractères'
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'max' => (new \DateTime())->format('Y-m-d') // Ensures date input is not in the future
                ],
                'label' => 'Date'
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'En attente' => 'En attente',
                    'Traité' => 'Traité',
                    'Rejeté' => 'Rejeté'
                ],
                'attr' => ['class' => 'form-select'],
                'label' => 'Statut',
                'placeholder' => 'Sélectionnez un statut'
            ])
            ->add('indice', null, [
                'attr' => [
                    'readonly' => true,
                    'class' => 'form-control bg-light',
                    'placeholder' => 'Optional index/number'
                ],
            ]);
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reponse::class,
        ]);
    }
}
