<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\Authenticator;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, Authenticator $authenticator, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setBalance(0);

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('CHANGE_THIS_EMAIL@yahoo.ro', 'CHANGE THIS NAME Triple Skin'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );
            // do anything else you need here, like send an email

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/steamLogin', name: 'app_steam_login', methods: 'POST')]
    public function steamLogin(Request $request): Response
    {
        $login_url_params = [
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => 'http://localhost:8080/steamLogin/authenticate',
            'openid.realm' => (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'],
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        ];

        $steam_login_url = 'https://steamcommunity.com/openid/login' . '?' . http_build_query($login_url_params, '', '&');

        header("location: $steam_login_url");

        return new JsonResponse([
            'status' => null,
        ]);
    }

    #[Route('/steamLogin/authenticate', name: 'app_steam_auth', methods: 'GET')]
    public function steamLoginAuth(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, Authenticator $authenticator, EntityManagerInterface $entityManager): Response
    {
        $params = [
            'openid.assoc_handle' => $_GET['openid_assoc_handle'],
            'openid.signed' => $_GET['openid_signed'],
            'openid.sig' => $_GET['openid_sig'],
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'check_authentication',
        ];

        $signed = explode(',', $_GET['openid_signed']);

        foreach ($signed as $item) {
            $val = $_GET['openid_' . str_replace('.', '_', $item)];
            $params['openid.' . $item] = stripslashes($val);
        }

        $data = http_build_query($params);
//data prep
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Accept-language: en\r\n" .
                    "Content-type: application/x-www-form-urlencoded\r\n" .
                    'Content-Length: ' . strlen($data) . "\r\n",
                'content' => $data,
            ],
        ]);

//get the data
        $result = file_get_contents('https://steamcommunity.com/openid/login', false, $context);

        if (preg_match("#is_valid\s*:\s*true#i", $result)) {
            preg_match('#^https://steamcommunity.com/openid/id/([0-9]{17,25})#', $_GET['openid_claimed_id'], $matches);
            $steamID64 = is_numeric($matches[1]) ? $matches[1] : 0;
//            echo 'request has been validated by open id, returning the client id (steam id) of: ' . $steamID64;

        } else {
//            echo 'error: unable to validate your request';
            return $this->redirectToRoute('app_register');
        }

        $steam_api_key = '1EDC0D204A7716E809F0B2DABE207BE7';

        $response = file_get_contents('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $steam_api_key . '&steamids=' . $steamID64);
        $response = json_decode($response, true);

        $userData = $response['response']['players'][0];

//        dd($_GET, $result, $userData, $userData['steamid']);

        $preUser = $entityManager->getRepository(User::class)->findOneBy(['steamid' => $userData['steamid']]);
        if ($preUser !== null) {
            return $userAuthenticator->authenticateUser(
                $preUser,
                $authenticator,
                $request
            );
        }

        $user = new User();

        $user->setPassword(
            $userPasswordHasher->hashPassword(
                $user,
                'asdasd'
            )
        );
//        $user->setEmail('nicolescu.andrei_18@yahoo.ru');

        $user->setIsVerified(1);
        $user->setBalance(100);
        $user->setSteamid($userData['steamid']);
        $user->setUsername($userData['personaname']);
        $user->setSteamurl($userData['profileurl']);
        $user->setAvatarurl($userData['avatarfull']);
        $user->setSteamCountry($userData['loccountrycode']);

        $entityManager->persist($user);
        $entityManager->flush();

        return $userAuthenticator->authenticateUser(
            $user,
            $authenticator,
            $request
        );

        /////////////////////s

//        $_SESSION['logged_in'] = true;
//        $_SESSION['userData'] = [
//            'steam_id' => $userData['steamid'],
//            'name' => $userData['personaname'],
//            'avatar' => $userData['avatarmedium'],
//        ];
//
//        $redirect_url = "dashboard.php";
//        header("Location: $redirect_url");
//        exit();
//
//        return new JsonResponse([
//            'status' => null,
//        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
    {
        $id = $request->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_register');
    }
}
