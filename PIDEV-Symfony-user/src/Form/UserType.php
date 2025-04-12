<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('surname')
            ->add('telephone')
            ->add('email')
            ->add('password')
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'client' => 'client',
                    'admin' => 'admin',
                    'tuteur' => 'tuteur',
                    'orphelin' => 'orphelin',
                ],
                'expanded' => false,  // This makes it a dropdown instead of radio buttons
                'multiple' => false,  // Ensures only one choice can be selected
            ])
            
            ->add('image')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
