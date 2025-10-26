<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/updates', name: 'admin_updates_')]
class UpdateController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        // Vérifie la version Git actuelle
        $version = trim(shell_exec('git rev-parse --short HEAD'));

        return $this->render('admin/updates/index.html.twig', [
            'version' => $version,
        ]);
    }

    #[Route('/run', name: 'run')]
    public function run(): Response
    {
        // ⚠️ Assure-toi que ton serveur a les droits d’exécuter git
        $output = [];
        exec('git pull 2>&1', $output);

        $this->addFlash('success', 'Mise à jour effectuée avec succès.');
        return $this->render('admin/updates/result.html.twig', [
            'output' => $output,
        ]);
    }
}
