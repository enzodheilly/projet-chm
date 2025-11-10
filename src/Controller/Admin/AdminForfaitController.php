<?php

namespace App\Controller\Admin;

use App\Entity\Forfait;
use App\Form\ForfaitType;
use App\Repository\ForfaitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/forfaits')]
class AdminForfaitController extends AbstractController
{
    #[Route('/', name: 'admin_forfait_index', methods: ['GET'])]
    public function index(ForfaitRepository $forfaitRepository): Response
    {
        return $this->render('admin/forfait/index.html.twig', [
            'forfaits' => $forfaitRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_forfait_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $forfait = new Forfait();
        $form = $this->createForm(ForfaitType::class, $forfait);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($forfait);
            $em->flush();

            $this->addFlash('success', '✅ Forfait créé avec succès.');
            return $this->redirectToRoute('admin_forfait_index');
        }

        return $this->render('admin/forfait/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_forfait_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Forfait $forfait, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ForfaitType::class, $forfait);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', '📝 Forfait mis à jour avec succès.');
            return $this->redirectToRoute('admin_forfait_index');
        }

        return $this->render('admin/forfait/edit.html.twig', [
            'form' => $form->createView(),
            'forfait' => $forfait,
        ]);
    }

    #[Route('/{id}', name: 'admin_forfait_delete', methods: ['POST'])]
    public function delete(Request $request, Forfait $forfait, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $forfait->getId(), $request->request->get('_token'))) {
            $em->remove($forfait);
            $em->flush();
            $this->addFlash('success', '🗑️ Forfait supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_forfait_index');
    }
}
