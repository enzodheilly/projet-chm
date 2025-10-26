<?php

namespace App\Controller;

use App\Service\SystemLoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, SystemLoggerService $logger): Response
    {
        $session = $request->getSession();

        // ✅ Nettoyage des doublons de flash "info" (utile après redirection depuis la newsletter)
        if ($request->query->getBoolean('newsletter_required') && $session) {
            $flashBag = $session->getFlashBag();
            $infos = $flashBag->peek('info');

            if (!empty($infos) && count($infos) > 1) {
                $flashBag->set('info', [reset($infos)]);
            }
        }

        // ✅ Récupération et sécurisation du redirect
        $redirect = $request->query->get('redirect');
        if ($redirect && !str_starts_with($redirect, '/')) {
            $redirect = null; // protection anti-redirection externe
        }

        // ⚙️ Gestion standard de l'authentification
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($error) {
            $logger->add(
                'Échec de connexion',
                sprintf('Tentative échouée pour l’utilisateur : %s', $lastUsername ?: 'inconnu')
            );
            $this->addFlash('error', 'Identifiants incorrects. Veuillez réessayer.');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'redirect' => $redirect, // 🔒 transmis au template si interne uniquement
            'error' => null,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Ce point de déconnexion est intercepté par le firewall.');
    }
}
