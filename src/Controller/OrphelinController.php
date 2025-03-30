<?php

namespace App\Controller;

use App\Entity\Orphelin;
use App\Form\OrphelinType;
use App\Form\OrphelinSearchType;
use App\Repository\OrphelinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/crud/orphelin')]
class OrphelinController extends AbstractController
{
    #[Route('/list', name: 'app_crud_orphelin', methods: ['GET'])]
    /*public function index(OrphelinRepository $orphelinRepository): Response
    {
        return $this->render('orphelin/list.html.twig', [
            'orphelins' => $orphelinRepository->findAll(),
        ]);
    }*/

    public function list(Request $request, OrphelinRepository $repository): Response
    {
        $form = $this->createForm(OrphelinSearchType::class);
        $form->handleRequest($request);

        // Récupérer le champ de recherche
        $query = $form->get('query')->getData();

        // Récupérer les paramètres de tri
        $sortField = $request->query->get('sort', 'nomO'); // Champ par défaut : Nom
        $sortOrder = $request->query->get('order', 'asc'); // Ordre par défaut : Ascendant

        // Appliquer la recherche et le tri en même temps
        if ($query) {
            $orphelins = $repository->searchOrphelins($query, $sortField, $sortOrder);
        } else {
            $orphelins = $repository->findBy([], [$sortField => $sortOrder]);
        }

        // Récupérer les statistiques (nombre d'orphelins par tuteur)
        $stats = $repository->countOrphelinsByTuteur();

        return $this->render('orphelin/list.html.twig', [
            'form' => $form->createView(),
            'orphelins' => $orphelins,
            'sortField' => $sortField,
            'sortOrder' => $sortOrder,
            'stats' => $stats,
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

    #[Route('/orphelins/search', name: 'app_orphelins_search', methods: ['GET'])]
    public function search(Request $request, OrphelinRepository $orphelinRepository)
    {
        $query = $request->query->get('query', '');
        $orphelins = $query ? $orphelinRepository->searchOrphelins($query) : [];

        return $this->json([
            'orphelins' => array_map(function ($orphelin) {
                return [
                    'nomO' => $orphelin->getNomO(),
                    'prenomO' => $orphelin->getPrenomO(),
                    'dateNaissance' => $orphelin->getDateNaissance(),
                ];
            }, $orphelins),
        ]);
    }


    #[Route('/orphelins/pdf', name: 'orphelins_pdf')]
    public function exportPdf(EntityManagerInterface $entityManager): Response
    {
        // Récupérer tous les tuteurs depuis la base de données
        $orphelins = $entityManager->getRepository(Orphelin::class)->findAll();

        // Configurer Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        // Initialiser Dompdf
        $dompdf = new Dompdf($pdfOptions);

        // Générer le HTML pour le PDF
        $html = $this->renderView('orphelin/orphelins_pdf.html.twig', [
            'orphelins' => $orphelins
        ]);

        // Charger le HTML dans Dompdf
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Renvoyer le PDF en réponse HTTP
        $response = new Response($dompdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="Liste_Orphelins.pdf"');

        return $response;
    }

    #[Route('/orphelins/stats', name: 'app_orphelin_stats')]
    public function stats(OrphelinRepository $repository): Response
    {
        $stats = $repository->countOrphelinsByTuteur();

        // Transformer les résultats en JSON
        $data = [
            'labels' => array_column($stats, 'nomT'), // Noms des tuteurs
            'counts' => array_column($stats, 'orphelinCount') // Nombre d'orphelins
        ];

        return $this->json($data);
    }
}
