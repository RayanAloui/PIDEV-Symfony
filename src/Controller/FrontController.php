<?php

namespace App\Controller;
use App\Service\Cryptage;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Services\EmailService;





class FrontController extends AbstractController
{
    #[Route('/front', name: 'app_front')]
    public function frontPage(SessionInterface $session): Response
    {
        // Redirect to login if no user session exists
        if (!$session->get('user_email')) {
            return $this->redirectToRoute('app_login');
        }

        // Render the front page if the user is logged in
        return $this->render('front/index.html.twig');
    }

    #[Route('/profile', name: 'app_profile_index')]
    public function profilePage(UserRepository $userRepository, SessionInterface $session): Response
    {
        // Check if the user is logged in via session
        $userEmail = $session->get('user_email');
        
        // If no user is logged in, redirect to login
        if (!$userEmail) {
            return $this->redirectToRoute('app_login');
        }

        // Get the user data from the database based on their email
        $user = $userRepository->findOneBy(['email' => $userEmail]);

        // If no user is found, redirect to login
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Pass user data to the profile page
        return $this->render('front/profile.html.twig', [
            'user' => $user
        ]);
    }
    #[Route('/profile/update', name: 'app_profile_update', methods: ['POST'])]
    public function updateProfile(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        SessionInterface $session
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $userEmail = $session->get('user_email');
        if (!$userEmail) {
            return $this->redirectToRoute('app_login');
        }
    
        // Récupérer l'utilisateur à partir de son email
        $user = $userRepository->findOneBy(['email' => $userEmail]);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
    
        // Mettre à jour les champs modifiables
        $user->setName($request->request->get('name'));
        $user->setSurname($request->request->get('surname'));
    
        // Sauvegarder les modifications
        $entityManager->flush();
    
        return $this->redirectToRoute('app_profile_index');
    }


    #[Route('/change-password', name: 'app_change_password')]
    public function changePassword(UserRepository $userRepository,Request $request, EntityManagerInterface $em, SessionInterface $session): \Symfony\Component\HttpFoundation\Response
    {
        
        $error = null;
        $success = null;
         // Check if the user is logged in via session
         $userEmail = $session->get('user_email');
        
         // If no user is logged in, redirect to login
         if (!$userEmail) {
             return $this->redirectToRoute('app_login');
         }
 
         // Get the user data from the database based on their email
         $user = $userRepository->findOneBy(['email' => $userEmail]);

        if ($request->isMethod('POST')) {
            $oldPassword = $request->request->get('oldPassword');
            $newPassword = $request->request->get('newPassword');
            $confirmPassword = $request->request->get('confirmPassword');

            if (Cryptage::crypte($oldPassword) !== $user->getPassword()) {
                $error = "Incorrect old password.";
            } elseif ($newPassword !== $confirmPassword) {
                $error = "New passwords do not match.";
            } elseif (strlen($newPassword) < 6) {
                $error = "Password must be at least 6 characters.";
            } else {
                $user->setPassword(Cryptage::crypte($newPassword));
                $em->persist($user);
                $em->flush();

                $success = "Password updated successfully.";
            }
        }

        return $this->render('front/change_password.html.twig', [
            'error' => $error,
            'success' => $success,
        ]);
    }
    #[Route('/confirm-email', name: 'app_confirm_email')]
    public function confirmEmail(
        UserRepository $userRepository,
        SessionInterface $session,
        EmailService $emailService
    ): Response {
        // Get the logged-in user's email from the session
        $userEmail = $session->get('user_email');
        
        // If no user is logged in, redirect to login
        if (!$userEmail) {
            return $this->redirectToRoute('app_login');
        }
    
        // Get the user entity from the database based on the email
        $user = $userRepository->findOneBy(['email' => $userEmail]);
    
        // If the user is not found, redirect to login
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
    
        // Retrieve the verification code from the user entity
        $verificationCode = $user->getNumberVerification();
        
        // Send the verification code to the user's email
        $subject = 'Email Verification';
        $body = 'Your verification code is: <strong>' . $verificationCode . '</strong>';
        
        // Call your email service to send the email
        $emailService->sendEmail($userEmail, $subject, $body);
    
        // Render the confirm email page
        return $this->render('front/confirm_email.html.twig');
    }
    


}
