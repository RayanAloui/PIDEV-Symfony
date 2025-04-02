<?php

namespace App\Controller;

use App\Entity\Dons;
use App\Form\DonsType;
use App\Repository\DonsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/crud/dons')]
class DonsController extends AbstractController
{
    #[Route('/list', name: 'app_crud_dons', methods: ['GET'])]
    public function list(Request $request, DonsRepository $repository): Response
    {
        $dons = $repository->findAll();
        return $this->render('dons/list.html.twig', [
            'dons' => $dons,
        ]);
    }

    #[Route('/add', name: 'app_crud_dons_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $don = new Dons();
        $form = $this->createForm(DonsType::class, $don);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($don);
            $entityManager->flush();
            $this->addFlash('success', 'Don ajouté avec succès.');
            return $this->redirectToRoute('app_crud_dons');
        }

        return $this->render('dons/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'app_crud_dons_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, DonsRepository $donsRepository, int $id): Response
    {
        $don = $donsRepository->find($id);
        if (!$don) {
            throw $this->createNotFoundException("Don non trouvé !");
        }
        
        $form = $this->createForm(DonsType::class, $don);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_crud_dons');
        }

        return $this->render('dons/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_crud_dons_delete')]
    public function delete(EntityManagerInterface $entityManager, DonsRepository $donsRepository, int $id): Response
    {
        $don = $donsRepository->find($id);
        if (!$don) {
            throw $this->createNotFoundException("Don non trouvé !");
        }

        $entityManager->remove($don);
        $entityManager->flush();

        return $this->redirectToRoute('app_crud_dons');
    }

   
}
