<?php

namespace App\Controller\Admin;

use App\Entity\ClubInfo;
use App\Form\ClubInfoNewType;
use App\Form\ClubInfoEditType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/club-info')]
class AdminClubInfoController extends AbstractController
{
    #[Route('/', name: 'admin_clubinfo_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $infos = $em->getRepository(ClubInfo::class)->findAll();

        return $this->render('admin/club_info/index.html.twig', [
            'infos' => $infos,
        ]);
    }

    #[Route('/new', name: 'admin_clubinfo_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $info = new ClubInfo();
        $form = $this->createForm(ClubInfoNewType::class, $info);
        $form->handleRequest($request);

        // Récupère toutes les catégories existantes
        $existingCategories = array_map(
            fn($i) => strtolower(trim($i->getCategory())),
            $em->getRepository(ClubInfo::class)->findAll()
        );

        if ($form->isSubmitted() && $form->isValid()) {
            $newCategory = strtolower(trim($info->getCategory()));

            if (in_array($newCategory, $existingCategories, true)) {
                $this->addFlash('error', '⚠️ Cette catégorie existe déjà.');
            } else {
                $em->persist($info);
                $em->flush();
                $this->addFlash('success', '✅ Nouvelle information ajoutée !');
                return $this->redirectToRoute('admin_clubinfo_index');
            }
        }

        return $this->render('admin/club_info/new.html.twig', [
            'form' => $form->createView(),
            'title' => 'Ajouter une information',
            'existingCategories' => $existingCategories,
        ]);
    }

    #[Route('/edit/{id}', name: 'admin_clubinfo_edit')]
    public function edit(ClubInfo $info, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ClubInfoEditType::class, $info);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', '✅ Information mise à jour !');
            return $this->redirectToRoute('admin_clubinfo_index');
        }

        return $this->render('admin/club_info/edit.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier une information',
        ]);
    }

    #[Route('/delete/{id}', name: 'admin_clubinfo_delete')]
    public function delete(ClubInfo $info, EntityManagerInterface $em): Response
    {
        $em->remove($info);
        $em->flush();
        $this->addFlash('success', '🗑️ Information supprimée.');
        return $this->redirectToRoute('admin_clubinfo_index');
    }
}
