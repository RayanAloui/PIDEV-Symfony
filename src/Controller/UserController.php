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

use App\Form\RegistrationType;
use App\Form\AddUserType;

use App\Service\Cryptage;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Services\MailerService;
use App\Services\SmsService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


use Knp\Snappy\Pdf;

use Twig\Environment;



#[Route('/user')]
final class UserController extends AbstractController
{
    
    
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, Request $request, SessionInterface $session): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$session->get('user_email') || $session->get('user_role') !== 'admin') {
            return $this->redirectToRoute('app_login');
        }
    
        // Paramètre de tri uniquement
        $sortParam = $request->query->get('sort');
        $searchTerm = $request->query->get('search');
    
        // Construction de la requête
        $queryBuilder = $userRepository->createQueryBuilder('u');
    
        if ($sortParam === 'email') {
            $queryBuilder->orderBy('u.email', 'ASC');
        } elseif ($sortParam === 'role') {
            $queryBuilder->orderBy('u.role', 'ASC');
        }
        if (!empty($searchTerm)) {
            $queryBuilder
                ->where('u.name LIKE :search')
                ->orWhere('u.surname LIKE :search')
                ->orWhere('u.email LIKE :search')
                ->orWhere('u.role LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }
    
        $users = $queryBuilder->getQuery()->getResult();
    
        // Données pour les graphiques
        $activeCount = count(array_filter($users, fn($u) => !$u->getIsBlocked()));
        $blockedCount = count(array_filter($users, fn($u) => $u->getIsBlocked()));
        $confirmedCount = count(array_filter($users, fn($u) => $u->getIsConfirmed()));
        $unconfirmedCount = count(array_filter($users, fn($u) => !$u->getIsConfirmed()));
    
        $admins = count(array_filter($users, fn($u) => $u->getRole() === 'admin'));
        $clients = count(array_filter($users, fn($u) => $u->getRole() === 'client'));
        $orphelins = count(array_filter($users, fn($u) => $u->getRole() === 'orphelin'));
        $tuteurs = count(array_filter($users, fn($u) => $u->getRole() === 'tuteur'));
    
        // Requête AJAX
        if ($request->isXmlHttpRequest()) {
            return $this->render('user/_user_list.html.twig', [
                'users' => $users,
            ]);
        }
    
        // Vue complète
        return $this->render('user/index.html.twig', [
            'users' => $users,
            'sort' => $sortParam,
            'search' => $searchTerm, // <-- ajoute ceci
            'activeCount' => $activeCount,
            'blockedCount' => $blockedCount,
            'confirmedCount' => $confirmedCount,
            'unconfirmedCount' => $unconfirmedCount,
            'admins' => $admins,
            'clients' => $clients,
            'orphelins' => $orphelins,
            'tuteurs' => $tuteurs,
        ]);
        
    }
    
    
    

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager , SessionInterface $session): Response
    {

             // Check if the user is logged in by checking session
    if (!$session->get('user_email')) {
        return $this->redirectToRoute('app_login'); // Redirect to login page if not logged in
    } if($session->get('user_role')!="admin"){
        return $this->redirectToRoute('app_login');

    }


        $user = new User();
        $form = $this->createForm(AddUserType::class, $user);
        $form->handleRequest($request);
    
        if ($form->isSubmitted()) {
            // Check if form is valid and perform validation
            if ($form->isValid()) {
                // Set default values
                $user->setIsBlocked(0);
                $user->setIsConfirmed(0);
                $user->setNumberVerification(random_int(100000, 999999)); // Generate a random 6-digit number
                $user->setToken(0);
                $user->setImage(NULL);

                $originalPassword = $user->getPassword();
                $cryptedPassword = Cryptage::crypte($originalPassword);
                $user->setPassword($cryptedPassword);
    
                $entityManager->persist($user);
                $entityManager->flush();
    
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
            } else {
                // If the form is not valid, you might want to handle specific actions here
                // Such as logging, debugging, or just ensuring the errors show up in the template
            }
        }
    
        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
    

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        // Déchiffrer le mot de passe
        $decryptedPassword = Cryptage::decrypte($user->getPassword());
    
        return $this->render('user/show.html.twig', [
            'user' => $user,
            'decryptedPassword' => $decryptedPassword,
        ]);
    }

    
 

#[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, User $user, EntityManagerInterface $entityManager, SessionInterface $session): Response
{
    // Vérifier si l'utilisateur est connecté
    if (!$session->get('user_email')) {
        return $this->redirectToRoute('app_login');
    }
    if($session->get('user_role')!="admin"){
        return $this->redirectToRoute('app_login');

    }
    

    
    $form = $this->createForm(UserType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
       
        $submittedPassword = $form->get('password')->getData(); 

        // Si le mot de passe a été modifié
        if (!empty($submittedPassword)) {
            $user->setPassword(Cryptage::crypte($submittedPassword)); 
        }else{
            $user->setPassword($user->getPassword());
        }

        // Persist les modifications
        $entityManager->flush();

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('user/edit.html.twig', [
        'user' => $user,
        'form' => $form->createView(),
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
    public function blockUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setIsBlocked(1);
        $entityManager->flush();

        $this->addFlash('warning', 'User has been blocked.');
        return $this->redirectToRoute('app_user_index');
    }


        #[Route('/{id}/activate', name: 'app_user_activate', methods: ['POST'])]
    public function activateUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setIsBlocked(0); 
        $entityManager->flush();

        $this->addFlash('success', 'User has been activated.');
        return $this->redirectToRoute('app_user_index');
    }


    

    #[Route('/users/pdf', name: 'users_pdf')]
    public function exportUsersPdf(EntityManagerInterface $entityManager): Response
    {
        // Récupérer les utilisateurs
        $users = $entityManager->getRepository(User::class)->findAll();
    
        // Configuration de Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true); // Important pour que les images locales fonctionnent
        $dompdf = new Dompdf($pdfOptions);
    
        // HTML à rendre
        $html = $this->renderView('user/users_pdf.html.twig', [
            'users' => $users,
            'currentDate' => (new \DateTime())->format('d/m/Y H:i')
        ]);
    
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Liste_Utilisateurs.pdf"',
        ]);
    }




 

}
