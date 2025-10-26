<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\SystemLoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        SessionInterface $session,
        SystemLoggerService $logger
    ): Response {
        try {
            // ✅ Si l’utilisateur est déjà connecté → redirection vers l’accueil
            if ($this->getUser()) {
                return $this->redirectToRoute('home');
            }

            $user = new User();
            $form = $this->createForm(RegistrationFormType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                // ✅ Récupère la redirection et la sécurise
                $redirectUrl = $request->query->get('redirect') ?? $request->request->get('redirect');
                if ($redirectUrl && !str_starts_with($redirectUrl, '/')) {
                    $redirectUrl = null; // protection anti-redirection externe
                }

                // 🔒 Hachage du mot de passe
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                );
                $user->setPassword($hashedPassword);

                // ✅ Génère le code de vérification
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $user->setVerificationCode($code);
                $user->setVerificationCodeExpiresAt(new \DateTimeImmutable('+15 minutes'));
                $user->setRoles(['ROLE_USER']);
                $user->setIsVerified(false);

                // 🔄 Enregistre en base
                $entityManager->persist($user);
                $entityManager->flush();

                // 🧠 Stocke l’e-mail pour la vérification
                $session->set('verify_email', $user->getEmail());

                // 🧭 Stocke la redirection future
                if ($redirectUrl) {
                    $session->set('redirect_after_verify', $redirectUrl);
                }

                // ✉️ Envoi du mail de vérification
                try {
                    $emailMessage = (new Email())
                        ->from('no-reply@monsite.com')
                        ->to($user->getEmail())
                        ->subject('Votre code de vérification - CHM Saleux')
                        ->html("
                            <p>Bonjour <strong>{$user->getFirstName()}</strong>,</p>
                            <p>Merci de vous être inscrit sur le site du CHM Saleux 💪</p>
                            <p>Voici votre code de vérification :</p>
                            <h2 style='font-size: 24px; letter-spacing: 4px; color: #005b94;'>$code</h2>
                            <p>Ce code est valable pendant <strong>15 minutes</strong>.</p>
                            <p>Entrez-le sur la page de vérification pour activer votre compte.</p>
                        ");

                    $mailer->send($emailMessage);
                    $logger->add('Email de vérification', sprintf('Email envoyé à %s', $user->getEmail()));
                } catch (\Throwable $e) {
                    $logger->add('Erreur email', sprintf('Échec d’envoi à %s : %s', $user->getEmail(), $e->getMessage()));
                }

                // ✅ Redirection vers la page de vérification avec le redirect conservé
                return $this->redirectToRoute('app_verify_code', [
                    'redirect' => $redirectUrl
                ]);
            }

            return $this->render('register/register.html.twig', [
                'registrationForm' => $form->createView(),
            ]);
        } catch (\Throwable $e) {
            $logger->add('Erreur inscription', 'Erreur inattendue : ' . $e->getMessage());
            return new Response('<pre style="color:red;">' . $e->getMessage() . '</pre>');
        }
    }
}
