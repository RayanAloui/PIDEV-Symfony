<?php

namespace App\Controller;

use App\Entity\Orphelin;
use App\Form\OrphelinType;
use App\Repository\OrphelinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/crud/orphelin')]
class OrphelinController extends AbstractController
{
    #[Route('/list', name: 'app_crud_orphelin', methods: ['GET'])]
    public function index(OrphelinRepository $orphelinRepository): Response
    {
        return $this->render('orphelin/list.html.twig', [
            'orphelins' => $orphelinRepository->findAll(),
        ]);
    }

    #[Route('/add', name: 'app_crud_orphelin_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $orphelin = new Orphelin();
        $form = $this->createForm(OrphelinType::class, $orphelin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($orphelin);
            $entityManager->flush();
            $this->addFlash('success', 'Orphelin ajouté avec succès.');
            return $this->redirectToRoute('app_crud_orphelin');
        }

        return $this->render('orphelin/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'app_crud_orphelin_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, OrphelinRepository $orphelinRepository, int $id): Response
    {
        $orphelin = $orphelinRepository->find($id);

        if (!$orphelin) {
            throw $this->createNotFoundException("Orphelin non trouvé !");
        }

        $form = $this->createForm(OrphelinType::class, $orphelin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_crud_orphelin');
        }

        return $this->render('orphelin/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_crud_orphelin_delete')]
    public function delete(EntityManagerInterface $entityManager, OrphelinRepository $orphelinRepository, int $id): Response
    {
        $orphelin = $orphelinRepository->find($id);

        if (!$orphelin) {
            throw $this->createNotFoundException("Orphelin non trouvé !");
        }

        $entityManager->remove($orphelin);
        $entityManager->flush();

        return $this->redirectToRoute('app_crud_orphelin');
    }
}
