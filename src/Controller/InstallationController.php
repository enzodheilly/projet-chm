<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InstallationController extends AbstractController
{

    #[Route('/installations/halterophilie', name: 'section_halterophilie')]
    public function halterophilie(): Response
    {
        return $this->render('1_accueil/section7/halterophilie/index.html.twig');
    }

    #[Route('/installations/musculation', name: 'section_musculation')]
    public function jambes(): Response
    {
        return $this->render('1_accueil/section7/musculation/index.html.twig');
    }
}
