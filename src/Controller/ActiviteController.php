<?php

namespace App\Controller;
use App\Services\EmailService;
use App\Entity\Activite;
use App\Entity\Participant;
use App\Entity\User;
use App\Form\ActiviteType;
use App\Repository\ActiviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use SebastianBergmann\Environment\Console;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Dompdf\Options;
use Dompdf\Dompdf;
use App\Repository\UserRepository;
use App\Services\WhatsAppService;
#[Route('/activite')]
final class ActiviteController extends AbstractController
{
    #[Route(name: 'app_activite_index', methods: ['GET'])]
    public function index(ActiviteRepository $activiteRepository, Request $request): Response
    {
        $sortParam = $request->query->get('sort', null);
        $searchQuery = $request->query->get('search', null);
        
        // Build the query with search and sort parameters
        $queryBuilder = $activiteRepository->createQueryBuilder('a');
    
        if ($searchQuery) {
            $queryBuilder
                ->where('a.nom LIKE :query OR a.categorie LIKE :query OR a.lieu LIKE :query OR a.nomDuTuteur LIKE :query')
                ->setParameter('query', '%' . $searchQuery . '%');
        }
    
        if ($sortParam) {
            if ($sortParam === 'categorie') {
                $queryBuilder->orderBy('a.categorie', 'ASC');
            } elseif ($sortParam === 'duree') {
                $queryBuilder->orderBy('a.duree', 'ASC');
            }
        }
    
        $activites = $queryBuilder->getQuery()->getResult();
    
        // Calculate statut counts for the sidebar
        $statutCounts = [];
        foreach ($activites as $activite) {
            $statut = $activite->getStatut() ?? 'Non défini';
            $statutCounts[$statut] = ($statutCounts[$statut] ?? 0) + 1;
        }
        
        // Check if it's an AJAX request
        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            // Return only the table partial for AJAX requests
            return $this->render('activite/_activites_list.html.twig', [
                'activites' => $activites
            ]);
        }
        
