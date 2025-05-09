<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Form\NotificationType;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/notification')]
final class NotificationController extends AbstractController
{
    #[Route(name: 'app_notification_index', methods: ['GET'])]
public function index(NotificationRepository $notificationRepository, Request $request): Response
{
    $page = max(1, $request->query->getInt('page', 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $totalNotifications = $notificationRepository->count([]);

    // Récupération paginée
    $notifications = $notificationRepository->findBy([], ['id' => 'DESC'], $limit, $offset);

    // Groupement par date (en format 'Y-m-d' pour regroupement)
    $groupedNotifications = [];
    foreach ($notifications as $notification) {
        $dateKey = $notification->getDate()->format('Y-m-d');
        if (!isset($groupedNotifications[$dateKey])) {
            $groupedNotifications[$dateKey] = [];
        }
        $groupedNotifications[$dateKey][] = $notification;
    }

    krsort($groupedNotifications); // Tri inverse pour avoir les jours les plus récents en haut

    return $this->render('notification/index.html.twig', [
        'notifications' => $notifications,
        'notificationsGroupedByDate' => $groupedNotifications,
        'currentPage' => $page,
        'totalPages' => max(1, ceil($totalNotifications / $limit)),
        'totalNotifications' => $totalNotifications,
    ]);
}


    
    

    #[Route('/new', name: 'app_notification_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $notification = new Notification();
        $form = $this->createForm(NotificationType::class, $notification);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($notification);
            $entityManager->flush();

            return $this->redirectToRoute('app_notification_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('notification/new.html.twig', [
            'notification' => $notification,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_notification_show', methods: ['GET'])]
    public function show(Notification $notification): Response
    {
        return $this->render('notification/show.html.twig', [
            'notification' => $notification,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_notification_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Notification $notification, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NotificationType::class, $notification);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_notification_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('notification/edit.html.twig', [
            'notification' => $notification,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_notification_delete', methods: ['POST'])]
    public function delete(Request $request, Notification $notification, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$notification->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($notification);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_notification_index', [], Response::HTTP_SEE_OTHER);
    }
}
