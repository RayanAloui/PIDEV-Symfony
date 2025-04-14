<?php



namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; 
use App\Service\Cryptage;


final class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function index(Request $request, UserRepository $userRepository, SessionInterface $session): Response
{
    // Get stored cookies for email and password (if any)
    $cookieEmail = $request->cookies->get('user_email');
    $cookiePassword = $request->cookies->get('user_password');

    // If the user is already logged in, redirect to the front page
    if ($session->get('user_email')) {
        $role = $session->get('user_role');
        if ($role === 'admin') {
            return $this->redirectToRoute('app_user_index');
        }
        return $this->redirectToRoute('app_front');
    }

    $errorMessage = null;

    if ($request->isMethod('POST')) {
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $rememberMe = $request->request->get('remember_me');

        if ($username && $password) {
            $user = $userRepository->findOneBy(['email' => $username]);

            if ($user) {
                // Check if user is blocked
                if ($user->getIsBlocked()) {
                    $errorMessage = 'Your account is blocked. Please contact support.';
                } else {
                    // Check if the hashed password matches (consider using password_verify for hashed passwords)
                    if (Cryptage::crypte($password) == $user->getPassword()) {
                        $role = $user->getRole();

                        // Store session data
                        $session->set('user_id', $user->getId());
                        $session->set('user_email', $user->getEmail());
                        $session->set('user_role', $role);
                        

                        // Handle Remember Me checkbox
                        if ($rememberMe) {
                            setcookie('user_email', $user->getEmail(), time() + 30 * 24 * 60 * 60, '/');
                            setcookie('user_password', $user->getPassword(), time() + 30 * 24 * 60 * 60, '/');
                        } else {
                            setcookie('user_email', '', time() - 3600, '/');
                            setcookie('user_password', '', time() - 3600, '/');
                        }

                        return $this->redirectToRoute($role === 'admin' ? 'app_user_index' : 'app_front');
                    } else {
                        $errorMessage = 'Incorrect password.';
                    }
                }
            } else {
                $errorMessage = 'Email not found.';
            }
        } else {
            $errorMessage = 'Please enter both email and password.';
        }
    }

    return $this->render('login/index.html.twig', [
        'error_message' => $errorMessage,
        'cookie_email' => $cookieEmail,
        'cookie_password' => $cookiePassword,
    ]);
}


    #[Route('/logout', name: 'app_logout')]
    public function logout(SessionInterface $session): Response
    {
        $session->invalidate();
        return $this->redirectToRoute('app_login');
    }


    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
           
            $user->setIsBlocked(0);
            $user->setIsConfirmed(0);
            $user->setRole('client'); 
            $user->setNumberVerification(random_int(100000, 999999)); 
            $user->setToken(0); 
            $user->setImage(null);

            $originalPassword = $user->getPassword();
            $cryptedPassword = Cryptage::crypte($originalPassword);
            $user->setPassword($cryptedPassword);
    
          
          
         
    
           
            $entityManager->persist($user);
            $entityManager->flush();
    
            
            return $this->redirectToRoute('app_login');
        }
    
        return $this->render('registration/register.html.twig', [
            'form' => $form->createView(),
            
        ]);
    }
    
  
}
