<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Form\ReclamationType;
use App\Entity\Reponse;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Services\SmsService;
use App\Services\BadWordsFilter;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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

    #[Route('/admin/statistics', name: 'app_reclamation_admin_statistics', methods: ['GET'])]
    public function statistics(ReclamationRepository $reclamationRepository): Response
    {
        $byType = $reclamationRepository->countReclamationsByType();
        
        // Prepare data for Chart.js
        $chartData = [
            'labels' => array_column($byType, 'type'),
            'counts' => array_map('intval', array_column($byType, 'count')),
        ];

        return $this->render('reclamation/admin/statistics.html.twig', [
            'byType' => $byType,
            'chartData' => json_encode($chartData),
        ]);
    }
    #[Route('/admin/{id}', name: 'app_reclamation_admin_show', methods: ['GET'])]
    public function adminShow(?Reclamation $reclamation): Response
    {
        if (!$reclamation) {
            $this->addFlash('error', 'Réclamation non trouvée.');
            return $this->redirectToRoute('app_reclamation_admin_index');
        }

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
public function new(Request $request, SessionInterface $session, EntityManagerInterface $entityManager, SmsService $smsService, BadWordsFilter $badWordsFilter): Response
{
    $reclamation = new Reclamation();
    $form = $this->createForm(ReclamationType::class, $reclamation);
    $form->handleRequest($request);
    $userEmail = $session->get('user_email');

    if ($form->isSubmitted() && $form->isValid()) {
        // Filter bad words from description
        $description = $badWordsFilter->filter($reclamation->getDescription());
        $reclamation->setDescription($description);

        // Set the email from session (if not already set)
        $reclamation->setMail($userEmail);

        // Persist the reclamation
        $entityManager->persist($reclamation);
        $entityManager->flush();    

        // Send SMS notification
        $message = "📬 Nouvelle réclamation de type '{$reclamation->getTypereclamation()}' reçue.\n";
        $message .= "📧 Email: {$reclamation->getMail()}\n";
        $message .= "📝 Description: " . substr($reclamation->getDescription(), 0, 100) . '...';

        $smsService->sendSms('+21699058580', $message);

        $this->addFlash('success', 'Reclamation submitted successfully!');

        return $this->redirectToRoute('app_front', [], Response::HTTP_SEE_OTHER);
    }

    // ⬇️ Here: Fetch all Reclamations where mail = session user_email
    $userReclamations = $entityManager->getRepository(Reclamation::class)->findBy([
        'mail' => $userEmail,
    ]);
    $reclamationId = $userReclamations[0]->getId();
    
    // Initialize an array to store all responses
$userReponses = [];

// Loop through each reclamation and fetch the responses
foreach ($userReclamations as $reclamation) {
    $reclamationId = $reclamation->getId();
    
    // Fetch responses for the current reclamation
    $responses = $entityManager->getRepository(Reponse::class)->findBy([
        'indice' => $reclamationId,
    ]);
    
    // Add the responses to the userReponses array
    $userReponses[$reclamationId] = $responses;
}
    

    return $this->render('reclamation/new.html.twig', [
        'reclamation' => $reclamation,
        'form' => $form,
        'user_email' => $userEmail,
        'user_reclamations' => $userReclamations, 
        'user_reponses'=>$userReponses,
        
    ]);
}


    #[Route('/{id}', name: 'app_reclamation_show', methods: ['GET'])]
    public function show(?Reclamation $reclamation): Response
    {
        if (!$reclamation) {
            $this->addFlash('error', 'Réclamation non trouvée.');
            return $this->redirectToRoute('app_reclamation_index');
        }

        return $this->render('reclamation/show.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reclamation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ?Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        if (!$reclamation) {
            $this->addFlash('error', 'Réclamation non trouvée.');
            return $this->redirectToRoute('app_reclamation_index');
        }

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
    public function delete(Request $request, ?Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        if (!$reclamation) {
            $this->addFlash('error', 'Réclamation non trouvée.');
            return $this->redirectToRoute('app_reclamation_index');
        }

        if ($this->isCsrfTokenValid('delete' . $reclamation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/export/pdf', name: 'app_reclamation_admin_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, ReclamationRepository $reclamationRepository, \Knp\Snappy\Pdf $knpSnappyPdf): Response
    {
        $query = $request->query->get('q');
        $type = $request->query->get('type');
        $sort = $request->query->get('sort', 'date');
        $order = $request->query->get('order', 'DESC');

        $reclamations = $reclamationRepository->searchReclamations($query, $sort, $order, $type);

        $html = $this->renderView('reclamation/reclamation_pdf.html.twig', [
            'reclamations' => $reclamations,
        ]);

        return new PdfResponse(
            $knpSnappyPdf->getOutputFromHtml($html),
            'reclamations-report.pdf',
            'application/pdf',
            'attachment'
        );
    }


   

}