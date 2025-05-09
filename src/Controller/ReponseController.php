<?php

namespace App\Controller;

use App\Services\EmailService;
use App\Entity\Reclamation;
use App\Entity\Reponse;
use App\Form\ReponseType;
use App\Repository\ReponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse; // Added import

#[Route('/reponse')]
final class ReponseController extends AbstractController
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

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
    public function adminIndex(Request $request, ReponseRepository $reponseRepository): Response
    {
        $query = $request->query->get('query', '');
        $sortField = $request->query->get('sortField', 'date');
        $sortOrder = $request->query->get('sortOrder', 'DESC');

        $reponses = $reponseRepository->searchReponses($query, $sortField, $sortOrder);

        return $this->render('reponse/admin/index.html.twig', [
            'reponses' => $reponses,
            'query' => $query,
            'sortField' => $sortField,
            'sortOrder' => $sortOrder,
        ]);
    }
    #[Route('/admin/statistics', name: 'app_reponse_admin_statistics', methods: ['GET'])]
    public function statistics(ReponseRepository $reponseRepository): Response
    {
        $byStatus = $reponseRepository->countReponsesByStatut();
        $chartData = [
            'labels' => array_column($byStatus, 'statut'),
            'counts' => array_map('intval', array_column($byStatus, 'reponseCount')),
            'averageIndices' => array_map('floatval', array_column($byStatus, 'averageIndice')),
        ];
        return $this->render('reponse/admin/statistics.html.twig', [
            'byStatus' => $byStatus,
            'chartData' => json_encode($chartData),
        ]);
    }
    #[Route('/admin/new', name: 'app_reponse_admin_new', methods: ['GET', 'POST'])]
public function adminNew(Request $request, EntityManagerInterface $entityManager): Response
{
    $reponse = new Reponse();

    // 🔥 Récupérer l'ID de la réclamation depuis l'URL (paramètre GET)
    $reclamationId = $request->query->get('id');

    if ($reclamationId !== null) {
        $reponse->setIndice($reclamationId); // Remplir l'indice avec l'id de réclamation
    }

    $form = $this->createForm(ReponseType::class, $reponse);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($reponse);
        $entityManager->flush();

        // Envoi d'email après validation
        $recipient = 'sarahbelhadej19@gmail.com';
        $subject = 'Nouvelle réponse à votre réclamation';
        $content = sprintf(
            "<h2>Bonjour,</h2>
            <p>Une réponse a été ajoutée à votre réclamation :</p>
            <blockquote>%s</blockquote>
            <p>Merci de nous avoir contactés.</p>
            <p><em>L'équipe OrphenCare</em></p>",
            nl2br($reponse->getDescription())
        );

        $this->emailService->sendEmail($recipient, $subject, $content);

        $this->addFlash('success', 'Réponse enregistrée et email envoyé.');
       
        return $this->redirectToRoute('app_reclamation_admin_index', [], Response::HTTP_SEE_OTHER);
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

    #[Route('/admin/export/pdf', name: 'app_reponse_admin_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, ReponseRepository $reponseRepository, \Knp\Snappy\Pdf $knpSnappyPdf): Response
    {
        $query = $request->query->get('query', '');
        $sortField = $request->query->get('sortField', 'date');
        $sortOrder = $request->query->get('sortOrder', 'DESC');

        $reponses = $reponseRepository->searchReponses($query, $sortField, $sortOrder);

        $html = $this->renderView('reponse/reponse_pdf.html.twig', [
            'reponses' => $reponses,
        ]);

        return new PdfResponse(
            $knpSnappyPdf->getOutputFromHtml($html),
            'reponses-report.pdf',
            'application/pdf',
            'attachment'
     
        );
    }


    #[Route('/admin/reponse/{id}', name: 'app_reponse_reclamation', methods: ['GET'])]
public function showReponseByReclamation(int $id, EntityManagerInterface $entityManager): Response
{
    // Chercher la réponse où l'indice correspond à l'id de la réclamation
    $reponse = $entityManager->getRepository(Reponse::class)->findOneBy([
        'indice' => $id,
    ]);

    if (!$reponse) {
        throw $this->createNotFoundException('Aucune réponse trouvée pour cette réclamation.');
    }

    return $this->render('reponse/admin/show.html.twig', [
        'reponse' => $reponse,
    ]);
}

}