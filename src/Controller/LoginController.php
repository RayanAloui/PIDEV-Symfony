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
use Symfony\Component\Form\FormError;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use ReCaptcha\ReCaptcha;
use App\Entity\Notification;
use App\Services\EmailService;
use App\Services\sms;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function index(Request $request, UserRepository $userRepository, SessionInterface $session, EmailService $emailService): Response
{
    // Get stored cookies for email and password (if any)
    $cookieEmail = $request->cookies->get('user_email');
    $cookiePassword = $request->cookies->get('user_password') ? Cryptage::decrypte($request->cookies->get('user_password')) : '123';



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
               
                if ($user->getIsBlocked()) {
                    $errorMessage = 'Your account is blocked. Please contact support.';
                } else {
                    // Check if the hashed password matches (consider using password_verify for hashed passwords)
                    if (Cryptage::crypte($password) == $user->getPassword()) {
                        $role = $user->getRole();

                        
                        $session->set('user_token', $user->getToken());

                        // Handle Remember Me checkbox
                        if ($rememberMe) {
                            setcookie('user_email', $user->getEmail(), time() + 30 * 24 * 60 * 60, '/');
                            setcookie('user_password', $user->getPassword(), time() + 30 * 24 * 60 * 60, '/');
                        } else {
                            setcookie('user_email', '', time() - 3600, '/');
                            setcookie('user_password', '', time() - 3600, '/');
                        }

                        if( ($user->getIsConfirmed() )  && ($user->getRole() ==="client"  )){

                            $emailService->sendEmail($user->getEmail(), "token for 2AUTH :", $user->getToken());
                            return $this->render('login/2AUTH.html.twig', [
                                'user' => $user,
                            ]);

                        }else{
                            // Store session data
                        $session->set('user_id', $user->getId());
                        $session->set('user_email', $user->getEmail());
                        $session->set('user_role', $role);

                            return $this->redirectToRoute($role === 'admin' ? 'app_user_index' : 'app_front');

                        }

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
        $session->clear();
        return $this->redirectToRoute('app_login');
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ReCaptcha $reCaptcha
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);
    
        if ($form->isSubmitted()) {
            // Get the reCAPTCHA token from the hidden input field
            $recaptchaToken = $request->request->get('recaptcha_token');
            
            // Verify the reCAPTCHA token
            $response = $reCaptcha->verify($recaptchaToken);
            
           
    
            if ($form->isValid()) {
                // Proceed with user registration logic
                $user->setIsBlocked(0);
                $user->setIsConfirmed(0);
                $user->setRole('client');
                $user->setNumberVerification(random_int(100000, 999999));
                $user->setToken(0);
                $user->setImage(null);
    
                // Hash the password
                $originalPassword = $user->getPassword();
                $cryptedPassword = Cryptage::crypte($originalPassword);
                $user->setPassword($cryptedPassword);
    
                // Persist the user entity
                $entityManager->persist($user);
                $entityManager->flush();

                // Create a new notification
            $notification = new Notification();
            $notification->setDate(new \DateTime());
            $notification->setHeure((new \DateTime())->format('H:i'));
            $notification->setActivite('Register From Web');
            $notification->setUsername($user->getEmail()); // or getUsername() if that makes more sense

            $entityManager->persist($notification);
            $entityManager->flush();
    
                // Redirect to login
                return $this->redirectToRoute('app_login');
            }
        }
    
        return $this->render('registration/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    
    
        #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET'])]
        public function forgotPassword(): Response
        {
            return $this->render('login/forgot_password.html.twig');
        }
    




        #[Route('/send-sms-code', name: 'app_send_sms_code', methods: ['POST'])]
public function sendSmsCode(
    Request $request,
    UserRepository $userRepository,
    SessionInterface $session,
    sms $smsService
): Response {
    $phone = $request->request->get('phoneNumber');
    $cleanPhone = preg_replace('/\D/', '', $phone);

    $user = $userRepository->findOneBy(['telephone' => $cleanPhone]);

    if (!$user) {
        return $this->render('login/forgot_password.html.twig', [
            'errorMessage' => 'This phone number is not registered.',
        ]);
    }

    $code = $user->getNumberVerification();

    // Save the verification code in the session instead of email
    $session->set('user_verification_code', $code);

    // Send SMS
    $smsService->sendSms(
        '+216' . $user->getTelephone(),
        "Bonjour {$user->getName()} {$user->getSurname()}, votre code de réinitialisation est : {$code}."
    );

    return $this->render('login/app_smsCode_verify.html.twig');
}

    




#[Route('/verify-sms-code', name: 'app_verify_sms_code', methods: ['POST'])]
public function verifySmsCode(
    Request $request,
    SessionInterface $session,
    UserRepository $userRepository
): Response {
    $submittedCode = $request->request->get('smsCode');
    $sessionCode = $session->get('user_verification_code');

    if (!$sessionCode) {
        return $this->redirectToRoute('app_forgot_password');
    }

    $user = $userRepository->findOneBy(['numberverification' => $sessionCode]);

    if (!$user) {
        return $this->render('login/app_smsCode_verify.html.twig', [
            'errorMessage' => 'User not found.',
        ]);
    }

    if ($submittedCode == $sessionCode) {
        return $this->render('login/newPWD.html.twig');
    } else {
        return $this->render('login/app_smsCode_verify.html.twig', [
            'errorMessage' => 'Invalid verification code. Please try again.',
        ]);
    }
}









#[Route('/update-password', name: 'app_update_password', methods: ['GET', 'POST'])]
public function updatePassword(Request $request, UserRepository $userRepository, SessionInterface $session, EntityManagerInterface $entityManager): Response
{
    $sessionCode = $session->get('user_verification_code');
    if (!$sessionCode) {
        return $this->redirectToRoute('app_login');
    }

    $user = $userRepository->findOneBy(['numberverification' => $sessionCode]);

    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    if ($request->isMethod('POST')) {
        $newPassword = $request->request->get('newPassword');
        $confirmPassword = $request->request->get('confirmPassword');

        if ($newPassword !== $confirmPassword) {
            return $this->render('login/newPWD.html.twig', [
                'errorMessage' => 'Passwords do not match. Please try again.'
            ]);
        }

        $cryptedPassword = Cryptage::crypte($newPassword);

        $user->setPassword($cryptedPassword);
        $user->setNumberVerification(random_int(100000, 999999)); // Important to refresh the code

        $entityManager->persist($user);
        $entityManager->flush();

        $session->clear();
        return $this->redirectToRoute('app_login');
    }

    $session->clear();
    return $this->render('login/newPWD.html.twig');
}

    



#[Route('/verify-token', name: 'app_verify_token', methods: ['POST'])]
public function verifyToken(Request $request, UserRepository $userRepository, SessionInterface $session, EntityManagerInterface $entityManager): Response
{
    // Get the values from the form inputs
    $code1 = $request->request->get('code1');
    $code2 = $request->request->get('code2');
    $code3 = $request->request->get('code3');
    $code4 = $request->request->get('code4');

    // Check if the user didn't fill out the form (all inputs are empty)
    if (empty($code1) && empty($code2) && empty($code3) && empty($code4)) {
        // Clear session if the user decides to go elsewhere or doesn't fill out the form
        $session->clear();
        return $this->redirectToRoute('app_login'); // Redirect to login or any other page
    }

    // Combine the 4 parts into a single token string
    $submittedToken = $code1 . $code2 . $code3 . $code4;
    $submittedToken = trim($submittedToken);

    // Retrieve the email from the session (assuming the email is stored in the session during the flow)
    $userToken = $session->get('user_token');
    $user = $userRepository->findOneBy(['token' => $userToken]);

    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    $storedToken = $user->getToken();

    if (trim((string) $submittedToken) !== trim((string) $storedToken)) {
        return $this->render('login/2AUTH.html.twig', [
            'error_message' => 'Invalid token. Please try again.'
        ]);
    }

    // Generate a new token after successful verification
    $newToken = rand(1000, 9999);
    $user->setToken((string) $newToken);

    // Persist changes to the database
    $entityManager->persist($user);
    $entityManager->flush();

        // Store session data
        $session->set('user_id', $user->getId());
        $session->set('user_email', $user->getEmail());
        $session->set('user_role', $user->getRole());
    // Redirect to the front page after successful verification
    return $this->redirectToRoute('app_front');
}


    
 

#[Route('/google-api', name: 'google_api')]
public function googleApiSignUp(Request $request, EntityManagerInterface $entityManager, SessionInterface $session): Response
{
    $clientId = '246355943534-amsvb04oiull1bmu1blferldutq705oh.apps.googleusercontent.com';
    $clientSecret = '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';
    $redirectUri = 'http://127.0.0.1:8000/google-api'; // IMPORTANT!! Must match exactly

    // If we have ?code= in URL, it's callback phase
    $code = $request->query->get('code');
    if ($code) {
        // Exchange code for access token
        $tokenResponse = file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query([
                    'code' => $code,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect_uri' => $redirectUri,
                    'grant_type' => 'authorization_code',
                ]),
            ]
        ]));

        $data = json_decode($tokenResponse, true);
        if (isset($data['error'])) {
            return new Response('Error: ' . $data['error_description']);
        }

        $accessToken = $data['access_token'];

        // Fetch user details
        $userInfoResponse = file_get_contents('https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $accessToken);
        $userData = json_decode($userInfoResponse, true);

        $email = $userData['email'] ?? null;
        $name = $userData['name'] ?? null;

        if (!$email) {
            return new Response("
                <script>
                    alert('No email returned from Google.');
                    window.location.href = '" . $this->generateUrl('app_login') . "';
                </script>
            ");
        }

        // Check if user already exists
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        $session->set('user_email', $email);
        $session->set('user_role', "client");

        if (!$user) {
             $session->set('user_email', $email);
            $session->set('user_role', "client");
            // Create new user
            $user = new User();
            $user->setEmail($email);
            $user->setName($name);
            $user->setSurname($name);
            $user->setPassword('GOOGLE_ACCOUNT');
            $user->setTelephone(12345678);
            $user->setRole("client");
            $user->setIsBlocked(0);
            $user->setIsConfirmed(0);
            $user->setNumberVerification(random_int(100000, 999999)); // Generate a random 6-digit number
            $user->setToken(0);
            $user->setImage(NULL);
            $entityManager->persist($user);
            $entityManager->flush();
            $session->set('user_email', $email);
            $session->set('user_role', "client");


              // Create a new notification
              $notification = new Notification();
              $notification->setDate(new \DateTime());
              $notification->setHeure((new \DateTime())->format('H:i'));
              $notification->setActivite('Register From  API GOOGLE WITH WEB');
              $notification->setUsername($user->getEmail()); // or getUsername() if that makes more sense
  
              $entityManager->persist($notification);
              $entityManager->flush();
        }

        // After sign up -> redirect to login page
        return new Response("
            <script>
                alert('Signup successful! Please login.');
                window.location.href = '" . $this->generateUrl('app_front') . "';
            </script>
        ");
    }

    // Else: No code yet -> redirect user to Google's login/signup page
    $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'offline',
        'prompt' => 'consent',
    ]);

    return new RedirectResponse($url);
}




























    
    
    
  
}
