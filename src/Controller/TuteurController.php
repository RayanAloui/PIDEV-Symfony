<?php

namespace App\Controller;

use App\Entity\Tuteur;
use App\Repository\TuteurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\TuteurType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

#[Route('/crud/tuteur')]
class TuteurController extends AbstractController
{
    #[Route('/list', name: 'app_crud_tuteur', methods: ['GET'])]
    public function list(TuteurRepository $repository): Response
    {
        $tuteurs = $repository->findAll();
        return $this->render('tuteur/list.html.twig', [
            'tuteurs' => $tuteurs,
        ]);
    }

    #[Route('/add', name: 'app_crud_tuteur_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tuteur = new Tuteur();
        $form = $this->createForm(TuteurType::class, $tuteur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tuteur);
            $entityManager->flush();
            return $this->redirectToRoute('app_crud_tuteur');
        }

        return $this->render('tuteur/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'app_crud_tuteur_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, TuteurRepository $tuteurRepository, int $id): Response
    {
        $tuteur = $tuteurRepository->find($id);

        if (!$tuteur) {
            throw $this->createNotFoundException("Tuteur non trouvé !");
        }

        $form = $this->createForm(TuteurType::class, $tuteur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_crud_tuteur');
        }

        return $this->render('tuteur/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_crud_tuteur_delete')]
    public function delete(EntityManagerInterface $entityManager, TuteurRepository $tuteurRepository, int $id): Response
    {
        $tuteur = $tuteurRepository->find($id);

        if (!$tuteur) {
            throw $this->createNotFoundException("Tuteur non trouvé !");
        }

        $entityManager->remove($tuteur);
        $entityManager->flush();

        return $this->redirectToRoute('app_crud_tuteur');
    }
}
