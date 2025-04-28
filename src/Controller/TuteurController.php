<?php

namespace App\Controller;

use App\Entity\Tuteur;
use App\Entity\Cour;
use App\Repository\TuteurRepository;
use App\Repository\CourRepository;
use App\Repository\RatingRepository;
use App\Repository\OrphelinRepository;
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
use App\Services\MailerService;
use App\Services\SmsService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


#[Route('/crud/tuteur')]
class TuteurController extends AbstractController
{
    #[Route('/test', name: 'app_front')]
    public function frontPage(SessionInterface $session): Response
    {
        return $this->render('front.html.twig');
    }
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
    public function edit(Request $request, EntityManagerInterface $entityManager, TuteurRepository $tuteurRepository, MailerService $mailerService, SmsService $smsService, int $id): Response
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
                //Envoi de l'email
                $mailerService->sendAvailabilityChangeEmail(
                    $tuteur->getEmail(),
                    "{$tuteur->getNomT()} {$tuteur->getPrenomT()}",
                    $tuteur->getDisponibilite()
                );

                //Envoi du SMS
                $smsService->sendSms(
                    '+216' . $tuteur->getTelephoneT(),
                    "Bonjour {$tuteur->getNomT()} {$tuteur->getPrenomT()}, votre disponibilité a été mise à jour : {$tuteur->getDisponibilite()}."
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
    public function testNotification(MailerService $mailerService): Response
    {
        $mailerService->sendAvailabilityChangeEmail(
            'alouiahmed525@gmail.com',
            'Ahmed Aloui',
            'Disponible'
        );

        return new Response('✅ Notification e-mail + SMS envoyée.');
    }

    #[Route('/test-sms', name: 'test_sms')]
    public function testSms(SmsService $smsService): Response
    {
        $smsService->sendSms('+21699058580', 'Test Twilio Symfony 🚀');

        return new Response('✅ Test SMS envoyé.');
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

    #[Route('/tuteurs/search', name: 'app_tuteur_search', methods: ['GET'])]
    public function search(Request $request, TuteurRepository $tuteurRepository): JsonResponse
    {
        $query = $request->query->get('query', '');
        $sortField = $request->query->get('sort', 'nomT');
        $sortOrder = $request->query->get('order', 'asc');

        $tuteurs = $tuteurRepository->searchTuteurs($query, $sortField, $sortOrder);

        $results = [];
        foreach ($tuteurs as $tuteur) {
            $results[] = [
                'id' => $tuteur->getIdT(),
                'cin' => $tuteur->getCinT(),
                'nom' => $tuteur->getNomT(),
                'prenom' => $tuteur->getPrenomT(),
                'telephone' => $tuteur->getTelephoneT() ?: '-',
                'adresse' => $tuteur->getAdresseT() ?: '-',
                'disponibilite' => $tuteur->getDisponibilite(),
                'email' => $tuteur->getEmail(),
                'editUrl' => $this->generateUrl('app_crud_tuteur_edit', ['id' => $tuteur->getIdT()]),
                'deleteUrl' => $this->generateUrl('app_crud_tuteur_delete', ['id' => $tuteur->getIdT()])
            ];
        }

        return new JsonResponse($results);
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

    #[Route('/login', name: 'tuteur_login')]
    public function login(Request $request, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $cinT = $request->request->get('cinT');

            // Rechercher le tuteur dans la BD
            $tuteur = $entityManager->getRepository(Tuteur::class)->findOneBy([
                'email' => $email,
                'cinT' => $cinT
            ]);

            if ($tuteur) {
                // Stocker les infos du tuteur en session
                $session->set('idT', $tuteur->getIdT());
                $session->set('nomT', $tuteur->getNomT());
                $session->set('prenomT', $tuteur->getPrenomT());

                return $this->redirectToRoute('tuteur_dashboard');
            } else {
                $this->addFlash('error', 'Identifiants incorrects !');
            }
        }

        return $this->render('tuteur/login.html.twig');
    }

    #[Route('/dashboard', name: 'tuteur_dashboard')]
    public function dashboard(SessionInterface $session, CourRepository $courRepository): Response
    {
        // Vérifier si le tuteur est connecté
        if (!$session->has('idT')) {
            return $this->redirectToRoute('tuteur_login');
        }

        // Récupérer l'ID du tuteur depuis la session
        $tuteurId = $session->get('idT');

        // Récupérer les cours spécifiques à ce tuteur
        $cours = $courRepository->findBy(['tuteur' => $tuteurId]);

        return $this->render('tuteur/dashboard.html.twig', [
            'nomT' => $session->get('nomT'),
            'prenomT' => $session->get('prenomT'),
            'cours' => $cours
        ]);
    }


    #[Route('/logout', name: 'tuteur_logout')]
    public function logout(SessionInterface $session): Response
    {
        $session->clear();
        return $this->redirectToRoute('tuteur_login');
    }

    #[Route('/orphelins', name: 'tuteur_orphelins')]
    public function afficherOrphelins(SessionInterface $session, OrphelinRepository $orphelinRepository): Response
    {
        // Vérifier si le tuteur est connecté
        if (!$session->has('idT')) {
            return $this->redirectToRoute('tuteur_login');
        }

        $idTuteur = $session->get('idT');

        // Récupérer la liste des orphelins du tuteur connecté
        $orphelins = $orphelinRepository->findBy(['tuteur' => $idTuteur]);

        return $this->render('tuteur/orphelins_list.html.twig', [
            'orphelins' => $orphelins,
        ]);
    }

    #[Route('/cours/{id}', name: 'tuteur_cours_details')]
    public function voirCours(Cour $cours, SessionInterface $session, int $id, EntityManagerInterface $em, RatingRepository $ratingRepository): Response
    {
        // Vérifier si le tuteur est connecté
        if (!$session->has('idT')) {
            return $this->redirectToRoute('tuteur_login');
        }

        // Récupérer le cours par son ID
        $cours = $em->getRepository(Cour::class)->find($id);

        // Vérifier si le cours existe et si c'est bien un cours du tuteur connecté
        if (!$cours || $cours->getTuteur()->getIdT() !== $session->get('idT')) {
            throw $this->createNotFoundException("Cours introuvable ou non autorisé.");
        }

        // Récupérer les évaluations des orphelins pour ce cours
        $ratings = $ratingRepository->findBy(['cours' => $cours]);

        // Calcul de la note moyenne
        $totalRating = 0;
        $totalOrphelins = count($ratings);
        foreach ($ratings as $rating) {
            $totalRating += $rating->getNote();
        }
        $averageRating = $totalOrphelins > 0 ? $totalRating / $totalOrphelins : 0;

        // Renvoyer la vue avec les données
        return $this->render('tuteur/cours_details.html.twig', [
            'cours' => $cours,
            'ratings' => $ratings,
            'averageRating' => $averageRating,
        ]);
    }

    #[Route('/tuteurs_qr', name: 'app_tuteurs_qr')]
    public function tuteursQr(TuteurRepository $tuteurRepository): Response
    {
        $tuteurs = $tuteurRepository->findAll();

        return $this->render('tuteur/qr_list.html.twig', [
            'tuteurs' => $tuteurs,
        ]);
    }
}
