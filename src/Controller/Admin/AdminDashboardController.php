<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use App\Repository\SecurityLogRepository;
use App\Repository\NewsletterSubscriberRepository;

class AdminDashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(
        UserRepository $userRepo,
        SecurityLogRepository $logRepo,
        NewsletterSubscriberRepository $subsRepo
    ): Response {
        // 📊 Statistiques principales
        $totalUsers = $userRepo->count([]);
        $verifiedUsers = $userRepo->count(['isVerified' => true]);
        $newsletterSubscribers = $subsRepo->countConfirmed();

        // 🕓 Logs sécurité
        $successfulAttempts = $logRepo->countSuccessful();
        $failedAttempts = $logRepo->countFailedSince(new \DateTimeImmutable('-24 hours'));
        $recentLogs = $logRepo->findRecent(10);

        // 📅 Connexions réussies sur 7 jours
        $successByDay = $logRepo->getSuccessCountByDay(7);
        $labels7 = array_keys($successByDay);
        $loginsSuccessByDay = array_values($successByDay);

        // 📰 Nouveaux abonnés sur 7 jours (pour le graphique)
        $subsByDay = $subsRepo->countByDay(7);
        $newSubscribersByDay = array_values($subsByDay);

        // 🔁 Abonnés récents (5 derniers)
        $recentSubscribers = $subsRepo->findRecent(5);

        // 🧾 Activité simulée (tu peux la relier à un vrai logger plus tard)
        $recentActivity = [
            ['text' => 'Nouvel utilisateur <b>inscrit</b>', 'date' => new \DateTimeImmutable('-2 hours')],
            ['text' => 'Envoi d’une newsletter test', 'date' => new \DateTimeImmutable('-1 day')],
            ['text' => 'Suppression d’un ancien log', 'date' => new \DateTimeImmutable('-3 days')],
        ];

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'verifiedUsers' => $verifiedUsers,
            'successfulAttempts' => $successfulAttempts,
            'failedAttempts' => $failedAttempts,
            'newsletterSubscribers' => $newsletterSubscribers,
            'recentSubscribers' => $recentSubscribers,
            'labels7' => $labels7,
            'loginsSuccessByDay' => $loginsSuccessByDay,
            'newSubscribersByDay' => $newSubscribersByDay,
            'recentAttempts' => $recentLogs,
            'recentActivity' => $recentActivity,
        ]);
    }
}
