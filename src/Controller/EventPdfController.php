<?php

namespace App\Controller;

use App\Entity\Events;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Asset\Packages;

class EventPdfController extends AbstractController
{
    private $assetManager;

    public function __construct(Packages $assetManager)
    {
        $this->assetManager = $assetManager;
    }

    
      #[Route("/events/pdf/{id}", name:'app_crud_events_pdf')]
     
    public function generatePdf(Events $event): Response
    {
        // Configurer DOMPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        // Instancier Dompdf
        $dompdf = new Dompdf($options);
        
        // Obtenir le chemin du logo depuis les assets
        // Le chemin doit être relatif au dossier public
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/build/images/orphancare-logo.png';
        $logoData = '';
        
        // Vérifier si le fichier existe et le convertir en base64
        if (file_exists($logoPath)) {
            $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }
        
        // Construire le HTML pour le PDF
        $html = $this->renderView('events/pdfE.html.twig', [
            'event' => $event,
            'logoData' => $logoData,
            'date' => new \DateTime(),
        ]);
        
        // Charger le HTML dans DOMPDF
        $dompdf->loadHtml($html);
        
        // Définir la taille du papier et l'orientation
        $dompdf->setPaper('A4', 'portrait');
        
        // Rendre le PDF
        $dompdf->render();
        
        // Générer un nom de fichier
        $fileName = 'evenement-' . $event->getIdEvent() . '-' . date('Y-m-d') . '.pdf';
        
        // Envoyer le PDF au navigateur
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]
        );
    }
}