<?php
namespace App\Form;

use App\Entity\Activite;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

class ActiviteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nom de l\'activité est requis.',
                    ]),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Le nom de l\'activité ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('categorie', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La catégorie est requise.',
                    ]),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'La catégorie ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('duree', IntegerType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La durée est requise.',
                    ]),
                    new Assert\Type([
                        'type' => 'integer',
                        'message' => 'La durée doit être un nombre entier.',
                    ]),
                ],
            ])
            ->add('nom_du_tuteur', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nom du tuteur est requis.',
                    ]),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Le nom du tuteur ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            // Champ de date activite avec un format spécifique
            ->add('date_activite', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La date de l\'activité est requise.',
                    ]),
                     //Validation du format "dd/MM/yyyy"
                    new Assert\Regex([
                        'pattern' => '/^\d{2}\/\d{2}\/\d{4}$/',
                        'message' => 'Le format de la date doit être jj/mm/yyyy.',
                    ]),
                ],
            ])
            ->add('lieu', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le lieu est requis.',
                    ]),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Le lieu ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('description', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La description est requise.',
                    ]),
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Active' => 'Active',
                    'Completed' => 'Completed',
                    'Inactive' => 'Inactive',
                ],
                'expanded' => false,  
                'multiple' => false, 
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activite::class,
        ]);
    }
}
