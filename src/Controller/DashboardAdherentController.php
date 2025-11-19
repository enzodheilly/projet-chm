<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Licence;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

class DashboardAdherentController extends AbstractController
{
    private EntityManagerInterface $em;
    private CsrfTokenManagerInterface $csrf;

    public function __construct(EntityManagerInterface $em, CsrfTokenManagerInterface $csrf)
    {
        $this->em = $em;
        $this->csrf = $csrf;
    }

    /* ============================================================
       🔷 1) Dashboard
       ============================================================ */

    #[Route('/dashboard', name: 'dashboard')]
    #[Route('/espace-adherent', name: 'adherent_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('dashboard_adherent/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    /* ============================================================
       🔷 2) Licence : Ajouter / Modifier
       ============================================================ */

    #[Route('/espace-adherent/licence', name: 'adherent_edit_license', methods: ['POST'])]
    public function editLicense(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non connecté.'], 401);
        }

        $licenceNumber = trim((string) $request->request->get('licenceNumber', ''));
        if ($licenceNumber === '') {
            return $this->json(['success' => false, 'message' => 'Veuillez entrer un numéro de licence.']);
        }

        $licence = $this->em->getRepository(Licence::class)
            ->findOneBy(['number' => $licenceNumber]);

        if (!$licence) {
            return $this->json(['success' => false, 'message' => 'Numéro de licence introuvable ❌']);
        }

        if ($licence->isAlreadyAssociated()) {
            return $this->json([
                'success' => false,
                'message' => "Ce numéro de licence est déjà associé à un autre compte ❌<br>
                Si vous pensez être victime d'une usurpation d'identité, <a href='/contact'>contactez-nous ici</a>."
            ]);
        }

        $licence->setAlreadyAssociated(true);

        $user->setLicenceNumber($licence->getNumber());
        $user->setLicenceStatus('Active');
        $user->setLicenceEndDate($licence->getExpiryDate());

        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Licence ajoutée et synchronisée avec succès ✅',
            'licenceNumber' => $licence->getNumber(),
            'expiryDate' => $licence->getExpiryDate()->format('d/m/Y'),
            'status' => 'Active',
        ]);
    }

    /* ============================================================
       🔷 3) Licence : Supprimer
       ============================================================ */

    #[Route('/espace-adherent/licence/remove', name: 'adherent_remove_license', methods: ['POST'])]
    public function removeLicense(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non connecté.'], 401);
        }

        $licenceNumber = $user->getLicenceNumber();
        if (!$licenceNumber) {
            return $this->json(['success' => false, 'message' => 'Aucune licence à supprimer.']);
        }

        $licence = $this->em->getRepository(Licence::class)
            ->findOneBy(['number' => $licenceNumber]);

        if ($licence) {
            $licence->setAlreadyAssociated(false);
        }

        $user->setLicenceNumber(null);
        $user->setLicenceStatus(null);
        $user->setLicenceEndDate(null);

        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Licence retirée avec succès ✅'
        ]);
    }

    /* ============================================================
       🔷 4) Modifier les informations du compte
       ============================================================ */

    #[Route('/compte/modifier', name: 'account_edit')]
    public function edit(): Response
    {
        return $this->render('dashboard_adherent/edit.html.twig');
    }


    /* ============================================================
       🔷 5) Upload / Redimensionnement Photo Profil
       ============================================================ */

    #[Route('/profile/photo', name: 'profile_photo_upload', methods: ['POST'])]
    public function uploadProfilePhoto(Request $request): JsonResponse
    {
        try {
            $token = $request->headers->get('X-CSRF-TOKEN')
                ?? $request->request->get('_csrf_token');

            if (!$this->csrf->isTokenValid(new CsrfToken('profile_image', $token))) {
                return new JsonResponse(['error' => 'Token CSRF invalide'], 400);
            }

            $user = $this->getUser();
            if (!$user instanceof User) {
                return new JsonResponse(['error' => 'Utilisateur non authentifié'], 401);
            }

            /** @var UploadedFile|null $file */
            $file = $request->files->get('avatar');
            if (!$file || !$file->isValid()) {
                return new JsonResponse(['error' => 'Fichier manquant ou invalide'], 400);
            }

            $allowed = ['image/png', 'image/jpeg', 'image/webp'];
            $mime = $file->getMimeType();

            if (!in_array($mime, $allowed, true)) {
                return new JsonResponse(['error' => 'Type de fichier non autorisé'], 415);
            }

            if ($file->getSize() > 3 * 1024 * 1024) {
                return new JsonResponse(['error' => 'Fichier trop volumineux (max 3 Mo)'], 413);
            }

            $binary = $this->resizeImageBinary($file->getPathname(), 800, 800, $mime);

            $user->setProfileImage($binary);
            $user->setProfileImageMime($mime);
            $user->setProfileImageUpdatedAt(new \DateTimeImmutable());

            $this->em->flush();

            $dataUrl = sprintf('data:%s;base64,%s', $mime, base64_encode($binary));

            return new JsonResponse(['status' => 'ok', 'dataUrl' => $dataUrl]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Erreur interne : ' . $e->getMessage()], 500);
        }
    }

    private function resizeImageBinary(string $path, int $maxW, int $maxH, string $mime): string
    {
        [$origW, $origH] = getimagesize($path);
        if (!$origW || !$origH) {
            return file_get_contents($path);
        }

        $ratio = min($maxW / $origW, $maxH / $origH, 1);
        $newW = (int)($origW * $ratio);
        $newH = (int)($origH * $ratio);

        switch ($mime) {
            case 'image/png':
                $src = imagecreatefrompng($path);
                break;
            case 'image/webp':
                $src = imagecreatefromwebp($path);
                break;
            default:
                $src = imagecreatefromjpeg($path);
        }

        $dst = imagecreatetruecolor($newW, $newH);

        if (in_array($mime, ['image/png', 'image/webp'])) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        ob_start();
        match ($mime) {
            'image/png'  => imagepng($dst),
            'image/webp' => imagewebp($dst, null, 85),
            default      => imagejpeg($dst, null, 85),
        };
        $data = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $data;
    }
}
