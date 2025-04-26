<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Services\SmsService;
use App\Services\BadWordsFilter; // Add this import

#[Route('/reclamation')]
final class ReclamationController extends AbstractController
{
    #[Route('/admin', name: 'app_reclamation_admin_index', methods: ['GET'])]
    public function adminindex(Request $request, ReclamationRepository $reclamationRepository): Response
    {
        $query = $request->query->get('q');
        $type = $request->query->get('type');
        $sort = $request->query->get('sort', 'date');
        $order = $request->query->get('order', 'DESC');

        $reclamations = $reclamationRepository->searchReclamations($query, $sort, $order, $type);

        return $this->render('reclamation/admin/index.html.twig', [
            'reclamations' => $reclamations,
            'query' => $query,
            'type' => $type,
        ]);
    }

    #[Route('/admin/{id}', name: 'app_reclamation_admin_show', methods: ['GET'])]
    public function adminShow(Reclamation $reclamation): Response
    {
        return $this->render('reclamation/admin/show.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route(name: 'app_reclamation_index', methods: ['GET'])]
    public function index(ReclamationRepository $reclamationRepository): Response
    {
        return $this->render('reclamation/index.html.twig', [
            'reclamations' => $reclamationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SmsService $smsService, BadWordsFilter $badWordsFilter): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Filter bad words from description and subject
            $description = $badWordsFilter->filter($reclamation->getDescription());
            $reclamation->setDescription($description);
           

            // Persist the reclamation
            $entityManager->persist($reclamation);
            $entityManager->flush();

            // Send SMS notification
            $message = "📬 Nouvelle réclamation de type '{$reclamation->getTypereclamation()}' reçue.\n";
            $message .= "📧 Email: {$reclamation->getMail()}\n";
            $message .= "📝 Description: " . substr($reclamation->getDescription(), 0, 100) . '...';
            
            $smsService->sendSms('+21655732015', $message);
            
            $this->addFlash('success', 'Reclamation submitted successfully!');

            return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reclamation/new.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reclamation_show', methods: ['GET'])]
    public function show(Reclamation $reclamation): Response
    {
        return $this->render('reclamation/show.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reclamation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reclamation/edit.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $reclamation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
    }
}