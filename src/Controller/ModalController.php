<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ModalController extends AbstractController
{
    #[Route('/auth/modal/{view}', name: 'app_auth_modal')]
    public function modal(string $view): Response
    {
        // ✅ Liste blanche des vues autorisées
        $allowed = ['register', 'login', 'forgot', 'verify_code'];

        if (!in_array($view, $allowed)) {
            throw $this->createNotFoundException('Vue non autorisée');
        }

        // ✅ On récupère la clé publique du reCAPTCHA (depuis .env ou services.yaml)
        $recaptchaSiteKey = $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'] ?? '';

        // ✅ On passe la clé à Twig
        return $this->render("_partials/auth_{$view}.html.twig", [
            'recaptcha_site_key' => $recaptchaSiteKey,
        ]);
    }
}
