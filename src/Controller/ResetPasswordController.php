<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\PasswordHistory;
use App\Repository\PasswordHistoryRepository;
use App\Service\SystemLoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordController extends AbstractController
{
    #[Route('/reset-password', name: 'app_reset_password_request', methods: ['POST'])]
    public function request(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        SystemLoggerService $logger
    ): Response {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['success' => false, 'message' => 'Email manquant.'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user) {
            $now = new \DateTimeImmutable();
            $lastRequest = $user->getLastResetRequestAt();

            // ⏳ Limite de 60 secondes entre deux demandes
            if ($lastRequest && $lastRequest > $now->modify('-60 seconds')) {
                return $this->json([
                    'success' => false,
                    'message' => 'Veuillez patienter avant une nouvelle demande.'
                ], 429);
            }

            // 🔐 Création du token
            $token = Uuid::v4()->toRfc4122();
            $user->setResetToken($token);
            $user->setResetTokenExpiresAt($now->modify('+1 hour'));
            $user->setLastResetRequestAt($now);
            $em->flush();

            // ✉️ Envoi du mail
            $resetUrl = 'http://localhost:8000/?resetToken=' . $token;

            $emailMessage = (new Email())
                ->from('no-reply@monsite.com')
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe')
                ->html("
                    <p>Bonjour <strong>{$user->getFirstName()}</strong>,</p>
                    <p>Pour réinitialiser votre mot de passe, cliquez sur le lien ci-dessous :</p>
                    <p><a href='$resetUrl' target='_blank'>🔒 Réinitialiser mon mot de passe</a></p>
                    <p>Ce lien est valable 1 heure.</p>
                ");

            $mailer->send($emailMessage);
            $logger->add('Demande de réinitialisation', sprintf('Lien envoyé à %s', $user->getEmail()));
        }

        return $this->json(['success' => true]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET'])]
    public function redirectToModal(string $token): Response
    {
        return $this->redirect('/?resetToken=' . urlencode($token));
    }

    #[Route('/api/reset-password-final', name: 'app_reset_password_final', methods: ['POST'])]
    public function resetPasswordFinal(
        Request $request,
        EntityManagerInterface $em,
        SystemLoggerService $logger,
        PasswordHistoryRepository $passwordHistoryRepo,
        \Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface $passwordHasherFactory
    ): Response {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $newPassword = $data['password'] ?? null;

        if (!$token || !$newPassword) {
            return $this->json(['success' => false, 'message' => 'Paramètres manquants.'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['resetToken' => $token]);
        if (!$user || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            return $this->json(['success' => false, 'message' => 'Lien invalide ou expiré.'], 400);
        }

        // ✅ Vérifie les 5 derniers mots de passe via la factory
        $hasher = $passwordHasherFactory->getPasswordHasher($user);
        $lastPasswords = $passwordHistoryRepo->findLast($user, 5);

        foreach ($lastPasswords as $history) {
            if ($hasher->verify($history->getPasswordHash(), $newPassword)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Ce mot de passe a déjà été utilisé récemment. Veuillez en choisir un autre.'
                ], 400);
            }
        }

        // 🧩 Sauvegarde l'ancien mot de passe dans l’historique
        if ($user->getPassword()) {
            $oldHistory = new \App\Entity\PasswordHistory();
            $oldHistory->setUser($user);
            $oldHistory->setPasswordHash($user->getPassword());
            $em->persist($oldHistory);
        }

        // 🔐 Nouveau mot de passe
        $userHasher = $passwordHasherFactory->getPasswordHasher($user);
        $newHash = $userHasher->hash($newPassword);
        $user->setPassword($newHash);
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $user->setLastResetRequestAt(null);

        $em->flush();

        // 🧹 Garde uniquement les 5 derniers historiques
        $passwordHistoryRepo->pruneOldPasswords($user);

        // 🧾 Log
        $logger->add(
            'Changement de mot de passe',
            sprintf('Le mot de passe de %s a été modifié avec succès.', $user->getEmail())
        );

        return $this->json(['success' => true]);
    }
}
