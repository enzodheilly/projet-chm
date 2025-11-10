<?php

namespace App\Controller;

use App\Entity\LicenseRequest;
use App\Entity\Licence;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

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

        if ($firstName === '' || $lastName === '') {
            return new JsonResponse(['ok' => false, 'error' => 'Merci de renseigner prénom et nom.'], 400);
        }

        // 🔍 Recherche de la licence
        $license = $em->getRepository(Licence::class)->findOneBy([
            'firstName' => $firstName,
            'lastName'  => $lastName,
        ]);

        if (!$license) {
            return new JsonResponse(['ok' => false, 'error' => 'Aucune licence trouvée pour ce nom et prénom.'], 404);
        }

        $email = $license->getEmail();
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['ok' => false, 'error' => 'Aucune adresse e-mail valide associée à cette licence.'], 400);
        }

        $ip = $request->getClientIp();

        // 🧹 Nettoyage des demandes expirées
        $expired = $em->getRepository(LicenseRequest::class)->createQueryBuilder('r')
            ->where('r.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()->getResult();
        foreach ($expired as $old) {
            $em->remove($old);
        }
        $em->flush();

        // 🚫 Limite de requêtes par IP
        $activeCount = $em->getRepository(LicenseRequest::class)->count([
            'requesterIp' => $ip,
            'status' => LicenseRequest::STATUS_PENDING
        ]);
        if ($activeCount >= 3) {
            return new JsonResponse([
                'ok' => false,
                'error' => 'Trop de tentatives récentes depuis cette adresse IP. Réessaie dans quelques minutes.'
            ], 429);
        }

        // 🔁 Vérifie s’il existe déjà une demande valide pour cet e-mail
        $recent = $em->getRepository(LicenseRequest::class)->findOneBy([
            'userEmail' => $email,
            'status' => LicenseRequest::STATUS_PENDING
        ]);
        if ($recent && !$recent->isExpired()) {
            // Masquage partiel de l’adresse mail
            $masked = preg_replace('/(?<=.).(?=[^@]*?@)/', '*', $email);
            return new JsonResponse([
                'ok' => true,
                'message' => "Un code a déjà été envoyé récemment à $masked. Vérifie ta boîte mail.",
                'token' => $recent->getToken(),
            ]);
        }

        // ✅ Nouvelle demande
        $req = new LicenseRequest();
        $req->setUserEmail($email);
        $req->setRequesterIp($ip);
        $req->setExpiresAt((new \DateTimeImmutable())->modify('+10 minutes'));

        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $req->setVerificationCode($code);
        $req->setFailedAttempts(0);
        $em->persist($req);
        $em->flush();

        // 📧 Envoi du mail
        try {
            $emailMsg = (new TemplatedEmail())
                ->from(new Address('no-reply@tonclub.fr', 'Club Haltéro – Assistant'))
                ->to(new Address($email))
                ->subject('Votre code de confirmation de licence')
                ->htmlTemplate('emails/license_code.html.twig')
                ->context([
                    'firstName' => $license->getFirstName(),
                    'code' => $code,
                    'expiresAt' => $req->getExpiresAt(),
                ]);

            $mailer->send($emailMsg);
        } catch (\Throwable $e) {
            return new JsonResponse(['ok' => false, 'error' => 'Échec d’envoi de l’e-mail : ' . $e->getMessage()], 500);
        }

        return new JsonResponse([
            'ok' => true,
            'message' => 'Un code à 6 chiffres vient d’être envoyé à ton adresse e-mail.',
            'token' => $req->getToken(),
        ]);
    }

    #[Route('/assistant/license/verify', name: 'assistant_license_verify', methods: ['POST'])]
    public function verify(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $token = (string)($data['token'] ?? '');
        $code = trim((string)($data['code'] ?? ''));

        if ($token === '' || $code === '') {
            return new JsonResponse(['ok' => false, 'error' => 'Code ou identifiant manquant.'], 400);
        }

        /** @var LicenseRequest|null $req */
        $req = $em->getRepository(LicenseRequest::class)->findOneBy(['token' => $token]);

        if (!$req) {
            return new JsonResponse(['ok' => false, 'error' => 'Demande introuvable.'], 404);
        }

        // 🔐 Vérifications de sécurité
        if ($req->isExpired()) {
            $req->setStatus(LicenseRequest::STATUS_EXPIRED);
            $em->flush();
            return new JsonResponse(['ok' => false, 'error' => 'Le code a expiré.'], 403);
        }

        if ($req->getFailedAttempts() >= 3) {
            $req->setStatus(LicenseRequest::STATUS_EXPIRED);
            $em->flush();
            return new JsonResponse(['ok' => false, 'error' => 'Trop de tentatives échouées. Demande expirée.'], 403);
        }

        if ($req->getVerificationCode() !== $code) {
            $req->incrementFailedAttempts();
            $em->flush();
            return new JsonResponse(['ok' => false, 'error' => 'Code incorrect.'], 403);
        }

        // ✅ Validation réussie
        $req->setStatus(LicenseRequest::STATUS_CONFIRMED);
        $req->setConfirmedAt(new \DateTimeImmutable());
        $req->resetFailedAttempts();
        $req->setVerificationCode(null); // ❌ supprime le code
        $em->flush();

        // 🔎 Récupération de la licence associée
        $license = $em->getRepository(Licence::class)->findOneBy(['email' => $req->getUserEmail()]);
        if (!$license) {
            return new JsonResponse(['ok' => false, 'error' => 'Licence introuvable.'], 404);
        }

        return new JsonResponse([
            'ok' => true,
            'license' => $license->getNumber(),
            'email' => $license->getEmail(),
            'fullName' => trim($license->getFirstName() . ' ' . $license->getLastName()),
        ]);
    }
}
