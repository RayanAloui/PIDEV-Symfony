<?php
// src/Controller/VisitesController.php
namespace App\Controller;

use App\Entity\Visites;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\VisitesRepository;


use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use Dompdf\Dompdf;
use Dompdf\Options;



class VisitesController extends AbstractController
{
    #[Route('/visite/scan', name: 'visite_scan', methods: ['GET', 'POST'])]
    public function scan(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('GET')) {
            return $this->render('visites/scan.html.twig');
        }
    
        try {
            $data = json_decode($request->getContent(), true);
    
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON data');
            }
    
            $visiteId = $data['visite_id'] ?? null;
    
            if (!$visiteId) {
                return new JsonResponse(
                    ['status' => 'error', 'message' => 'ID de visite manquant'],
                    Response::HTTP_BAD_REQUEST
                );
            }
    
            $visite = $em->getRepository(Visites::class)->find($visiteId);
    
            if (!$visite) {
                return new JsonResponse(
                    ['status' => 'error', 'message' => 'Visite non trouvée'],
                    Response::HTTP_NOT_FOUND
                );
            }
    
            $statut = $visite->getStatut();
    
            if ($statut === 'En attente') {
                $visite->setStatut('Confirmée');
                $em->flush();
    
                return new JsonResponse([
                    'status' => 'success',
                    'message' => 'Bienvenue ! Votre visite est confirmée.',
                ]);
            }
    
            if ($statut === 'Confirmée') {
                $visite->setStatut('Terminée');
                $em->flush();
    
                return new JsonResponse([
                    'status' => 'success',
                    'message' => 'Merci pour votre visite, à la prochaine 😊 !',
                ]);
            }
    
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Cette visite est déjà terminée.',
            ], Response::HTTP_CONFLICT);
    
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur serveur: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    

    #[Route('/visite/pdf/{id}', name: 'visite_pdf', methods: ['GET'])]
    public function generatePdf(EntityManagerInterface $em, int $id): Response
    {
        $visite = $em->getRepository(Visites::class)->find($id);
        
        if (!$visite) {
            $this->addFlash('error', 'Visite non trouvée');
            return $this->redirectToRoute('afficher_visite');
        }
    
        // 1. Génération du mot de passe

    
        // 2. Création simple du QR Code
        $qrCode = new QrCode((string)$visite->getId());
       
        
        // 3. Écriture en PNG
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        // 4. Conversion pour HTML
        $qrCodeData = 'data:'.$result->getMimeType().';base64,'.base64_encode($result->getString());
    
        // 5. Génération du PDF (reste identique)
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($pdfOptions);
        $html = $this->renderView('visites/pdf.html.twig', [
            'visite' => $visite,
            'qrCode' => $qrCodeData
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="visite_'.$visite->getId().'.pdf"'
            ]
        );
    }

    #[Route('/visite/ajouter', name: 'ajouter_visite', methods: ['GET', 'POST'])]
    public function ajouterVisite(Request $request, EntityManagerInterface $em, \Symfony\Contracts\HttpClient\HttpClientInterface $httpClient, ValidatorInterface $validator): Response
    {
        $visite = new Visites();
        $visite->setStatut('En attente');
    
        if ($request->isMethod('POST')) {
            $user = $em->getRepository(User::class)->find($request->request->get('id_user'));
    
            if (!$user) {
                $this->addFlash('error', 'Utilisateur non trouvé');
                return $this->redirectToRoute('ajouter_visite');
            }
    
            $visite->setId_user($user);
            $visite->setDate(new \DateTime($request->request->get('date')));
            $visite->setHeure($request->request->get('heure'));
            $visite->setMotif($request->request->get('motif'));
    
            // Validation de l'entité Visites
            $errors = $validator->validate($visite);
    
            if (count($errors) > 0) {
                // Si des erreurs de validation existent, on les passe à la vue
                $errorsString = (string) $errors;
                $this->addFlash('error', 'Erreur de validation : ' . $errorsString);
                return $this->render('visites/ajouter_visite.html.twig', [
                    'users' => $em->getRepository(User::class)->findAll(),
                    'errors' => $errors, // Passer les erreurs à la vue
                ]);
            }
    
            $em->persist($visite);
            $em->flush();

        // 📲 Envoi WhatsApp via CallMeBot
        $nom = $user->getName() . ' ' . $user->getSurname();
        $numero = +21694653884; // format +216XXXXXXXX
        $date = $visite->getDate()->format('d/m/Y');
        $heure = $visite->getHeure();
        $message = "Bonjour $nom, votre rendez-vous est confirmé pour le $date à $heure. Merci !";

        $apikey = '8351129'; // 👉 remplace par ta vraie clé CallMeBot
        $url = "https://api.callmebot.com/whatsapp.php?phone=" . urlencode($numero)
            . "&text=" . urlencode($message)
            . "&apikey=" . urlencode($apikey);

        try {
            $response = $httpClient->request('GET', $url);

            if ($response->getStatusCode() === 200) {
                $this->addFlash('success', 'Visite ajoutée et message WhatsApp envoyé ✅');
            } else {
                $this->addFlash('warning', 'Visite ajoutée, mais envoi WhatsApp échoué ❌');
            }
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Visite ajoutée, mais erreur lors de l’envoi WhatsApp ❌');
        }

        return $this->redirectToRoute('afficher_visite');
    }

    $users = $em->getRepository(User::class)->findAll();

    return $this->render('visites/ajouter_visite.html.twig', [
        'users' => $users
    ]);
}
#[Route('/visites/filtered', name: 'visites_filtered', methods: ['GET'])]
public function getFilteredVisites(
    Request $request,
    VisitesRepository $visitesRepository
): JsonResponse {
    $search = $request->query->get('search', '');
    $sortBy = $request->query->get('sortBy');
    $sortOrder = $request->query->get('sortOrder', 'asc');

    $visites = $visitesRepository->findByFilters($search, $sortBy, $sortOrder);

    $html = $this->renderView('visites/_visites_table.html.twig', [
        'visites' => $visites
    ]);

    return new JsonResponse(['success' => true, 'html' => $html]);
}




