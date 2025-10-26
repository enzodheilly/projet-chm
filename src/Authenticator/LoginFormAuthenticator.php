<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\SystemLoggerService; // ✅ ajout
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    private RouterInterface $router;
    private UserPasswordHasherInterface $passwordHasher;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private SystemLoggerService $logger; // ✅

    public function __construct(
        RouterInterface $router,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        SystemLoggerService $logger // ✅ injecté ici
    ) {
        $this->router = $router;
        $this->passwordHasher = $passwordHasher;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $ip = $request->getClientIp();

        return new Passport(
            new UserBadge($email, function ($userIdentifier) use ($password, $ip) {
                $user = $this->userRepository->findOneBy(['email' => $userIdentifier]);

                // ❌ Utilisateur inexistant
                if (!$user) {
                    $this->logger->add('Échec de connexion', sprintf('Tentative de connexion avec un email inconnu (%s)', $userIdentifier));
                    throw new CustomUserMessageAuthenticationException('Adresse e-mail ou mot de passe incorrect.');
                }

                // 🔒 Compte verrouillé
                if ($user->getLockedUntil() && $user->getLockedUntil() > new \DateTimeImmutable()) {
                    $lockedUntil = $user->getLockedUntil();
                    $remaining = $lockedUntil->getTimestamp() - time();

                    if ($remaining > 3153600000) { // ~100 ans
                        $this->logger->add('Blocage permanent', sprintf('Compte définitivement bloqué : %s', $user->getEmail()));
                        throw new CustomUserMessageAuthenticationException('Votre compte est bloqué définitivement.');
                    }

                    $minutes = ceil($remaining / 60);
                    $this->logger->add('Blocage temporaire', sprintf('Connexion refusée : compte de %s bloqué (%d min restantes).', $user->getEmail(), $minutes));
                    throw new CustomUserMessageAuthenticationException(
                        sprintf('Compte temporairement bloqué (%d min restantes).', $minutes)
                    );
                }

                // 📧 Compte non vérifié
                if (!$user->isVerified()) {
                    $this->logger->add('Connexion refusée', sprintf('Utilisateur %s a tenté de se connecter sans avoir vérifié son email.', $user->getEmail()));
                    throw new CustomUserMessageAuthenticationException('Vous devez vérifier votre email avant de vous connecter.');
                }

                // 🔑 Mot de passe incorrect
                if (!$this->passwordHasher->isPasswordValid($user, $password)) {
                    $failed = ($user->getFailedAttempts() ?? 0) + 1;
                    $user->setFailedAttempts($failed);

                    // Blocage progressif
                    if ($failed >= 5) {
                        switch ($failed) {
                            case 5:
                                $user->setLockedUntil(new \DateTimeImmutable('+3 minutes'));
                                $this->logger->add('Blocage de compte', sprintf('Compte %s bloqué 3 min après 5 tentatives.', $user->getEmail()));
                                break;
                            case 6:
                                $user->setLockedUntil(new \DateTimeImmutable('+5 minutes'));
                                $this->logger->add('Blocage de compte', sprintf('Compte %s bloqué 5 min après 6 tentatives.', $user->getEmail()));
                                break;
                            case 7:
                                $user->setLockedUntil(new \DateTimeImmutable('+10 minutes'));
                                $this->logger->add('Blocage de compte', sprintf('Compte %s bloqué 10 min après 7 tentatives.', $user->getEmail()));
                                break;
                            case 8:
                                $user->setLockedUntil(new \DateTimeImmutable('+20 minutes'));
                                $this->logger->add('Blocage de compte', sprintf('Compte %s bloqué 20 min après 8 tentatives.', $user->getEmail()));
                                break;
                            default:
                                $user->setLockedUntil(new \DateTimeImmutable('+100 years'));
                                $this->logger->add('Blocage permanent', sprintf('Compte %s bloqué définitivement après trop d’échecs.', $user->getEmail()));
                                break;
                        }
                    }

                    $this->entityManager->flush();
                    $this->logger->add('Échec de connexion', sprintf('Mot de passe incorrect pour %s', $user->getEmail()));
                    throw new CustomUserMessageAuthenticationException('Mot de passe incorrect.');
                }

                // ✅ Succès → on remet à zéro les tentatives
                $user->setFailedAttempts(0);
                $user->setLockedUntil(null);
                $this->entityManager->flush();

                // ✅ Log connexion réussie
                $this->logger->add('Connexion réussie', sprintf('Utilisateur %s connecté avec succès depuis IP %s', $user->getEmail(), $ip));

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new RememberMeBadge(),
                new PasswordUpgradeBadge($password, $this->userRepository),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        $redirectUrl = $request->request->get('redirect') ?? $request->query->get('redirect');

        // ✅ Protection : empêche les redirections externes
        if ($redirectUrl && !str_starts_with($redirectUrl, '/')) {
            $redirectUrl = null;
        }

        // ✅ Si un redirect valide est présent → on redirige
        if ($redirectUrl) {
            return new RedirectResponse($redirectUrl);
        }

        // Sinon → redirection par défaut
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
        if ($targetPath) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate('app_login');
    }
}
