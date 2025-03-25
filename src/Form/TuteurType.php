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

class TuteurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cinT', TextType::class, ['label' => 'CIN'])
            ->add('nomT', TextType::class, ['label' => 'Nom'])
            ->add('prenomT', TextType::class, ['label' => 'Prénom'])
            ->add('telephoneT', TextType::class, ['label' => 'Téléphone', 'required' => false])
            ->add('adresseT', TextType::class, ['label' => 'Adresse', 'required' => false])
            ->add('disponibilite', TextType::class, ['label' => 'Disponibilité'])
            ->add('disponibilite', ChoiceType::class, [
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => false, // Mettre true si tu veux des boutons radio au lieu d'un select
                'multiple' => false,
                'required' => true,
                'data' => $options['data']->getDisponibilite() ?? 'oui', // Assure une valeur par défaut
            ])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('save', SubmitType::class, ['label' => 'Enregistrer']);;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tuteur::class,
        ]);
    }
}
