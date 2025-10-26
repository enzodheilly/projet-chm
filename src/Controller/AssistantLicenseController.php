<?php

namespace App\Controller;

use App\Entity\LicenseRequest;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class AssistantLicenseController extends AbstractController
{
    #[Route('/assistant/license/start', name: 'assistant_license_start', methods: ['POST'])]
    public function start(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];
        $firstName = trim((string)($data['firstName'] ?? ''));
        $lastName  = trim((string)($data['lastName'] ?? ''));
        $email     = trim((string)($data['email'] ?? ''));

        if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['ok' => false, 'error' => 'Champs invalides.'], 400);
        }

        // Recherche stricte par email, et on vérifie nom/prénom pour cohérence
        /** @var User|null $user */
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user || strcasecmp($user->getFirstName() ?? '', $firstName) !== 0 || strcasecmp($user->getLastName() ?? '', $lastName) !== 0) {
            return new JsonResponse(['ok' => false, 'error' => 'Aucun adhérent correspondant.'], 404);
        }

        // Anti-spam simple: refuse une nouvelle demande si une PENDING récente existe
        $recent = $em->getRepository(LicenseRequest::class)->findOneBy(['user' => $user, 'status' => LicenseRequest::STATUS_PENDING]);
        if ($recent && !$recent->isExpired()) {
            return new JsonResponse(['ok' => true, 'message' => 'Un email de confirmation a déjà été envoyé récemment. Vérifie ta boîte mail.']);
        }

        $req = new LicenseRequest();
        $req->setUser($user);
        $req->setRequesterIp($request->getClientIp());
        $em->persist($req);
        $em->flush();

        // Envoi email de confirmation
        $confirmUrl = $this->generateUrl('assistant_license_confirm', ['token' => $req->getToken()], 0);
        $absoluteConfirmUrl = $request->getSchemeAndHttpHost() . $confirmUrl;

        $emailMsg = (new TemplatedEmail())
            ->from(new Address('no-reply@tonclub.fr', 'Club Haltéro – Assistant'))
            ->to(new Address($user->getEmail(), trim($user->getFirstName() . ' ' . $user->getLastName())))
            ->subject('Confirmez votre demande de numéro de licence')
            ->htmlTemplate('emails/license_confirm.html.twig')
            ->context([
                'firstName' => $user->getFirstName(),
                'confirmUrl' => $absoluteConfirmUrl,
                'expiresAt' => $req->getExpiresAt(),
            ]);

        $mailer->send($emailMsg);

        return new JsonResponse([
            'ok' => true,
            'message' => 'Un email de confirmation vient de vous être envoyé. Cliquez sur le lien pour valider la demande.',
            'token' => $req->getToken() // utile si tu veux piloter depuis le chat
        ]);
    }

    #[Route('/assistant/license/confirm/{token}', name: 'assistant_license_confirm', methods: ['GET'])]
    public function confirm(string $token, EntityManagerInterface $em): Response
    {
        /** @var LicenseRequest|null $req */
        $req = $em->getRepository(LicenseRequest::class)->findOneBy(['token' => $token]);
        if (!$req) {
            return $this->render('assistant/license_confirm_result.html.twig', ['ok' => false, 'reason' => 'invalid']);
        }
        if ($req->isExpired()) {
            $req->setStatus(LicenseRequest::STATUS_EXPIRED);
            $em->flush();
            return $this->render('assistant/license_confirm_result.html.twig', ['ok' => false, 'reason' => 'expired']);
        }

        $req->setStatus(LicenseRequest::STATUS_CONFIRMED);
        $req->setConfirmedAt(new \DateTimeImmutable());
        $em->flush();

        // Choix 1 : on affiche directement le numéro ici
        return $this->render('assistant/license_confirm_result.html.twig', [
            'ok' => true,
            'license' => $req->getUser()->getLicenseNumber(),
            'user' => $req->getUser(),
        ]);
        // Choix 2 : sinon, on peut afficher "confirmation ok", et laisser le chat appeler /assistant/license/result
    }

    #[Route('/assistant/license/result', name: 'assistant_license_result', methods: ['POST'])]
    public function result(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $token = (string)($data['token'] ?? '');
        if ($token === '') {
            return new JsonResponse(['ok' => false, 'error' => 'Token manquant.'], 400);
        }

        /** @var LicenseRequest|null $req */
        $req = $em->getRepository(LicenseRequest::class)->findOneBy(['token' => $token]);
        if (!$req) {
            return new JsonResponse(['ok' => false, 'error' => 'Demande introuvable.'], 404);
        }
        if ($req->getStatus() !== LicenseRequest::STATUS_CONFIRMED || $req->isExpired()) {
            return new JsonResponse(['ok' => false, 'error' => 'Demande non confirmée ou expirée.'], 403);
        }

        return new JsonResponse([
            'ok' => true,
            'license' => $req->getUser()->getLicenseNumber(),
            'email' => $req->getUser()->getEmail(),
            'fullName' => trim(($req->getUser()->getFirstName() ?? '') . ' ' . ($req->getUser()->getLastName() ?? '')),
        ]);
    }
}
