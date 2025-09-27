<?php
// src/Controller/HalterophilieController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HalterophilieController extends AbstractController
{
    #[Route('/ecole', name: 'ecole')]
    public function ecole(): Response
    {
        // On indique le chemin relatif depuis templates/
        return $this->render('halterophilie/ecole/index.html.twig');
    }

    #[Route('/halterophilie', name: 'halterophilie')]
    public function halterophilie(): Response
    {
        // On indique le chemin relatif depuis templates/
        return $this->render('halterophilie/definitions/index.html.twig');
    }
}
