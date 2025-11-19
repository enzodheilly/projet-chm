<?php

namespace App\Authenticator;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\SystemLoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\SecurityRequestAttributesInterface;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    private RouterInterface $router;
    private UserPasswordHasherInterface $passwordHasher;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private SystemLoggerService $logger;

    public function __construct(
        RouterInterface $router,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        SystemLoggerService $logger
    ) {
        $this->router = $router;
        $this->passwordHasher = $passwordHasher;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function authenticate(Request $request): Passport
    {
        $email = trim($request->request->get('email', ''));
        $password = $request->request->get('password', '');
        $ip = $request->getClientIp();

        return new Passport(
            new UserBadge($email, function ($userIdentifier) use ($password, $ip) {
                $user = $this->userRepository->findOneBy(['email' => $userIdentifier]);

                // ❌ Utilisateur inexistant
                if (!$user) {
                    $this->logger->add('Échec de connexion', sprintf('Tentative avec un email inconnu (%s)', $userIdentifier));
                    throw new CustomUserMessageAuthenticationException('Adresse e-mail ou mot de passe incorrect.');
                }

                // 🔒 Compte verrouillé
                if ($user->getLockedUntil() && $user->getLockedUntil() > new \DateTimeImmutable()) {
                    $remaining = $user->getLockedUntil()->getTimestamp() - time();
                    $minutes = ceil($remaining / 60);
                    throw new CustomUserMessageAuthenticationException(
                        sprintf('Compte temporairement bloqué (%d min restantes).', $minutes)
                    );
                }

                // 📧 Compte non vérifié
                if (!$user->isVerified()) {
                    throw new CustomUserMessageAuthenticationException('Veuillez vérifier votre e-mail avant de vous connecter.');
                }

                // 🔑 Mot de passe incorrect
                if (!$this->passwordHasher->isPasswordValid($user, $password)) {
                    $failed = ($user->getFailedAttempts() ?? 0) + 1;
                    $user->setFailedAttempts($failed);

                    if ($failed >= 5) {
                        $user->setLockedUntil(new \DateTimeImmutable('+3 minutes'));
                        $this->logger->add('Blocage de compte', sprintf('Compte %s bloqué 3 min.', $user->getEmail()));
                    }

                    $this->entityManager->flush();
                    throw new CustomUserMessageAuthenticationException('Adresse e-mail ou mot de passe incorrect.');
                }

                // ✅ Succès : réinitialise les compteurs
                $user->setFailedAttempts(0);
                $user->setLockedUntil(null);
                $user->setLastLoginAt(new \DateTimeImmutable());
                $user->setLastLoginIp($ip);
                $this->entityManager->flush();

                $this->logger->add('Connexion réussie', sprintf('Utilisateur %s connecté depuis %s', $user->getEmail(), $ip));

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new RememberMeBadge(),
                new PasswordUpgradeBadge($password, $this->userRepository),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $user = $token->getUser();
        $this->logger->add('Connexion réussie', sprintf('Utilisateur %s connecté.', $user->getEmail()));

        // ⚡ Force la session à être écrite AVANT de renvoyer la réponse JSON
        $request->getSession()->save();

        return new JsonResponse([
            'success' => true,
            'message' => 'Connexion réussie',
            'redirect' => $this->router->generate('home'),
        ]);
    }


    public function onAuthenticationFailure(Request $request, \Throwable $exception): Response
    {
        // ✅ Retour JSON avec le message d’erreur
        return new JsonResponse([
            'success' => false,
            'message' => $exception->getMessage() ?: 'Identifiants incorrects',
        ], 401);
    }

    protected function getLoginUrl(Request $request): string
    {
        // Ne sert plus vraiment, mais doit exister
        return $this->router->generate('app_login');
    }
}
