<?php

namespace App\Controller;

use App\Entity\Cour;
use App\Form\CourType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/crud/cours')]
class CourController extends AbstractController
{
    #[Route('/list', name: 'app_crud_cours', methods: ['GET'])]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $cours = $entityManager->getRepository(Cour::class)->findAll();

        return $this->render('cour/list.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/add', name: 'app_crud_cours_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cour = new Cour();
        $form = $this->createForm(CourType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageC')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($this->getParameter('cours_images_directory'), $newFilename);
                    $cour->setImageC($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $entityManager->persist($cour);
            $entityManager->flush();

            $this->addFlash('success', 'Cours ajouté avec succès !');
            return $this->redirectToRoute('app_crud_cours');
        }

        return $this->render('cour/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'app_crud_cours_edit')]
    public function edit(Request $request, Cour $cours, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CourType::class, $cours);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageC')->getData();

            if ($imageFile) {
                // Supprimer l'ancienne image (optionnel, si tu veux éviter trop de fichiers inutiles)
                if ($cours->getImageC()) {
                    $oldImagePath = $this->getParameter('cours_images_directory') . '/' . $cours->getImageC();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                // Sauvegarder la nouvelle image
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('cours_images_directory'), $newFilename);
                $cours->setImageC($newFilename);
            }

            $entityManager->flush();
            return $this->redirectToRoute('app_crud_cours');
        }

        return $this->render('cour/edit.html.twig', [
            'form' => $form->createView(),
            'cours' => $cours
        ]);
    }

    #[Route('/delete/{id}', name: 'app_crud_cours_delete')]
    public function supprimer(Cour $cours, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($cours);
        $entityManager->flush();

        return $this->redirectToRoute('app_crud_cours');
    }

    #[Route('/voir/{id}', name: 'app_crud_cours_voir')]
    public function voir(Cour $cours): Response
    {
        return $this->render('cour/voir.html.twig', [
            'cours' => $cours,
        ]);
    }
}
