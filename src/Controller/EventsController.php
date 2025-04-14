<?php

namespace App\Controller;

use App\Entity\Events;
use App\Form\EventsType;
use App\Repository\EventsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/crud/events')]
class EventsController extends AbstractController
{
    #[Route('/list', name: 'app_crud_events', methods: ['GET'])]
    public function list(EventsRepository $repository): Response
    {
        $events = $repository->findAll();
        return $this->render('events/list.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/add', name: 'app_crud_events_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = new Events();
        $form = $this->createForm(EventsType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
}