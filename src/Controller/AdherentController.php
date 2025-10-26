<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdherentController extends AbstractController
{
    #[Route('/espace-adherent', name: 'adherent_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('adherent/dashboard.html.twig');
    }

    #[Route('/espace-adherent/licence', name: 'adherent_edit_license', methods: ['GET','POST'])]
    public function editLicense(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $license = trim((string) $request->request->get('license', ''));
            $user->setLicenseNumber($license !== '' ? $license : null);
            $em->flush();

            $this->addFlash('success', 'Votre numéro de licence a été mis à jour.');
            return $this->redirectToRoute('adherent_dashboard');
        }

        return $this->render('adherent/edit_license.html.twig', [
            'user' => $user,
        ]);
    }
}
