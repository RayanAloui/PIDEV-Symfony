<?php

namespace App\Controller;

use App\Entity\Events;
use App\Form\EventsType;
use App\Service\EventImageService;
use App\Repository\EventsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/crud/events')]
class EventsController extends AbstractController
{
    private $httpClient;
    private $eventImageService;


    public function __construct(HttpClientInterface $httpClient, EventImageService $eventImageService)
    {
        $this->httpClient = $httpClient;
        $this->eventImageService = $eventImageService; // Mets ta vraie clé API Gemini ici
    }
    #[Route('/list', name: 'app_crud_events', methods: ['GET'])]
    public function list(Request $request,EventsRepository $repository): Response
    {
        $query = $request->query->get('query');
        
        // Champs de tri
        $sortField = $request->query->get('sort', 'nom'); // champ par défaut
        $sortOrder = $request->query->get('order', 'asc'); // ordre par défaut
        
        // Requête : recherche avec tri ou simple tri
        if ($query) {
            $events = $repository->searchEvents($query, $sortField, $sortOrder);
        } else {
            $events = $repository->findBy([], [$sortField => $sortOrder]);
        }
        
        return $this->render('events/list.html.twig', [
            'events' => $events,
            'sortField' => $sortField,
            'sortOrder' => $sortOrder,
            'query' => $query,
        ]);
    }

    #[Route('/add', name: 'app_crud_events_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = new Events();
        $form = $this->createForm(EventsType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer une image pour l'événement
            $imagePath = $this->eventImageService->getImageForEvent(
                $event->getNom(),
                $event->getDescription(),
                $event->getLieu()
            );
            
            // Si une image a été trouvée, l'associer à l'événement
            if ($imagePath) {
                $event->setImage($imagePath);
            }
            $entityManager->persist($event);
            $entityManager->flush();
            $this->addFlash('success', 'Événement ajouté avec succès.');
            return $this->redirectToRoute('app_crud_events');
        }

        return $this->render('events/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'app_crud_events_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, EventsRepository $eventsRepository, int $id): Response
    {
        $event = $eventsRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException("Événement non trouvé !");
        }

        $form = $this->createForm(EventsType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_crud_events');
        }

        return $this->render('events/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_crud_events_delete')]
    public function delete(EntityManagerInterface $entityManager, EventsRepository $eventsRepository, int $id): Response
    {
        $event = $eventsRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException("Événement non trouvé !");
        }

        $entityManager->remove($event);
        $entityManager->flush();

        return $this->redirectToRoute('app_crud_events');
    }
    #[Route('/events', name: 'app_events')]
    public function index(EventsRepository $eventRepository): Response
    {
        $events = $eventRepository->findAll();
        return $this->render('events/listFront.html.twig', [
        'events' => $events
        ]);
    }

    #[Route('/search', name: 'app_crud_events_search', methods: ['GET'])]
    public function search(Request $request, EventsRepository $repository): JsonResponse
    {
        try {
            $query = $request->query->get('query', '');
            $sortField = $request->query->get('sort', 'nom');
            $sortOrder = $request->query->get('order', 'asc');
        
            $events = $repository->searchEvents($query, $sortField, $sortOrder);
        
            $results = [];
            foreach ($events as $event) {
                $results[] = [
                    'id' => $event->getIdEvent(),
                    'nom' => $event->getNom(),
                    'dateEvent' => $event->getDateEvent()->format('d/m/Y'),
                    'lieu' => $event->getLieu(),
                    'description' => (strlen($event->getDescription()) > 30) ? 
                        substr($event->getDescription(), 0, 30) . '...' : $event->getDescription(),
                    'editUrl' => $this->generateUrl('app_crud_events_edit', ['id' => $event->getIdEvent()]),
                    'deleteUrl' => $this->generateUrl('app_crud_events_delete', ['id' => $event->getIdEvent()])
                ];
            }
        
            return new JsonResponse($results);
        } catch (\Exception $e) {
            // Log l'erreur et retourner une réponse d'erreur
            return new JsonResponse(['error' => 'Une erreur est survenue lors de la recherche: ' . $e->getMessage()], 500);
        }
    }


    
}