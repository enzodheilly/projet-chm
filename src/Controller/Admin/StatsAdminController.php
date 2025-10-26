<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use App\Repository\ArticleRepository;
use App\Repository\NewsletterSubscriberRepository; // ✅ Correction ici
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/stats', name: 'admin_stats_')]
class StatsAdminController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(
        UserRepository $userRepo,
        ArticleRepository $articleRepo,
        NewsletterSubscriberRepository $subsRepo, // ✅ Correction ici aussi
        EntityManagerInterface $em
    ): Response {
        // 🔢 Statistiques globales
        $totalUsers = $userRepo->count([]);
        $totalArticles = $articleRepo->count([]);
        $newsletterSubscribers = $subsRepo->countConfirmed(); // ✅ plus logique si tu veux que ce soit les abonnés confirmés

        // 🕒 7 derniers jours
        $labels = [];
        $userRegistrations = [];
        $articlesPublished = [];

        $today = new \DateTimeImmutable();
        for ($i = 6; $i >= 0; $i--) {
            $day = $today->modify("-$i days");
            $labels[] = $day->format('D'); // Lun, Mar...

            // 📈 Nombre d'inscriptions utilisateurs ce jour-là
            $countUsers = $userRepo->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $day->setTime(0, 0))
                ->setParameter('end', $day->setTime(23, 59, 59))
                ->getQuery()
                ->getSingleScalarResult();

            $userRegistrations[] = (int) $countUsers;

            // 📰 Nombre d’articles publiés ce jour-là
            $countArticles = $articleRepo->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.publishedAt BETWEEN :start AND :end')
                ->setParameter('start', $day->setTime(0, 0))
                ->setParameter('end', $day->setTime(23, 59, 59))
                ->getQuery()
                ->getSingleScalarResult();

            $articlesPublished[] = (int) $countArticles;
        }

        return $this->render('admin/stats/index.html.twig', [
            'title' => 'Statistiques du site',
            'totalUsers' => $totalUsers,
            'totalArticles' => $totalArticles,
            'newsletterSubscribers' => $newsletterSubscribers,
            'labels' => $labels,
            'userRegistrations' => $userRegistrations,
            'articlesPublished' => $articlesPublished,
        ]);
    }
}
