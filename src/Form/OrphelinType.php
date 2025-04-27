<?php

namespace App\Form;

use App\Entity\Orphelin;
use App\Entity\Tuteur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class OrphelinType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomO', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Entrez le nom']
            ])
            ->add('prenomO', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Entrez le prénom']
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de Naissance',
                'widget' => 'single_text',
                'attr' => ['type' => 'date']
            ])
            ->add('sexe', ChoiceType::class, [
                'label' => 'Sexe',
                'choices' => [
                    'Masculin' => 'M',
                    'Féminin' => 'F',
                ],
                'data' => 'M'
            ])
            ->add('situationScolaire', ChoiceType::class, [
                'label' => 'Situation Scolaire',
                'choices' => [
                    'Primaire' => 'Primaire',
                    'Collège' => 'Collège',
                    'Lycée' => 'Lycée',
                    'Université' => 'Université',
                    'Aucun' => 'Aucun'
                ],
                'placeholder' => 'Sélectionner une option'
            ])
            ->add('tuteur', EntityType::class, [
                'class' => Tuteur::class,
                'choice_label' => function(Tuteur $tuteur) {
                    return $tuteur->getNomT() . ' ' . $tuteur->getPrenomT();  
                },
                'label' => 'Tuteur'
            ])            
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Orphelin::class,
        ]);
    }
}
