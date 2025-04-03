<?php

namespace App\Form;

use App\Entity\Rating;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class RatingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('note', IntegerType::class, [
            'constraints' => [new Range(['min' => 1, 'max' => 5])],
            'attr' => ['min' => 1, 'max' => 5],
        ])
        /*->add('note', NumberType::class, [
            'label' => 'Note (1 à 5)',
            'attr' => ['min' => 1, 'max' => 5],
        ])*/
        ->add('save', SubmitType::class, ['label' => 'Noter']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rating::class,
        ]);
    }
}
