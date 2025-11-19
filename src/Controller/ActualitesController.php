<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ArticleRepository;
use App\Repository\CategorieRepository;
use App\Entity\Article;

class ActualitesController extends AbstractController
{
    #[Route('/actualites/{page<\d+>?1}', name: 'actualites')]
    public function index(
        Request $request,
        ArticleRepository $articleRepository,
        CategorieRepository $categorieRepository,
        int $page = 1
    ): Response {
        $limit = 16;

        // 🔹 Récupération des filtres depuis la requête
        $rawCategorie = $request->query->get('categorie');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');

        // 🔹 Conversion sécurisée de la catégorie
        $categorieId = (ctype_digit($rawCategorie ?? '') && $rawCategorie !== '')
            ? (int) $rawCategorie
            : null;

        // 🔹 Récupération des articles filtrés
        $result = $articleRepository->findFilteredArticles(
            $categorieId,
            $dateFrom,
            $dateTo,
            $page,
            $limit
        );

        $articles = $result['data'];
        $totalArticles = $result['total'];
        $totalPages = max(1, ceil($totalArticles / $limit));

        // 🔹 Récupération et nettoyage des catégories (suppression des doublons)
        $categories = $categorieRepository->findBy([], ['name' => 'ASC']);

        $uniqueCategories = [];
        $seenNames = [];

        foreach ($categories as $cat) {
            $name = trim(strtolower($cat->getName())); // normalisation pour éviter "Événement" / "événement"
            if (!in_array($name, $seenNames)) {
                $uniqueCategories[] = $cat;
                $seenNames[] = $name;
            }
        }

        // 🔹 Rendu du template
        return $this->render('1_accueil/section4/actualites/articles.html.twig', [
            'articles' => $articles,
            'categories' => $uniqueCategories,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => [
                'categorie' => $rawCategorie,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    #[Route('/article/{id}', name: 'article_show')]
    public function show(Article $article): Response
    {
        return $this->render('1_accueil/section4/actualites/showarticles.html.twig', [
            'article' => $article,
        ]);
    }
}