        // Return the full page for regular requests
        return $this->render('activite/index.html.twig', [
            'activites' => $activites,
            'search' => $searchQuery,
            'sort' => $sortParam,
            'statutCounts' => $statutCounts,
        ]);
    }

    #[Route('/new', name: 'app_activite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $activite = new Activite();
        $form = $this->createForm(ActiviteType::class, $activite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($activite);
            $entityManager->flush();
            return $this->redirectToRoute('app_activite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activite/new.html.twig', [
            'activite' => $activite,
            'form' => $form,
        ]);
    }

    #[Route('/show/{id}', name: 'app_activite_show', methods: ['GET'])]
    public function show(Activite $activite): Response
    {
        return $this->render('activite/show.html.twig', [
            'activite' => $activite,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_activite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Activite $activite, EntityManagerInterface $entityManager,WhatsAppService $whatsAppService): Response
    {
        $form = $this->createForm(ActiviteType::class, $activite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $message = "✅ L activite a ete modifier ! nous vous envoyons les nouvelles changements. 🙏 - OrphanCare";
            $whatsAppService->sendConfirmationMessage($message);
            return $this->redirectToRoute('app_activite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activite/edit.html.twig', [
            'activite' => $activite,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_activite_delete', methods: ['POST'])]
    public function delete(Request $request, Activite $activite, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $activite->getId(), $request->request->get('_token'))) {
            $entityManager->remove($activite);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_activite_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/front', name: 'app_activite_front', methods: ['GET'])]
    public function front(ActiviteRepository $activiteRepository, Request $request): Response
    {
        $searchQuery = $request->query->get('search', null);
        $filterParam = $request->query->get('filter', null);

        $queryBuilder = $activiteRepository->createQueryBuilder('a');

        if ($searchQuery) {
            $queryBuilder
                ->andWhere('a.nom LIKE :query OR a.categorie LIKE :query OR a.lieu LIKE :query')
                ->setParameter('query', '%' . $searchQuery . '%');
        }

        if ($filterParam) {
            $queryBuilder
                ->andWhere('a.statut LIKE :query')
                ->setParameter('query', '%' . $filterParam . '%');

        }

        $queryBuilder->orderBy('a.duree', 'ASC');

        $activites = $queryBuilder->getQuery()->getResult();

        return $this->render('activite/front.html.twig', [
            'activites' => $activites,
            'search' => $searchQuery,
            'filter' => $filterParam,

        ]);
    }

    #[Route('/{id}/join', name: 'app_activite_join', methods: ['POST'])]
    public function join( EmailService $emailService,Activite $activite, EntityManagerInterface $entityManager, UserRepository $userRepository, SessionInterface $session): Response
    {
        $email = $session->get('user_email');
        $user = $userRepository->findOneBy(['email' => $email]);
        $userid = $user->getId();

        if ($activite->getStatut() === 'Completed') {
            $this->addFlash('error', 'Cette activité est terminée et ne peut plus être rejointe.');
            return $this->redirectToRoute('app_activite_front');
        }

        $existingParticipation = $entityManager->getRepository(Participant::class)
            ->findOneBy(['user' => $user, 'activite' => $activite]);

        if ($existingParticipation) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit à cette activité.');
            return $this->redirectToRoute('app_activite_front');
        }

        $participant = new Participant();
        $participant->setUser($user);
        $participant->setActivite($activite);

        $entityManager->persist($participant);
        $entityManager->flush();

        $emailService->sendEmail($user->getEmail(), $user->getName(),"Nous tenons a vous exprimer notre sincere gratitude pour avoir pris part a [nom de l'activite]. Votre engagement et votre enthousiasme ont grandement contribue a la reussite de cet evenement. Grace a votre participation, nous avons pu offrir une experience enrichissante et memorable pour tous les participants.

Nous esperons que vous avez apprecie l'activite autant que nous avons apprecie vous y voir. N'hesitez pas a nous faire part de vos retours ou suggestions pour ameliorer nos futures initiatives.

Encore un grand merci pour votre implication !

Cordialement,");

        $this->addFlash('success', 'Vous avez rejoint l\'activité avec succès !');
        return $this->redirectToRoute('app_activite_front');
    }

    #[Route('/{id}/export-pdf', name: 'app_activite_export_pdf', methods: ['GET'])]
    public function exportPdf(Activite $activite): Response
    {
        // Configure Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        // Instantiate Dompdf
        $dompdf = new Dompdf($options);
        
        // Render the activity details in HTML
        $html = $this->renderView('activite/pdf.html.twig', [
            'activite' => $activite,
        ]);
        
        // Load HTML to Dompdf
        $dompdf->loadHtml($html);
        
        // Set paper size and orientation
        $dompdf->setPaper('A4', 'portrait');
        
        // Render the PDF
        $dompdf->render();
        
        // Generate a filename
        $filename = 'activite-' . $activite->getId() . '-' . date('Y-m-d') . '.pdf';
        
        // Return the PDF as a response
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]
        );
    }
    
    #[Route('/export-all-pdf', name: 'app_activite_export_all_pdf', methods: ['GET'])]
    public function exportAllPdf(ActiviteRepository $activiteRepository, Request $request): Response
    {
        // Get the filtered activities (reusing logic from front method)
        $searchQuery = $request->query->get('search', null);
        $filterParam = $request->query->get('filter', null);

        $queryBuilder = $activiteRepository->createQueryBuilder('a');

        if ($searchQuery) {
            $queryBuilder
                ->andWhere('a.nom LIKE :query OR a.categorie LIKE :query OR a.lieu LIKE :query')
                ->setParameter('query', '%' . $searchQuery . '%');
        }

        if ($filterParam) {
            $queryBuilder
                ->andWhere('a.statut LIKE :query')
                ->setParameter('query', '%' . $filterParam . '%');
        }

        $activites = $queryBuilder->getQuery()->getResult();
        
        // Configure Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        
        // Instantiate Dompdf
        $dompdf = new Dompdf($options);
        
        // Render the activities list in HTML
        $html = $this->renderView('activite/pdf_list.html.twig', [
            'activites' => $activites,
        ]);
        
        // Load HTML to Dompdf
        $dompdf->loadHtml($html);
        
        // Set paper size and orientation
        $dompdf->setPaper('A4', 'portrait');
        
        // Render the PDF
        $dompdf->render();
        
        // Generate a filename
        $filename = 'liste-activites-' . date('Y-m-d') . '.pdf';
        
        // Return the PDF as a response
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]
        );
    }
}