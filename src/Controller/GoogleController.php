<?php

// src/Controller/GoogleController.php
namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'oauth_google_start')]
    public function connect(ClientRegistry $clientRegistry)
    {
        // redirige vers Google
        return $clientRegistry->getClient('google')->redirect(['email', 'profile']);
    }

    #[Route('/connect/google/check', name: 'oauth_google_check')]
    public function connectCheck(): Response
    {
        // gestion du retour Google
        return $this->redirectToRoute('dashboard');
    }
}
