<?php
// src/Controller/ClubController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClubController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        // On indique le chemin relatif depuis templates/
        return $this->render('home/index.html.twig');
    }

    #[Route('/comite', name: 'comite')]
    public function comite(): Response
    {
        // On indique le chemin relatif depuis templates/
        return $this->render('comite/comite.html.twig');
    }

    #[Route('/encadrants', name: 'encadrants')]
    public function encadrants(): Response
    {
        return $this->render('encadrants/encadrants.html.twig');
    }

    #[Route('/nouveautes', name: 'nouveautes')]
    public function nouveautes(): Response
    {
        return $this->render('nouveautes/nouveautes.html.twig');
    }

    // Ajouter les autres pages de la même façon
}
