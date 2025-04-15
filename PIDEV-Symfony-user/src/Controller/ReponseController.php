<?php

namespace App\Controller;

use App\Entity\Reponse;
use App\Form\ReponseType;
use App\Repository\ReponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reponse')]
final class ReponseController extends AbstractController
{
    // ✅ User routes first to avoid conflict with {id}
    #[Route('', name: 'app_reponse_index', methods: ['GET'])]
    public function index(ReponseRepository $reponseRepository): Response
    {
        return $this->render('reponse/index.html.twig', [
            'reponses' => $reponseRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_reponse_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Reponse $reponse): Response
    {
        return $this->render('reponse/show.html.twig', [
            'reponse' => $reponse,
        ]);
    }

   
    #[Route('/admin', name: 'app_reponse_admin_index', methods: ['GET'])]
    public function adminIndex(ReponseRepository $reponseRepository): Response
    {
        return $this->render('reponse/admin/index.html.twig', [
            'reponses' => $reponseRepository->findAll(),
        ]);
    }

    #[Route('/admin/new', name: 'app_reponse_admin_new', methods: ['GET', 'POST'])]
    public function adminNew(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reponse = new Reponse();
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reponse);
            $entityManager->flush();

            return $this->redirectToRoute('app_reponse_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reponse/admin/new.html.twig', [
            'reponse' => $reponse,
            'form' => $form,
        ]);
    }

    #[Route('/admin/{id}/edit', name: 'app_reponse_admin_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function adminEdit(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reponse_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reponse/admin/edit.html.twig', [
            'reponse' => $reponse,
            'form' => $form,
        ]);
    }

    #[Route('/admin/{id}', name: 'app_reponse_admin_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function adminShow(Reponse $reponse): Response
    {
        return $this->render('reponse/admin/show.html.twig', [
            'reponse' => $reponse,
        ]);
    }

    #[Route('/admin/{id}', name: 'app_reponse_admin_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function adminDelete(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $reponse->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reponse);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reponse_admin_index', [], Response::HTTP_SEE_OTHER);
    }
}

