<?php

namespace App\Form;

use App\Entity\Cour;
use App\Entity\Tuteur;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CourType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du cours',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le titre du cours est obligatoire.',
                    ])
                ]
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le contenu est obligatoire.',
                    ])
                ]
            ])
            ->add('imageC', FileType::class, [
                'label' => 'Image du cours',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG ou PNG)',
                    ])
                ],
            ])
            ->add('tuteur', EntityType::class, [
                'class' => Tuteur::class,
                'choice_label' => function (Tuteur $tuteur) {
                    return $tuteur->getNomT() . ' ' . $tuteur->getPrenomT();
                },
                'label' => 'Tuteur'
            ])
            ->add('save', SubmitType::class, ['label' => 'Enregistrer']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cour::class,
        ]);
    }
}
