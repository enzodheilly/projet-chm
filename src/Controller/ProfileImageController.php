<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

class ProfileImageController extends AbstractController
{
    private EntityManagerInterface $em;
    private CsrfTokenManagerInterface $csrf;

    public function __construct(EntityManagerInterface $em, CsrfTokenManagerInterface $csrf)
    {
        $this->em = $em;
        $this->csrf = $csrf;
    }

    #[Route('/profile/photo', name: 'profile_photo_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        try {
            // Vérification du token CSRF
            $token = $request->headers->get('X-CSRF-TOKEN') ?? $request->request->get('_csrf_token');
            if (!$this->csrf->isTokenValid(new CsrfToken('profile_image', $token))) {
                return new JsonResponse(['error' => 'Token CSRF invalide'], Response::HTTP_BAD_REQUEST);
            }

            // Récupère l'utilisateur connecté
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
            }

            /** @var UploadedFile|null $file */
            $file = $request->files->get('avatar');
            if (!$file || !$file->isValid()) {
                return new JsonResponse(['error' => 'Fichier manquant ou invalide'], Response::HTTP_BAD_REQUEST);
            }

            // Validation du type MIME et de la taille
            $allowed = ['image/png', 'image/jpeg', 'image/webp'];
            $mime = $file->getMimeType();
            $maxBytes = 3 * 1024 * 1024; // 3 Mo

            if (!in_array($mime, $allowed, true)) {
                return new JsonResponse(['error' => 'Type de fichier non autorisé'], Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
            }

            if ($file->getSize() > $maxBytes) {
                return new JsonResponse(['error' => 'Fichier trop volumineux (max 3 Mo)'], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
            }

            // Redimensionne côté serveur
            $binary = $this->resizeImageBinary($file->getPathname(), 800, 800, $mime);

            // Enregistre dans la base
            $user->setProfileImage($binary);
            $user->setProfileImageMime($mime);
            $user->setProfileImageUpdatedAt(new \DateTimeImmutable());

            $this->em->persist($user);
            $this->em->flush();

            // Crée l’URL base64 pour prévisualisation instantanée
            $dataUrl = sprintf('data:%s;base64,%s', $mime, base64_encode($binary));

            return new JsonResponse(['status' => 'ok', 'dataUrl' => $dataUrl]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Erreur interne : ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function resizeImageBinary(string $path, int $maxW, int $maxH, string $mime): string
    {
        [$origW, $origH] = getimagesize($path);
        if ($origW === 0 || $origH === 0) {
            return file_get_contents($path);
        }

        $ratio = min($maxW / $origW, $maxH / $origH, 1);
        $newW = (int)round($origW * $ratio);
        $newH = (int)round($origH * $ratio);

        switch ($mime) {
            case 'image/png':
                $src = imagecreatefrompng($path);
                break;
            case 'image/webp':
                $src = imagecreatefromwebp($path);
                break;
            default:
                $src = \imagecreatefromjpeg($path);
        }

        $dst = imagecreatetruecolor($newW, $newH);

        // Transparence PNG / WEBP
        if (in_array($mime, ['image/png', 'image/webp'])) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        ob_start();
        switch ($mime) {
            case 'image/png':
                imagepng($dst);
                break;
            case 'image/webp':
                imagewebp($dst, null, 85);
                break;
            default:
                imagejpeg($dst, null, 85);
        }
        $data = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $data;
    }
}
