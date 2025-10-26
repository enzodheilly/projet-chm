<?php
// src/Controller/ActualitesController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ArticleRepository;
use App\Entity\Article;

class ActualitesController extends AbstractController
{
    #[Route('/actualites/{page<\d+>?1}', name: 'actualites')]
    public function index(ArticleRepository $articleRepository, int $page = 1): Response
    {
        $articlesParPage = 16;

        // Récupérer le nombre total d'articles
        $totalArticles = $articleRepository->count([]);

        // Récupérer les articles pour la page actuelle
        $articles = $articleRepository->findBy(
            [],                          // Pas de critère spécifique
            ['publishedAt' => 'DESC'],   // Tri par date décroissante
            $articlesParPage,            // Limite
            ($page - 1) * $articlesParPage // Offset
        );

        $totalPages = ceil($totalArticles / $articlesParPage);

        return $this->render('1_accueil/section4/actualites/articles.html.twig', [
            'articles' => $articles,
            'page' => $page,
            'totalPages' => $totalPages
        ]);
    }

    #[Route('/article/{id}', name: 'article_show')]
    public function show(Article $article): Response
    {
        // Le param converter de Symfony va récupérer l'article par son id
        return $this->render('1_accueil/section4/actualites/showarticles.html.twig', [
            'article' => $article,
        ]);
    }
}
