<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\SystemLoggerService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class VerifyCodeController extends AbstractController
{
    #[Route('/verify/code', name: 'app_verify_code')]
    public function verifyCode(
        Request $request,
        SessionInterface $session,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        SystemLoggerService $logger
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        // ✅ Récupération sécurisée du redirect
        $redirect = $request->query->get('redirect') ?? $session->get('redirect_after_verify');
        if ($redirect && !str_starts_with($redirect, '/')) {
            $redirect = null; // Protection contre les redirections externes
        }

        $email = $session->get('verify_email');
        if (!$email) {
            $this->addFlash('error', 'Aucun email de vérification trouvé. Veuillez vous inscrire à nouveau.');
            $logger->add('Erreur vérification compte', 'Aucun email trouvé dans la session.');
            return $this->redirectToRoute('app_register', ['redirect' => $redirect]);
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            $logger->add('Erreur vérification compte', sprintf('Utilisateur introuvable pour %s', $email));
            return $this->redirectToRoute('app_register', ['redirect' => $redirect]);
        }

        if ($request->isMethod('POST')) {
            $code = trim($request->request->get('code'));

            if ($user->isVerified()) {
                $this->addFlash('info', 'Ce compte est déjà vérifié.');
                $logger->add('Vérification inutile', sprintf('Le compte %s est déjà vérifié.', $user->getEmail()));
                return $this->redirectToRoute('app_login', ['redirect' => $redirect]);
            }

            if (!$user->getVerificationCodeExpiresAt() || $user->getVerificationCodeExpiresAt() < new \DateTimeImmutable()) {
                $this->addFlash('error', 'Le code a expiré. Veuillez en redemander un.');
                $logger->add('Code expiré', sprintf('Le code de %s a expiré.', $user->getEmail()));
                return $this->redirectToRoute('app_verify_code', ['redirect' => $redirect]);
            }

            if ($user->getVerificationCode() !== $code) {
                $this->addFlash('error', 'Code incorrect.');
                $logger->add('Code incorrect', sprintf('Code erroné pour %s.', $user->getEmail()));
                return $this->redirectToRoute('app_verify_code', ['redirect' => $redirect]);
            }

            // ✅ Succès
            $user->setIsVerified(true);
            $user->setVerificationCode(null);
            $user->setVerificationCodeExpiresAt(null);
            $entityManager->flush();

            $session->remove('verify_email');

            $logger->add('Compte vérifié', sprintf('Le compte %s a été vérifié avec succès.', $user->getEmail()));
            $this->addFlash('success', 'Votre compte a été vérifié avec succès ! Vous pouvez maintenant vous connecter.');

            // 🔁 Redirige vers login avec redirect conservé (sécurisé)
            if ($redirect) {
                return $this->redirectToRoute('app_login', ['redirect' => $redirect]);
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('verify_code/verify_code.html.twig', [
            'redirect' => $redirect,
        ]);
    }

    #[Route('/verify/code/resend', name: 'app_resend_code')]
    public function resendCode(
        SessionInterface $session,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        SystemLoggerService $logger
    ): Response {
        $email = $session->get('verify_email');
        $redirect = $session->get('redirect_after_verify');

        // ✅ Sécurisation du redirect ici aussi
        if ($redirect && !str_starts_with($redirect, '/')) {
            $redirect = null;
        }

        if (!$email) {
            $this->addFlash('danger', 'Aucun e-mail trouvé dans la session.');
            return $this->redirectToRoute('app_register', ['redirect' => $redirect]);
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $this->addFlash('danger', 'Aucun utilisateur trouvé avec cet e-mail.');
            return $this->redirectToRoute('app_register', ['redirect' => $redirect]);
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Votre compte est déjà activé.');
            return $this->redirectToRoute('app_login', ['redirect' => $redirect]);
        }

        // 🔁 Nouveau code
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
                    <h2 style='font-size: 24px; letter-spacing: 4px; color: #005b94;'>$newCode</h2>
                    <p>Ce code est valable pendant 15 minutes.</p>
                ");

            $mailer->send($emailMessage);
            $this->addFlash('success', '📬 Un nouveau code vous a été envoyé par e-mail.');
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Erreur lors de l’envoi du nouveau code.');
        }

        return $this->redirectToRoute('app_verify_code', ['redirect' => $redirect]);
    }
}
