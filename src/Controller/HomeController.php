<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ArticleRepository;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(ArticleRepository $articleRepository): Response
    {
        // 🔹 Vérifie si l'utilisateur est connecté
        $user = $this->getUser();

        // 🔹 Exemple : rediriger selon le rôle
        if ($user) {
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_dashboard'); // adapte le nom de ta route admin
            }

            if ($this->isGranted('ROLE_USER')) {
                // tu peux garder la home ou rediriger vers un dashboard
                // return $this->redirectToRoute('user_dashboard');
            }

            // ❌ Supprimé : dump($user);
        }

        // Récupérer les derniers articles depuis la BDD
        $articles = $articleRepository->findBy([], ['publishedAt' => 'DESC']);

        return $this->render('0_home/index.html.twig', [
            'articles' => $articles,
        ]);
    }
}
