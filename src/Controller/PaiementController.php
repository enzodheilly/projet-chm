<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaiementController extends AbstractController
{
    #[Route('/paiement/{type}', name: 'paiement_type')]
    public function paiement(string $type): Response
    {
        // ✅ Lien du formulaire HelloAsso
        $urls = [
            'competition' => 'https://www.helloasso.com/associations/chm-saleux/adhesions/licence-competition-2025/formulaire',
            'jeunes'      => 'https://www.helloasso.com/associations/chm-saleux/adhesions/licence-jeunes-2025/formulaire',
            'loisir'      => 'https://www.helloasso.com/associations/chm-saleux/adhesions/licence-loisir-2025/formulaire',
            'enfants'     => 'https://www.helloasso.com/associations/chm-saleux/adhesions/licence-enfant-2025/formulaire',
        ];

        if (!array_key_exists($type, $urls)) {
            $this->addFlash('danger', 'Type de paiement invalide.');
            return $this->redirectToRoute('home');
        }

        // ✅ Si l’utilisateur N’EST PAS connecté → affiche la page de connexion requise
        if (!$this->getUser()) {
            return $this->render('paiement/not_connected.html.twig', [
                'type' => ucfirst($type),
                // ⚠️ On transmet l’URL cible au template :
                'redirectUrl' => $this->generateUrl('paiement_type', ['type' => $type]),
            ]);
        }

        // ✅ Si connecté → afficher le formulaire de paiement HelloAsso
        return $this->render('paiement/index.html.twig', [
            'iframeUrl' => $urls[$type],
            'type' => ucfirst($type),
        ]);
    }
}
