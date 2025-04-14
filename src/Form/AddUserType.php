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
use Symfony\Component\Validator\Constraints as Assert;

class AddUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Name cannot be empty.']),
                    new Length(['min' => 2, 'max' => 50]),
                    new Regex(['pattern' => '/^[a-zA-Z]+$/', 'message' => 'Name must contain only letters.'])
                ],
                'attr' => ['novalidate' => 'novalidate']
            ])
            ->add('surname', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Surname cannot be empty.']),
                    new Length(['min' => 2, 'max' => 50]),
                    new Regex(['pattern' => '/^[a-zA-Z]+$/', 'message' => 'Surname must contain only letters.'])
                ],
                'attr' => ['novalidate' => 'novalidate']
            ])
            ->add('telephone', TelType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Telephone cannot be empty.']),
                    new Regex(['pattern' => '/^\d{8}$/', 'message' => 'Telephone must contain exactly 8 digits.'])
                ],
                'attr' => ['novalidate' => 'novalidate']
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Email cannot be empty.']),
                    new Email(['message' => 'Please enter a valid email address.'])
                ],
                'attr' => ['novalidate' => 'novalidate']
            ])
            ->add('password', PasswordType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'min' => 6,
                        'minMessage' => 'Password must be at least {{ limit }} characters long.', // Use 'minMessage' instead of 'message'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^(?=.*[A-Za-z])(?=.*\d).{6,}$/',
                        'message' => 'Password must contain both letters and numbers.',
                    ]),
                ],
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
                'attr' => ['novalidate' => 'novalidate']
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Add User',
                'attr' => ['class' => 'btn btn-primary', 'novalidate' => 'novalidate']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
