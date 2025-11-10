<?php
// src/Controller/ActualitesController.php

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
        $rawCategorie = $request->query->get('categorie'); // string|null
        $dateFrom = $request->query->get('date_from');     // string|null
        $dateTo = $request->query->get('date_to');         // string|null

        // 🔹 Conversion sécurisée de la catégorie en entier
        $categorieId = (ctype_digit($rawCategorie ?? '') && $rawCategorie !== '')
            ? (int) $rawCategorie
            : null;

        // 🔹 Récupération des articles filtrés via le repository
        $result = $articleRepository->findFilteredArticles(
            $categorieId,
            $dateFrom,
            $dateTo,
            $page,
            $limit
        );

        $articles = $result['data'];
        $totalArticles = $result['total'];
        $totalPages = max(1, ceil($totalArticles / $limit)); // évite division par zéro

        // 🔹 Récupération de toutes les catégories pour le <select>
        $categories = $categorieRepository->findBy([], ['name' => 'ASC']);

        // 🔹 Rendu du template
        return $this->render('1_accueil/section4/actualites/articles.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
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
