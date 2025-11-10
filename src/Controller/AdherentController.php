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
        return $this->render('adherent/index.html.twig');
    }

    #[Route('/espace-adherent/licence', name: 'adherent_edit_license', methods: ['POST'])]
    public function editLicense(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non connecté.'], 401);
        }

        $licenceNumber = trim((string) $request->request->get('licenceNumber', ''));

        if ($licenceNumber === '') {
            return $this->json(['success' => false, 'message' => 'Veuillez entrer un numéro de licence.']);
        }

        // Recherche de la licence
        $licence = $em->getRepository(\App\Entity\Licence::class)
            ->findOneBy(['number' => $licenceNumber]);

        if (!$licence) {
            return $this->json(['success' => false, 'message' => 'Numéro de licence introuvable ❌']);
        }

        // 🔒 Vérifie si la licence a déjà été utilisée
        if ($licence->isAlreadyAssociated()) {
            return $this->json([
                'success' => false,
                'message' => "Ce numéro de licence est déjà associé à un autre compte ❌<br>
            Si vous pensez être victime d'une usurpation d'identité, <a href='/contact' style='color:#007bff;'>contactez-nous ici</a>."
            ]);
        }

        // Tout est OK → on marque la licence comme utilisée
        $licence->setAlreadyAssociated(true);

        // Met à jour les infos dans le profil utilisateur
        $user->setLicenceNumber($licence->getNumber());
        $user->setLicenceStatus('Active');
        $user->setLicenceEndDate($licence->getExpiryDate());
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Licence ajoutée et synchronisée avec succès ✅',
            'licenceNumber' => $licence->getNumber(),
            'expiryDate' => $licence->getExpiryDate()->format('d/m/Y'),
            'status' => 'Active',
        ]);
    }

    #[Route('/espace-adherent/licence/remove', name: 'adherent_remove_license', methods: ['POST'])]
    public function removeLicense(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non connecté.'], 401);
        }

        $licenceNumber = $user->getLicenceNumber();
        if (!$licenceNumber) {
            return $this->json(['success' => false, 'message' => 'Aucune licence à supprimer.']);
        }

        // Retrouve la licence correspondante
        $licence = $em->getRepository(\App\Entity\Licence::class)
            ->findOneBy(['number' => $licenceNumber]);

        if ($licence) {
            // Libère la licence
            $licence->setAlreadyAssociated(false);
        }

        // Supprime les infos de la licence du profil
        $user->setLicenceNumber(null);
        $user->setLicenceStatus(null);
        $user->setLicenceEndDate(null);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Licence retirée avec succès ✅'
        ]);
    }



    #[Route('/compte/modifier', name: 'account_edit')]
    public function edit(): Response
    {
        // Tu peux plus tard y ajouter un formulaire pour modifier l'utilisateur
        return $this->render('adherent/edit.html.twig');
    }
}
