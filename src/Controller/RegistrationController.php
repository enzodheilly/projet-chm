<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
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
use Symfony\Component\HttpFoundation\JsonResponse;
use GuzzleHttp\Client;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        SessionInterface $session,
        SystemLoggerService $logger,
        UserRepository $userRepo
    ): Response {
        try {
            // Déjà connecté ?
            if ($this->getUser()) {
                return new JsonResponse([
                    'success' => false,
                    'errors'  => ['Vous êtes déjà connecté.']
                ], 400);
            }

            // 🔒 Vérification Turnstile
            $turnstileResponse = $request->request->get('cf-turnstile-response');
            if (!$turnstileResponse) {
                return new JsonResponse([
                    'success' => false,
                    'errors'  => ['Vérification anti-robot manquante.']
                ], 400);
            }

            $client = new Client();
            $response = $client->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'form_params' => [
                    'secret'   => $_ENV['TURNSTILE_SECRET_KEY'],
                    'response' => $turnstileResponse,
                    'remoteip' => $request->getClientIp(),
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            if (empty($result['success']) || $result['success'] !== true) {
                return new JsonResponse([
                    'success' => false,
                    'errors'  => ['Vérification anti-robot échouée. Merci de réessayer.']
                ], 400);
            }

            // Récupération *propre* des données envoyées par la modale
            $data = $request->request->all('registration_form');
            $firstName   = trim($data['firstName'] ?? '');
            $lastName    = trim($data['lastName'] ?? '');
            $email       = trim($data['email'] ?? '');
            $accepted    = (bool)($data['acceptedTerms'] ?? false);

            $passArray   = $data['password'] ?? [];
            $password1   = $passArray['first']  ?? '';
            $password2   = $passArray['second'] ?? '';

            $errors = [];

            // Validations serveur
            if (!$accepted) {
                $errors[] = "Vous devez accepter les conditions générales pour continuer.";
            }

            if ($firstName === '') {
                $errors[] = "Le prénom est obligatoire.";
            }
            if ($lastName === '') {
                $errors[] = "Le nom est obligatoire.";
            }

            if ($email === '') {
                $errors[] = "L’email est obligatoire.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Cette adresse email n’est pas valide.";
            } elseif ($userRepo->findOneBy(['email' => $email])) {
                $errors[] = "Cette adresse email est déjà utilisée.";
            }

            if ($password1 === '' || $password2 === '') {
                $errors[] = "Les deux champs mot de passe sont obligatoires.";
            } elseif ($password1 !== $password2) {
                $errors[] = "Les mots de passe doivent correspondre.";
            } else {
                if (strlen($password1) < 12) {
                    $errors[] = "Votre mot de passe doit contenir au moins 12 caractères.";
                }
                if (!preg_match('/[A-Z]/', $password1)) {
                    $errors[] = "Votre mot de passe doit contenir au moins une majuscule.";
                }
                if (!preg_match('/[a-z]/', $password1)) {
                    $errors[] = "Votre mot de passe doit contenir au moins une minuscule.";
                }
                if (!preg_match('/\d/', $password1)) {
                    $errors[] = "Votre mot de passe doit contenir au moins un chiffre.";
                }
                if (!preg_match('/[\W_]/', $password1)) {
                    $errors[] = "Votre mot de passe doit contenir au moins un caractère spécial.";
                }
            }

            if (!empty($errors)) {
                return new JsonResponse([
                    'success' => false,
                    'errors'  => $errors
                ], 400);
            }

            // Création de l'utilisateur
            $user = new User();
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setEmail($email);

            $hashed = $passwordHasher->hashPassword($user, $password1);
            $user->setPassword($hashed);

            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->setVerificationCode($code);
            $user->setVerificationCodeExpiresAt(new \DateTimeImmutable('+15 minutes'));
            $user->setRoles(['ROLE_USER']);
            $user->setIsVerified(false);

            $entityManager->persist($user);
            $entityManager->flush();

            $session->set('verify_email', $user->getEmail());

            // Envoi du mail
            try {
                $emailMessage = (new Email())
                    ->from('no-reply@monsite.com')
                    ->to($user->getEmail())
                    ->subject('Votre code de vérification - CHM Saleux')
                    ->html("
                        <p>Bonjour <strong>{$user->getFirstName()}</strong>,</p>
                        <p>Merci de vous être inscrit sur le site du CHM Saleux 💪</p>
                        <p>Voici votre code de vérification :</p>
                        <h2 style='font-size: 24px; letter-spacing: 4px; color: #005b94;'>{$code}</h2>
                        <p>Ce code est valable pendant <strong>15 minutes</strong>.</p>
                        <p>Entrez-le sur la page de vérification pour activer votre compte.</p>
                    ");
                $mailer->send($emailMessage);
                $logger->add('Email de vérification', sprintf('Email envoyé à %s', $user->getEmail()));
            } catch (\Throwable $e) {
                $logger->add('Erreur email', sprintf('Échec d’envoi à %s : %s', $user->getEmail(), $e->getMessage()));
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Compte créé avec succès. Un code de vérification a été envoyé par e-mail.'
            ]);
        } catch (\Throwable $e) {
            $logger->add('Erreur inscription', 'Erreur inattendue : ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'errors'  => ['Erreur serveur : ' . $e->getMessage()]
            ], 500);
        }
    }
}
