<?php

namespace App\Form;

use App\Entity\Participant;
use App\Entity\User;
use App\Entity\Activite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getName() . ' ' . $user->getSurname();
                },
                'label' => 'Utilisateur',
            ])
            ->add('activite', EntityType::class, [
                'class' => Activite::class,
                'choice_label' => 'nom',
                'label' => 'Activité',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}