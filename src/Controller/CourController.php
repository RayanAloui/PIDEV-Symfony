<?php

namespace App\Controller;

use App\Entity\Cour;
use App\Entity\Orphelin;
use App\Entity\Rating;
use App\Form\RatingType;
use App\Form\CourType;
use App\Repository\CourRepository;
use App\Form\CourSearchType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Entity\Tuteur;
use App\Service\EdenAiService;

#[Route('/crud/cours')]
class CourController extends AbstractController
{
    #[Route('/list', name: 'app_crud_cours', methods: ['GET'])]
    public function list(Request $request, CourRepository $repository): Response
    {
        $form = $this->createForm(CourSearchType::class);
        $form->handleRequest($request);

        // Récupérer le champ de recherche
        $query = $form->get('query')->getData();

        // Récupérer les paramètres de tri
        $sortField = $request->query->get('sort', 'titre'); // Champ par défaut : Nom
        $sortOrder = $request->query->get('order', 'asc'); // Ordre par défaut : Ascendant

        // Appliquer la recherche et le tri en même temps
        if ($query) {
            $cours = $repository->searchByTitle($query, $sortField, $sortOrder);
        } else {
            $cours = $repository->findBy([], [$sortField => $sortOrder]);
        }

        return $this->render('cour/list.html.twig', [
            'form' => $form->createView(),
            'cours' => $cours,
            'sortField' => $sortField,
            'sortOrder' => $sortOrder,
        ]);
    }

    #[Route('/add', name: 'app_crud_cours_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager,EdenAiService $edenAiService): Response
    {
        $cour = new Cour();
        $form = $this->createForm(CourType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageC')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($this->getParameter('cours_images_directory'), $newFilename);
                    $cour->setImageC($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            // Génération automatique du résumé
            $contenu = $cour->getContenu();
            $resume = $edenAiService->summarizeText($contenu);
            $cour->setResume($resume);

            $entityManager->persist($cour);
            $entityManager->flush();

            $this->addFlash('success', 'Cours ajouté avec succès !');
            return $this->redirectToRoute('app_crud_cours');
        }

        return $this->render('cour/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'app_crud_cours_edit')]
    public function edit(Request $request, Cour $cours, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CourType::class, $cours);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageC')->getData();

            if ($imageFile) {
                // Supprimer l'ancienne image (optionnel, si tu veux éviter trop de fichiers inutiles)
                if ($cours->getImageC()) {
                    $oldImagePath = $this->getParameter('cours_images_directory') . '/' . $cours->getImageC();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                // Sauvegarder la nouvelle image
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('cours_images_directory'), $newFilename);
                $cours->setImageC($newFilename);
            }

            $entityManager->flush();
            return $this->redirectToRoute('app_crud_cours');
        }

        return $this->render('cour/edit.html.twig', [
            'form' => $form->createView(),
            'cours' => $cours
        ]);
    }

    #[Route('/delete/{id}', name: 'app_crud_cours_delete')]
    public function supprimer(Cour $cours, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($cours);
        $entityManager->flush();

        return $this->redirectToRoute('app_crud_cours');
    }

    #[Route('/voir/{id}', name: 'app_crud_cours_voir')]
    public function voir(Cour $cours): Response
    {
        return $this->render('cour/voir.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/cours/search', name: 'app_cours_search', methods: ['GET'])]
    public function search(Request $request, CourRepository $courRepository)
    {
        $query = $request->query->get('query', '');
        $cours = $query ? $courRepository->searchByTitle($query) : [];

        return $this->json([
            'cours' => array_map(function ($cour) {
                return [
                    'titre' => $cour->getTitre(),
                ];
            }, $cours),
        ]);
    }

    /*public function exportPdf(EntityManagerInterface $entityManager): Response
    {
        // Récupérer tous les tuteurs depuis la base de données
        $cours = $entityManager->getRepository(Cour::class)->findAll();

        // Configurer Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('isRemoteEnabled', true); // ✅ Autorise les images distantes
        $pdfOptions->set('defaultFont', 'Arial');

        // Initialiser Dompdf
        $dompdf = new Dompdf($pdfOptions);

        // Générer le HTML pour le PDF
        $html = $this->renderView('cour/cours_pdf.html.twig', [
            'cours' => $cours
        ]);

        // Charger le HTML dans Dompdf
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Renvoyer le PDF en réponse HTTP
        $response = new Response($dompdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="Liste_Cours.pdf"');

        return $response;
    }*/

    #[Route('/cours/pdf', name: 'cours_pdf')]
    public function generatePdf(CourRepository $coursRepository): Response
    {
        $cours = $coursRepository->findAll();

        foreach ($cours as $cour) {
            if ($cour->getImageC()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/assets/img/' . $cour->getImageC();
                if (file_exists($imagePath)) {
                    $imageData = base64_encode(file_get_contents($imagePath));
                    $cour->base64Image = 'data:image/jpeg;base64,' . $imageData;
                } else {
                    $cour->base64Image = null;
                }
            } else {
                $cour->base64Image = null;
            }
        }

        $html = $this->renderView('cour/cours_pdf.html.twig', [
            'cours' => $cours,
        ]);

        // Générer le PDF
        $pdfOptions = new Options();
        $pdfOptions->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($pdfOptions);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Liste_Cours.pdf"',
        ]);
    }


    #[Route('/dashboard/cours/ajouter', name: 'tuteur_cours_ajouter')]
    public function ajouterCours(Request $request, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        if (!$session->has('idT')) {
            return $this->redirectToRoute('tuteur_login');
        }

        $tuteurId = $session->get('idT');
        $tuteur = $entityManager->getRepository(Tuteur::class)->find($tuteurId);

        $cour = new Cour();
        $cour->setTuteur($tuteur);

        $form = $this->createForm(CourType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($cour);
            $entityManager->flush();

            return $this->redirectToRoute('tuteur_dashboard');
        }

        return $this->render('tuteur/cours_add.html.twig', [
            'form' => $form->createView(),
            'action' => 'Ajouter'
        ]);
    }


    #[Route('/dashboard/cours/modifier/{id}', name: 'tuteur_cours_modifier')]
    public function modifierCours(Cour $cours, Request $request, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        if (!$session->has('idT') || $cours->getTuteur()->getIdT() !== $session->get('idT')) {
            return $this->redirectToRoute('tuteur_dashboard');
        }

        $form = $this->createForm(CourType::class, $cours);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageC')->getData();

            if ($imageFile) {
                // Supprimer l'ancienne image (optionnel, si tu veux éviter trop de fichiers inutiles)
                if ($cours->getImageC()) {
                    $oldImagePath = $this->getParameter('cours_images_directory') . '/' . $cours->getImageC();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                // Sauvegarder la nouvelle image
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('cours_images_directory'), $newFilename);
                $cours->setImageC($newFilename);
            }

            $entityManager->flush();
            return $this->redirectToRoute('tuteur_dashboard');
        }

        return $this->render('tuteur/cours_edit.html.twig', [
            'form' => $form->createView(),
            'cours' => $cours,
            'action' => 'Modifier'

        ]);
    }


    #[Route('/dashboard/cours/supprimer/{id}', name: 'tuteur_cours_supprimer')]
    public function supprimerCours(Cour $cour, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        if (!$session->has('idT') || $cour->getTuteur()->getIdT() !== $session->get('idT')) {
            return $this->redirectToRoute('tuteur_dashboard');
        }

        $entityManager->remove($cour);
        $entityManager->flush();

        return $this->redirectToRoute('tuteur_dashboard');
    }


}