#[Route('/visite/afficher', name: 'afficher_visite', methods: ['GET'])]
public function listeVisites(EntityManagerInterface $em): Response
{
    $visites = $em->getRepository(Visites::class)->findAll();

    return $this->render('visites/afficher_visite.html.twig', [
        'visites' => $visites
    ]);
}


    #[Route('/visite/modifier/{id}', name: 'modifier_visite', methods: ['GET', 'POST'])]
    public function modifierVisite(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $visite = $em->getRepository(Visites::class)->find($id);
        
        if (!$visite) {
            $this->addFlash('error', 'Visite non trouvée');
            return $this->redirectToRoute('afficher_visite');
        }

        if ($request->isMethod('POST')) {
            $user = $em->getRepository(User::class)->find($request->request->get('id_user'));
            
            if (!$user) {
                $this->addFlash('error', 'Utilisateur non trouvé');
                return $this->redirectToRoute('modifier_visite', ['id' => $id]);
            }

            $visite->setId_user($user);
            $visite->setDate(new \DateTime($request->request->get('date')));
            $visite->setHeure($request->request->get('heure'));
            $visite->setMotif($request->request->get('motif'));
            $visite->setStatut($request->request->get('statut'));

            $em->flush();

            $this->addFlash('success', 'Visite modifiée avec succès');
            return $this->redirectToRoute('afficher_visite');
        }

        // Récupérer tous les utilisateurs pour le formulaire
        $users = $em->getRepository(User::class)->findAll();
        
        return $this->render('visites/modifier_visite.html.twig', [
            'visite' => $visite,
            'users' => $users
        ]);
    }

    #[Route('/visite/supprimer/{id}', name: 'supprimer_visite', methods: ['POST'])]
    public function supprimerVisite(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $visite = $em->getRepository(Visites::class)->find($id);
        
        if (!$visite) {
            $this->addFlash('error', 'Visite non trouvée');
            return $this->redirectToRoute('afficher_visite');
        }

        // Vérifier le token CSRF pour la sécurité
        if ($this->isCsrfTokenValid('delete'.$visite->getId(), $request->request->get('_token'))) {
            $em->remove($visite);
            $em->flush();
            
            $this->addFlash('success', 'Visite supprimée avec succès');
        } else {
            $this->addFlash('error', 'Token CSRF invalide');
        }

        return $this->redirectToRoute('afficher_visite');
    }
}