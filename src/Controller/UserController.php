<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        // Get the "sort" or "search" parameter from the query string
        $sortParam = $request->query->get('sort', null); // Default to null if no param is passed
        $searchQuery = $request->query->get('search', null); // Get search query if it exists
    
        // Build the query for filtering/searching
        $queryBuilder = $userRepository->createQueryBuilder('u');
    
        // Apply search filter if there's a search query
        if ($searchQuery) {
            $queryBuilder
                ->where('u.name LIKE :query OR u.surname LIKE :query OR u.email LIKE :query OR u.telephone LIKE :query')
                ->setParameter('query', '%'.$searchQuery.'%');
        }
    
        // Apply sorting if "sort" parameter is provided
        if ($sortParam) {
            if ($sortParam === 'email') {
                $queryBuilder->orderBy('u.email', 'ASC');
            } elseif ($sortParam === 'role') {
                $queryBuilder->orderBy('u.role', 'ASC');
            }
        }
    
        // Execute the query and get the results
        $users = $queryBuilder->getQuery()->getResult();
    
        // Count the active and blocked users
        $activeCount = count(array_filter($users, fn($user) => $user->getIsBlocked() === 0));
        $blockedCount = count(array_filter($users, fn($user) => $user->getIsBlocked() === 1));

        $confirmedCount = count(array_filter($users, fn($user) => $user->getIsConfirmed() === 1));
        $unconfirmedCount = count(array_filter($users, fn($user) => $user->getIsConfirmed() === 0)); 

    
        // Render the view and pass the users, query parameters, and chart data
        return $this->render('user/index.html.twig', [
            'users' => $users,
            'search' => $searchQuery, // Pass the current search term to the view
            'sort' => $sortParam,     // Pass the current sort option to the view
            'activeCount' => $activeCount, // Count of active users for the chart
            'blockedCount' => $blockedCount, // Count of blocked users for the chart
            'confirmedCount' => $confirmedCount, // Count of confirmed users
            'unconfirmedCount' => $unconfirmedCount, // Count of unconfirmed users
        ]);
    }
    
    

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Set default values
            $user->setIsBlocked(0);
            $user->setIsConfirmed(0);
            $user->setNumberVerification(random_int(100000, 999999)); // Generate a random 6-digit number
            $user->setToken(0);
    
            $entityManager->persist($user);
            $entityManager->flush();
    
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
    

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/{id}/block', name: 'app_user_block', methods: ['POST'])]
    public function blockUser(int $id, EntityManagerInterface $entityManager): Response
    {
        $conn = $entityManager->getConnection();
        $sql = "UPDATE user SET isBlocked = 1 WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id' => $id]);

        $this->addFlash('warning', 'User has been blocked.');
        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/{id}/activate', name: 'app_user_activate', methods: ['POST'])]
    public function activateUser(int $id, EntityManagerInterface $entityManager): Response
    {
        $conn = $entityManager->getConnection();
        $sql = "UPDATE user SET isBlocked = 0 WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id' => $id]);

        $this->addFlash('success', 'User has been activated.');
        return $this->redirectToRoute('app_user_index');
    }

    
}
