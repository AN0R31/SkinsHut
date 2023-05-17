<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public function denyAccessUnlessLoggedIn(): Response|null
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_landing');
        }
        return null;
    }

    #[Route(path: '', name: 'app_landing')]
    public function landing_render(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('home/landing.html.twig');
//        return $this->redirectToRoute('app_register');
    }

    #[Route(path: '/home', name: 'app_home')]
    public function home_render(): Response|null
    {
        $this->denyAccessUnlessLoggedIn();

        $user = $this->getUser();

        return $this->render('home/home.html.twig', [
            'user' => $user,
        ]);
    }
}
