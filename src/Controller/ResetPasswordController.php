<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\SystemLoggerService; // ✅ ajouté
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
    #[Route('/reset-password', name: 'app_reset_password_request', methods: ['GET', 'POST'])]
    public function request(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        SystemLoggerService $logger // ✅ injecté ici
    ): Response {
        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true);
            $email = $data['email'] ?? null;

            if (!$email) {
                return $this->json(['success' => false, 'message' => 'Email manquant.'], 400);
            }

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user) {
                $now = new \DateTimeImmutable();
                $lastRequest = $user->getLastResetRequestAt();

                // Anti-spam : 1 demande / 60 secondes
                if ($lastRequest && $lastRequest > $now->modify('-60 seconds')) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Veuillez patienter avant une nouvelle demande.'
                    ], 429);
                }

                // Génération du token
                $token = Uuid::v4()->toRfc4122();
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt($now->modify('+1 hour'));
                $user->setLastResetRequestAt($now);
                $em->flush();

                // Envoi de l’email
                $emailMessage = (new Email())
                    ->from('no-reply@monsite.com')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->html("
        <div style='font-family:Poppins,Arial,sans-serif;color:#333;'>
            <p>Bonjour <strong>{$user->getFirstName()}</strong>,</p>
            <p>Vous avez demandé à réinitialiser votre mot de passe.</p>
            <p>Pour continuer, cliquez sur le bouton ci-dessous :</p>

            <p style='text-align:center;margin:25px 0;'>
                <a href='http://localhost:8000/reset-password/$token' 
                   target='_self'
                   style='background-color:#ff6600;
                          color:#fff;
                          padding:12px 24px;
                          border-radius:8px;
                          text-decoration:none;
                          font-weight:600;
                          font-size:15px;
                          display:inline-block;'>
                    🔒 Réinitialiser mon mot de passe
                </a>
            </p>

            <p style='font-size:0.9rem;color:#555;'>
                Ce lien est valable pendant <strong>1 heure</strong>.<br>
                Si vous n'êtes pas à l'origine de cette demande, ignorez simplement ce message.
            </p>

            <hr style='border:none;border-top:1px solid #eee;margin:25px 0;'>
            <p style='font-size:0.75rem;color:#999;text-align:center;'>
                CHM Saleux — Système de réinitialisation sécurisée
            </p>
        </div>
    ");

                $mailer->send($emailMessage);


                // ✅ Log : demande de réinitialisation
                $logger->add(
                    'Demande de réinitialisation',
                    sprintf('Un email de réinitialisation a été envoyé à %s.', $user->getEmail())
                );
            }

            return $this->json(['success' => true]);
        }

        return $this->render('reset_password/wizard.html.twig', [
            'token' => null,
            'invalid_token' => false
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET'])]
    public function reset(string $token, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['resetToken' => $token]);
        $isValid = $user && $user->getResetTokenExpiresAt() > new \DateTimeImmutable();

        return $this->render('reset_password/wizard.html.twig', [
            'token' => $isValid ? $token : null,
            'invalid_token' => !$isValid
        ]);
    }

    #[Route('/api/reset-password-final', name: 'app_reset_password_final', methods: ['POST'])]
    public function resetPasswordFinal(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        SystemLoggerService $logger // ✅ injecté ici aussi
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

        // ✅ Réinitialisation du mot de passe
        $user->setPassword($hasher->hashPassword($user, $newPassword));
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $user->setLastResetRequestAt(null);
        $em->flush();

        // ✅ Log : mot de passe modifié
        $logger->add(
            'Changement de mot de passe',
            sprintf('Le mot de passe de %s a été modifié avec succès.', $user->getEmail())
        );

        return $this->json(['success' => true]);
    }
}
