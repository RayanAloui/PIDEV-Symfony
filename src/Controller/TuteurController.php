<?php

namespace App\Controller;

use App\Entity\Tuteur;
use App\Repository\TuteurRepository;
use App\Form\TuteurSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\TuteurType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Service\MailerService;


#[Route('/crud/tuteur')]
class TuteurController extends AbstractController
{
    #[Route('/list', name: 'app_crud_tuteur', methods: ['GET'])]
    public function list(Request $request, TuteurRepository $repository): Response
    {
        $form = $this->createForm(TuteurSearchType::class);
        $form->handleRequest($request);

        // Récupérer le champ de recherche
        $query = $form->get('query')->getData();

        // Récupérer les paramètres de tri
        $sortField = $request->query->get('sort', 'nomT'); // Champ par défaut : Nom
        $sortOrder = $request->query->get('order', 'asc'); // Ordre par défaut : Ascendant

        // Appliquer la recherche et le tri en même temps
        if ($query) {
            $tuteurs = $repository->searchTuteurs($query, $sortField, $sortOrder);
        } else {
            $tuteurs = $repository->findBy([], [$sortField => $sortOrder]);
        }

        return $this->render('tuteur/list.html.twig', [
            'form' => $form->createView(),
            'tuteurs' => $tuteurs,
            'sortField' => $sortField,
            'sortOrder' => $sortOrder,
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
    public function edit(Request $request, EntityManagerInterface $entityManager, TuteurRepository $tuteurRepository, MailerService $mailerService, int $id): Response
    {
        $tuteur = $tuteurRepository->find($id);

        if (!$tuteur) {
            throw $this->createNotFoundException("Tuteur non trouvé !");
        }

        $ancienneDisponibilite = $tuteur->getDisponibilite();

        $form = $this->createForm(TuteurType::class, $tuteur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($ancienneDisponibilite !== $tuteur->getDisponibilite()) {
                $mailerService->sendAvailabilityChangeEmail(
                    $tuteur->getEmail(),
                    "{$tuteur->getNomT()} {$tuteur->getPrenomT()}",
                    $tuteur->getDisponibilite()
                );
            }

            $entityManager->flush();
            return $this->redirectToRoute('app_crud_tuteur');
        }

        return $this->render('tuteur/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/test-email', name: 'test_email')]
    public function testEmail(MailerService $mailerService): Response
    {
        $mailerService->sendAvailabilityChangeEmail(
            'alouiahmed525@gmail.com', 
            'Ahmed Aloui',
            'Disponible'
        );
    
        return new Response('succès email');
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

    #[Route('/tuteurs/search', name: 'app_tuteurs_search', methods: ['GET'])]
    public function search(Request $request, TuteurRepository $tuteurRepository)
    {
        $query = $request->query->get('query', '');
        $tuteurs = $query ? $tuteurRepository->searchTuteurs($query) : [];

        return $this->json([
            'tuteurs' => array_map(function ($tuteur) {
                return [
                    'cinT' => $tuteur->getCinT(),
                    'nomT' => $tuteur->getNomT(),
                    'prenomT' => $tuteur->getPrenomT(),
                    'telephoneT' => $tuteur->getTelephoneT(),
                    'email' => $tuteur->getEmail(),
                ];
            }, $tuteurs),
        ]);
    }


    #[Route('/tuteurs/pdf', name: 'tuteurs_pdf')]
    public function exportPdf(EntityManagerInterface $entityManager): Response
    {
        // Récupérer tous les tuteurs depuis la base de données
        $tuteurs = $entityManager->getRepository(Tuteur::class)->findAll();

        // Configurer Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        // Initialiser Dompdf
        $dompdf = new Dompdf($pdfOptions);

        // Générer le HTML pour le PDF
        $html = $this->renderView('tuteur/tuteurs_pdf.html.twig', [
            'tuteurs' => $tuteurs
        ]);

        // Charger le HTML dans Dompdf
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Renvoyer le PDF en réponse HTTP
        $response = new Response($dompdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="Liste_Tuteurs.pdf"');

        return $response;
    }
}
