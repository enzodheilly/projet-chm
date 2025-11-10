<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\SystemLoggerService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class VerifyCodeController extends AbstractController
{
    #[Route('/verify/code', name: 'app_verify_code', methods: ['POST'])]
    public function verifyCode(
        Request $request,
        SessionInterface $session,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        SystemLoggerService $logger
    ): JsonResponse {
        $email = $session->get('verify_email');
        $code = trim($request->request->get('code', ''));

        if (!$email) {
            $msg = 'Aucun e-mail de vérification trouvé. Veuillez vous inscrire à nouveau.';
            $logger->add('Erreur vérification compte', $msg);
            return $this->json(['success' => false, 'message' => $msg], 400);
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $msg = 'Utilisateur introuvable.';
            $logger->add('Erreur vérification compte', $msg);
            return $this->json(['success' => false, 'message' => $msg], 404);
        }

        // ⚠️ Si déjà vérifié
        if ($user->isVerified()) {
            return $this->json(['success' => false, 'message' => 'Ce compte est déjà vérifié.']);
        }

        // ⏳ Vérifie expiration du code
        if (!$user->getVerificationCodeExpiresAt() || $user->getVerificationCodeExpiresAt() < new \DateTimeImmutable()) {
            return $this->json(['success' => false, 'message' => 'Le code a expiré. Veuillez en redemander un.']);
        }

        // ❌ Mauvais code
        if ($user->getVerificationCode() !== $code) {
            return $this->json(['success' => false, 'message' => 'Code incorrect.']);
        }

        // ✅ Succès
        $user->setIsVerified(true);
        $user->setVerificationCode(null);
        $user->setVerificationCodeExpiresAt(null);
        $entityManager->flush();

        $session->remove('verify_email');
        $logger->add('Compte vérifié', sprintf('Le compte %s a été vérifié avec succès.', $user->getEmail()));

        // 💡 On renvoie un signal de succès SANS message texte
        return $this->json(['success' => true]);
    }

    #[Route('/verify/code/resend', name: 'app_resend_code', methods: ['GET'])]
    public function resendCode(
        Request $request,
        SessionInterface $session,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        SystemLoggerService $logger
    ): JsonResponse {
        $email = $session->get('verify_email');

        if (!$email) {
            return $this->json(['success' => false, 'message' => 'Aucun e-mail trouvé dans la session.'], 400);
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Utilisateur introuvable.'], 404);
        }

        // 🔒 Vérifie délai minimal de 15 minutes
        $lastSent = $user->getVerificationCodeExpiresAt();
        if ($lastSent && $lastSent > new \DateTimeImmutable('-14 minutes')) {
            $wait = $lastSent->diff(new \DateTimeImmutable())->i;
            $msg = sprintf('Veuillez patienter encore %d minute(s) avant de demander un nouveau code.', 15 - $wait);
            return $this->json(['success' => false, 'message' => $msg], 429);
        }

        // 🆕 Nouveau code
        $newCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->setVerificationCode($newCode);
        $user->setVerificationCodeExpiresAt(new \DateTimeImmutable('+15 minutes'));
        $entityManager->flush();

        try {
            $emailMessage = (new Email())
                ->from('no-reply@monsite.com')
                ->to($user->getEmail())
                ->subject('Nouveau code de vérification')
                ->html("
                    <p>Bonjour <strong>{$user->getFirstName()}</strong>,</p>
                    <p>Voici votre nouveau code de vérification :</p>
                    <h2 style='font-size: 24px; letter-spacing: 4px; color: #005b94;'>{$newCode}</h2>
                    <p>Ce code est valable pendant 15 minutes.</p>
                ");
            $mailer->send($emailMessage);

            $logger->add('Nouveau code envoyé', sprintf('Nouveau code envoyé à %s', $user->getEmail()));

            return $this->json(['success' => true, 'message' => '✅ Un nouveau code vous a été envoyé par e-mail.']);
        } catch (\Throwable $e) {
            $logger->add('Erreur renvoi code', $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Erreur lors de l’envoi du code.'], 500);
        }
    }
}
