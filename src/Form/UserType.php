<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Name should not be blank.']),
                    new Length(['min' => 2, 'max' => 50]),
                    new Regex(['pattern' => '/^[a-zA-Z]+$/', 'message' => 'Name must contain only letters.'])
                ]
            ])
            ->add('surname', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Surname should not be blank.']),
                    new Length(['min' => 2, 'max' => 50]),
                    new Regex(['pattern' => '/^[a-zA-Z]+$/', 'message' => 'Surname must contain only letters.'])
                ]
            ])
            ->add('telephone', TelType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Telephone number should not be blank.']),
                    new Regex(['pattern' => '/^\d{8}$/', 'message' => 'Telephone must contain exactly 8 digits.'])
                ]
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Email should not be blank.']),
                    new Email(['message' => 'Please enter a valid email address.'])
                ]
            ])
            ->add('password', PasswordType::class, [
                'mapped' => false,  // This means we won't pre-fill the password field with the existing value
                'constraints' => [
                    new Length(['min' => 6]),
                    new Regex(['pattern' => '/^(?=.*[A-Za-z])(?=.*\d).{6,}$/', 'message' => 'Password must contain both letters and numbers.'])
                ],
                'required' => false, // Makes the password field optional
                'attr' => ['placeholder' => 'Leave blank to keep current password']
            ])
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'client' => 'client',
                    'admin' => 'admin',
                    'tuteur' => 'tuteur',
                    'orphelin' => 'orphelin',
                ],
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
