<?php

namespace App\Controller;

use App\Entity\Orphelin;
use App\Entity\Rating;
use App\Form\RatingType;
use App\Repository\CourRepository;
use App\Repository\RatingRepository;
use App\Entity\Cour;
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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\EdenAiService;

#[Route('/crud/orphelin')]
class OrphelinController extends AbstractController
{
    #[Route('/test', name: 'app_front')]
    public function frontPage(SessionInterface $session): Response
    {
        return $this->render('front.html.twig');
    }
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

    /*#[Route('/orphelins/search', name: 'app_orphelins_search', methods: ['GET'])]
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
    }*/

    #[Route('/orphelins/search', name: 'app_orphelins_search', methods: ['GET'])]
    public function search(Request $request, OrphelinRepository $orphelinRepository): JsonResponse
    {
        $query = $request->query->get('query', '');
        $sortField = $request->query->get('sort', 'nomO');
        $sortOrder = $request->query->get('order', 'asc');

        $orphelins = $orphelinRepository->searchOrphelins($query, $sortField, $sortOrder);

        $results = [];
        foreach ($orphelins as $orphelin) {
            $tuteurNom = $orphelin->getTuteur() ? $orphelin->getTuteur()->getNomT() . ' ' . $orphelin->getTuteur()->getPrenomT() : 'Non assigné';

            $results[] = [
                'id' => $orphelin->getIdO(),
                'nom' => $orphelin->getNomO(),
                'prenom' => $orphelin->getPrenomO(),
                'dateNaissance' => $orphelin->getDateNaissance()->format('d/m/Y'),
                'sexe' => $orphelin->getSexe(),
                'situationScolaire' => $orphelin->getSituationScolaire() ?: 'Non spécifiée',
                'tuteur' => $tuteurNom,
                'deleteUrl' => $this->generateUrl('app_crud_orphelin_delete', ['id' => $orphelin->getIdO()]),
                'editUrl' => $this->generateUrl('app_crud_orphelin_edit', ['id' => $orphelin->getIdO()])
            ];
        }

        return new JsonResponse($results);
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

    #[Route('/login', name: 'orphelin_login')]
    public function login(Request $request, SessionInterface $session, EntityManagerInterface $em): Response
    {
        if ($session->has('idO')) {
            return $this->redirectToRoute('orphelin_dashboard');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $nomO = $request->request->get('nomO');
            $prenomO = $request->request->get('prenomO');
            $idO = $request->request->get('idO');

            // Vérifier si l'orphelin existe
            $orphelin = $em->getRepository(Orphelin::class)->findOneBy([
                'idO' => $idO,
                'nomO' => $nomO,
                'prenomO' => $prenomO
            ]);

            if ($orphelin) {
                $session->set('idO', $orphelin->getIdO());
                $session->set('nomO', $orphelin->getNomO());
                $session->set('prenomO', $orphelin->getPrenomO());
                $session->set('idTuteur', $orphelin->getTuteur()->getIdT());

                return $this->redirectToRoute('orphelin_dashboard');
            } else {
                $error = "Identifiants incorrects.";
            }
        }

        return $this->render('orphelin/login.html.twig', [
            'error' => $error
        ]);
    }


    #[Route('/dashboard', name: 'orphelin_dashboard')]
    public function dashboard(SessionInterface $session, EntityManagerInterface $em): Response
    {
        // Vérifier si l'orphelin est connecté
        if (!$session->has('idO')) {
            return $this->redirectToRoute('orphelin_login');
        }

        $idTuteur = $session->get('idTuteur');

        // Récupérer les cours du tuteur de cet orphelin
        $cours = $em->getRepository(Cour::class)->findBy(['tuteur' => $idTuteur]);

        return $this->render('orphelin/dashboard.html.twig', [
            'cours' => $cours,
            'nomO' => $session->get('nomO'),
            'prenomO' => $session->get('prenomO'),
        ]);
    }

    /*#[Route('/cours/{id}', name: 'orphelin_cours_details')]
    public function coursDetails(int $id, SessionInterface $session, EntityManagerInterface $em): Response
    {
        if (!$session->has('idO')) {
            return $this->redirectToRoute('orphelin_login');
        }

        $cours = $em->getRepository(Cour::class)->find($id);

        if (!$cours || $cours->getTuteur()->getIdT() !== $session->get('idTuteur')) {
            throw $this->createNotFoundException("Cours introuvable ou non autorisé.");
        }

        return $this->render('orphelin/cours_details.html.twig', [
            'cours' => $cours
        ]);
    }*/

    #[Route('/cours/{id}', name: 'orphelin_cours_details')]
    public function coursDetails(
        int $id,
        SessionInterface $session,
        Request $request,
        EntityManagerInterface $em,
        RatingRepository $ratingRepository,
        CourRepository $courRepository,
        EdenAiService $edenAiService
    ): Response {
        if (!$session->has('idO')) {
            return $this->redirectToRoute('orphelin_login');
        }

        $cours = $em->getRepository(Cour::class)->find($id);

        if (!$cours || $cours->getTuteur()->getIdT() !== $session->get('idTuteur')) {
            throw $this->createNotFoundException("Cours introuvable ou non autorisé.");
        }

        // Si la requête est une requête AJAX pour la traduction
        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            $translated = $edenAiService->translateToEnglish($cours->getContenu());
            return new JsonResponse(['translated' => $translated]);
        }

        // Gestion de la note
        $orphelin = $em->getRepository(Orphelin::class)->find($session->get('idO'));
        $existingRating = $ratingRepository->findOneBy(['orphelin' => $orphelin, 'cours' => $cours]);

        if ($existingRating) {
            $form = $this->createForm(RatingType::class, $existingRating);
        } else {
            $form = $this->createForm(RatingType::class);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rating = $form->getData();
            $rating->setOrphelin($orphelin);
            $rating->setCours($cours);

            if (!$existingRating) {
                $em->persist($rating);
            }
            $em->flush();

            // Recalcul de la moyenne
            $ratings = $ratingRepository->findBy(['cours' => $cours]);
            $totalRating = array_reduce($ratings, fn($sum, $r) => $sum + $r->getNote(), 0);
            $averageRating = count($ratings) > 0 ? $totalRating / count($ratings) : 0;

            $cours->setNote_Moyenne($averageRating);
            $em->flush();

            return $this->redirectToRoute('orphelin_cours_details', ['id' => $id]);
        }

        $ratings = $ratingRepository->findBy(['cours' => $cours]);

        $keywords = [];
        if ($request->query->get('extract_keywords')) {
            $keywords = $edenAiService->extractKeywords($cours->getContenu());
        }

        // Synthèse vocale
        $audioUrl = null;
        if ($request->query->get('tts') === '1') {
            dump('Synthèse vocale déclenchée');
            $audioUrl = $edenAiService->synthesize($cours->getContenu());
        }

        return $this->render('orphelin/cours_details.html.twig', [
            'cours' => $cours,
            'ratings' => $ratings,
            'form' => $form->createView(),
            'keywords' => $keywords,
            'audioUrl' => $audioUrl,
        ]);
    }

