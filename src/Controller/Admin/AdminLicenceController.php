<?php

namespace App\Controller\Admin;

use App\Entity\Licence;
use App\Form\LicenceType;
use App\Repository\LicenceRepository;
use App\Repository\ForfaitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/licences')]
class AdminLicenceController extends AbstractController
{
    #[Route('/', name: 'admin_licence_index', methods: ['GET'])]
    public function index(LicenceRepository $licenceRepository): Response
    {
        return $this->render('admin/licence/index.html.twig', [
            'licences' => $licenceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_licence_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $licence = new Licence();
        $form = $this->createForm(LicenceType::class, $licence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forfait = $licence->getForfait();

            // 🧠 Si un forfait est sélectionné, on définit automatiquement le type
            if ($forfait) {
                $licence->setType($forfait->getNom());
                $licence->setBenefits($forfait->getAvantages());
            }

            $em->persist($licence);
            $em->flush();

            $this->addFlash('success', '✅ Licence créée avec succès.');
            return $this->redirectToRoute('admin_licence_index');
        }

        return $this->render('admin/licence/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/{id}/edit', name: 'admin_licence_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Licence $licence, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(LicenceType::class, $licence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', '📝 Licence mise à jour avec succès.');
            return $this->redirectToRoute('admin_licence_index');
        }

        return $this->render('admin/licence/edit.html.twig', [
            'licence' => $licence,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_licence_delete', methods: ['POST'])]
    public function delete(Request $request, Licence $licence, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $licence->getId(), $request->request->get('_token'))) {
            $em->remove($licence);
            $em->flush();
            $this->addFlash('success', '🗑️ Licence supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_licence_index');
    }

    // 🧠 Nouvelle route AJAX pour renvoyer les avantages d’un forfait
    #[Route('/forfait/{id}/avantages', name: 'admin_licence_forfait_avantages', methods: ['GET'])]
    public function getForfaitAvantages(int $id, ForfaitRepository $repo): JsonResponse
    {
        $forfait = $repo->find($id);
        if (!$forfait) {
            return new JsonResponse(['error' => 'Forfait introuvable'], 404);
        }

        return new JsonResponse([
            'nom' => $forfait->getNom(),
            'avantages' => $forfait->getAvantages(),
        ]);
    }
}
