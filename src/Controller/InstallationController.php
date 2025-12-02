<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InstallationController extends AbstractController
{
    #[Route('/installations/cardio', name: 'install_cardio')]
    public function cardio(): Response
    {
        return $this->render('1_accueil/section7/installations/cardio.html.twig');
    }

    #[Route('/installations/pecs', name: 'install_pecs')]
    public function pecs(): Response
    {
        return $this->render('1_accueil/section7/installations/pecs.html.twig');
    }

    #[Route('/installations/biceps', name: 'install_biceps')]
    public function biceps(): Response
    {
        return $this->render('1_accueil/section7/installations/biceps.html.twig');
    }

    #[Route('/installations/halterophilie', name: 'install_halterophilie')]
    public function halterophilie(): Response
    {
        return $this->render('1_accueil/section7/installations/halterophilie.html.twig');
    }

    #[Route('/installations/jambes', name: 'install_jambes')]
    public function jambes(): Response
    {
        return $this->render('1_accueil/section7/installations/jambes.html.twig');
    }
}
