<?php

namespace App\Controller;

use App\Entity\ClubInfo;
use App\Entity\Licence;
use App\Entity\LicenseRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AssistantController extends AbstractController
{
    private HttpClientInterface $client;
    private EntityManagerInterface $em;

    public function __construct(HttpClientInterface $client, EntityManagerInterface $em)
    {
        $this->client = $client;
        $this->em = $em;
    }

    /* ============================================================
       🔹 1) Assistant IA (Chat)
       ============================================================ */

    #[Route('/assistant/chat', name: 'assistant_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $data = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        if (!$message) {
            return $this->json(['reply' => "Je n’ai pas compris 😅"]);
        }

        $history = $session->get('elios_history', []);

        // Vérification en BDD
        $reply = $this->getInfoFromDatabase($message);
        if ($reply) {
            $history[] = ['role' => 'user', 'content' => $message];
            $history[] = ['role' => 'assistant', 'content' => $reply];
            $session->set('elios_history', array_slice($history, -10));

            return $this->json(['reply' => $reply]);
        }

        // Appel IA
        $apiUrl = $_ENV['DEEPSEEK_API_URL'] ?? 'http://localhost:11434/api/chat';
        $model = $_ENV['DEEPSEEK_MODEL'] ?? 'deepseek-r1:8b';

        $messages = array_merge(
            [['role' => 'system', 'content' => "Tu es Elios, assistant virtuel du club d’haltérophilie et musculation. Réponds en français, de façon claire et amicale."]],
            $history,
            [['role' => 'user', 'content' => $message]]
        );

        try {
            $response = $this->client->request('POST', $apiUrl, [
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'stream' => false,
                ],
            ]);

            $data = $response->toArray(false);

            $reply = $data['message']['content']
                ?? ($data['messages'][0]['content'] ?? "Je n’ai pas compris 😅");

            $history[] = ['role' => 'user', 'content' => $message];
            $history[] = ['role' => 'assistant', 'content' => $reply];
            $session->set('elios_history', array_slice($history, -10));

            return $this->json(['reply' => $reply]);
        } catch (\Throwable $e) {
            return $this->json([
                'reply' => "🤖 Elios n’est pas disponible pour le moment (serveur IA éteint). Il reviendra bientôt !",
            ]);
        }
    }


    /** 🔍 Recherche d'info dans la BDD */
    private function getInfoFromDatabase(string $message): ?string
    {
        $categories = [
            'horaires' => ['horaire', 'ouvert', 'heures'],
            'tarifs'   => ['tarif', 'prix', 'abonnement'],
            'contact'  => ['contact', 'téléphone', 'mail', 'email'],
            'adresse'  => ['adresse', 'où se trouve', 'localisation'],
            'coach'    => ['coach', 'entraîneur'],
        ];

        foreach ($categories as $cat => $keywords) {
            foreach ($keywords as $word) {
                if (str_contains(mb_strtolower($message), $word)) {

                    $info = $this->em->getRepository(ClubInfo::class)
                        ->findOneBy(['category' => $cat]);

                    return $info ? $info->getContent() : null;
                }
            }
        }

        return null;
    }



    #[Route('/assistant/categories', name: 'assistant_categories', methods: ['GET'])]
    public function getCategories(): JsonResponse
    {
        $categories = $this->em->getRepository(ClubInfo::class)->findAll();

        $data = [];
        foreach ($categories as $cat) {
            $data[] = [
                'category' => $cat->getCategory(),
                'label' => ucfirst($cat->getCategory()),
            ];
        }

        return $this->json($data);
    }


    /* ============================================================
       🔹 2) Assistant Licence : Demande + Vérification
       ============================================================ */

    #[Route('/assistant/license/start', name: 'assistant_license_start', methods: ['POST'])]
    public function licenseStart(
        Request $request,
        MailerInterface $mailer
    ): JsonResponse {

        $data = json_decode($request->getContent(), true) ?? [];
        $firstName = trim((string)($data['firstName'] ?? ''));
        $lastName  = trim((string)($data['lastName'] ?? ''));

        if ($firstName === '' || $lastName === '') {
            return new JsonResponse(['ok' => false, 'error' => 'Merci de renseigner prénom et nom.'], 400);
        }

        // Recherche licence
        $license = $this->em->getRepository(Licence::class)->findOneBy([
            'firstName' => $firstName,
            'lastName'  => $lastName,
        ]);

        if (!$license) {
            return new JsonResponse(['ok' => false, 'error' => 'Aucune licence trouvée.'], 404);
        }

        $email = $license->getEmail();
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['ok' => false, 'error' => 'Adresse e-mail invalide.'], 400);
        }

        $ip = $request->getClientIp();

        // Suppression demandes expirées
        $expired = $this->em->getRepository(LicenseRequest::class)->createQueryBuilder('r')
            ->where('r.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()->getResult();

        foreach ($expired as $old) {
            $this->em->remove($old);
        }
        $this->em->flush();

        // Limite par IP
        $activeCount = $this->em->getRepository(LicenseRequest::class)->count([
            'requesterIp' => $ip,
            'status' => LicenseRequest::STATUS_PENDING,
        ]);

        if ($activeCount >= 3) {
            return new JsonResponse([
                'ok' => false,
                'error' => 'Trop de tentatives depuis cette IP. Réessaie plus tard.'
            ], 429);
        }

        // Déjà une demande active ?
        $recent = $this->em->getRepository(LicenseRequest::class)->findOneBy([
            'userEmail' => $email,
            'status' => LicenseRequest::STATUS_PENDING
        ]);

        if ($recent && !$recent->isExpired()) {
            $masked = preg_replace('/(?<=.).(?=[^@]*?@)/', '*', $email);

            return new JsonResponse([
                'ok' => true,
                'message' => "Un code a déjà été envoyé à $masked.",
                'token' => $recent->getToken(),
            ]);
        }

        // Nouvelle demande
        $req = new LicenseRequest();
        $req->setUserEmail($email);
        $req->setRequesterIp($ip);
        $req->setExpiresAt((new \DateTimeImmutable())->modify('+10 minutes'));

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $req->setVerificationCode($code);

        $this->em->persist($req);
        $this->em->flush();

        // Envoi du code par email
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
            return new JsonResponse(['ok' => false, 'error' => 'Erreur envoi email : ' . $e->getMessage()], 500);
        }

        return new JsonResponse([
            'ok' => true,
            'message' => 'Un code t’a été envoyé par e-mail.',
            'token' => $req->getToken(),
        ]);
    }


    #[Route('/assistant/license/verify', name: 'assistant_license_verify', methods: ['POST'])]
    public function licenseVerify(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $token = (string)($data['token'] ?? '');
        $code = trim((string)($data['code'] ?? ''));

        if ($token === '' || $code === '') {
            return new JsonResponse(['ok' => false, 'error' => 'Informations manquantes.'], 400);
        }

        /** @var LicenseRequest|null $req */
        $req = $this->em->getRepository(LicenseRequest::class)->findOneBy(['token' => $token]);

        if (!$req) {
            return new JsonResponse(['ok' => false, 'error' => 'Demande introuvable.'], 404);
        }

        if ($req->isExpired()) {
            $req->setStatus(LicenseRequest::STATUS_EXPIRED);
            $this->em->flush();

            return new JsonResponse(['ok' => false, 'error' => 'Code expiré.'], 403);
        }

        if ($req->getFailedAttempts() >= 3) {
            $req->setStatus(LicenseRequest::STATUS_EXPIRED);
            $this->em->flush();

            return new JsonResponse(['ok' => false, 'error' => 'Trop de tentatives.'], 403);
        }

        if ($req->getVerificationCode() !== $code) {
            $req->incrementFailedAttempts();
            $this->em->flush();

            return new JsonResponse(['ok' => false, 'error' => 'Code incorrect.'], 403);
        }

        // Validation OK
        $req->setStatus(LicenseRequest::STATUS_CONFIRMED);
        $req->setConfirmedAt(new \DateTimeImmutable());
        $req->resetFailedAttempts();
        $req->setVerificationCode(null);
        $this->em->flush();

        // Récupération licence
        $license = $this->em->getRepository(Licence::class)
            ->findOneBy(['email' => $req->getUserEmail()]);

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
