<?php

namespace App\Controller\Admin;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/articles', name: 'admin_articles_')]
class ArticleAdminController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(ArticleRepository $articleRepo): Response
    {
        // 🔹 On trie par date de publication
        $articles = $articleRepo->findBy([], ['publishedAt' => 'DESC']);

        return $this->render('admin/articles/index.html.twig', [
            'title' => 'Gestion des articles',
            'articles' => $articles,
        ]);
    }
}
