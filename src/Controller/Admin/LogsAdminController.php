<?php

namespace App\Controller\Admin;

use App\Repository\LogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/logs', name: 'admin_logs_')]
class LogsAdminController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(LogRepository $logRepo): Response
    {
        // On récupère tous les logs système (classés du plus récent au plus ancien)
        $systemLogs = $logRepo->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/logs/index.html.twig', [
            'systemLogs' => $systemLogs,
        ]);
    }
}