    /*public function coursDetails(int $id, SessionInterface $session, EntityManagerInterface $em, Request $request, RatingRepository $ratingRepository): Response
    {
        // Vérification si l'orphelin est connecté
        if (!$session->has('idO')) {
            return $this->redirectToRoute('orphelin_login');
        }

        // Récupérer le cours par son ID
        $cours = $em->getRepository(Cour::class)->find($id);

        // Vérifier si le cours existe et s'il appartient bien au tuteur de l'orphelin connecté
        if (!$cours || $cours->getTuteur()->getIdT() !== $session->get('idTuteur')) {
            throw $this->createNotFoundException("Cours introuvable ou non autorisé.");
        }

        // Récupérer les évaluations du cours
        $ratings = $ratingRepository->findBy(['cours' => $cours]);

        // Créer un nouvel objet Rating pour le formulaire de notation
        $rating = new Rating();
        $rating->setCours($cours);
        $rating->setOrphelin($em->getRepository(Orphelin::class)->find($session->get('idO')));  // Assurer que l'orphelin est l'utilisateur connecté

        // Créer le formulaire de notation
        $form = $this->createForm(RatingType::class, $rating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistrer la note dans la base de données
            $em->persist($rating);
            $em->flush();

            // Rediriger vers la même page pour afficher la note mise à jour
            return $this->redirectToRoute('orphelin_cours_details', ['id' => $id]);
        }

        // Renvoyer la vue avec les détails du cours, le formulaire et les évaluations
        return $this->render('orphelin/cours_details.html.twig', [
            'cours' => $cours,
            'form' => $form->createView(),
            'ratings' => $ratings
        ]);
    }*/

    #[Route('/logout', name: 'orphelin_logout')]
    public function logout(SessionInterface $session): Response
    {
        $session->clear();
        return $this->redirectToRoute('orphelin_login');
    }

    #[Route('/chatbot/ask', name: 'chatbot_ask', methods: ['POST'])]
    public function chatbotAsk(Request $request, EdenAiService $edenAiService): JsonResponse
    {
        $question = $request->request->get('question');

        if (!$question) {
            return new JsonResponse(['error' => 'Question vide.'], 400);
        }

        $answer = $edenAiService->askChatbot($question);

        return new JsonResponse(['answer' => $answer]);
    }

    #[Route('/orphelins_qr', name: 'app_orphelins_qr')]
    public function orphelinsQr(OrphelinRepository $orphelinRepository): Response
    {
        $orphelins = $orphelinRepository->findAll();

        return $this->render('orphelin/qr_list.html.twig', [
            'orphelins' => $orphelins,
        ]);
    }
}
