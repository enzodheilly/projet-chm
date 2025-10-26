<?php

namespace App\EventListener;

use App\Entity\SecurityLog;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LoginAttemptListener
{
    private RequestStack $requestStack;
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        RequestStack $requestStack,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        TokenStorageInterface $tokenStorage
    ) {
        $this->requestStack = $requestStack;
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * 🔍 Détecte le système et le navigateur depuis le User-Agent
     */
    private function parseUserAgent(string $userAgent): array
    {
        $os = 'Inconnu';
        $browser = 'Inconnu';

        // --- OS detection ---
        if (preg_match('/Windows NT 11\.0/i', $userAgent)) $os = 'Windows 11';
        elseif (preg_match('/Windows NT 10\.0/i', $userAgent)) $os = 'Windows 10';
        elseif (preg_match('/Mac OS X ([\d_]+)/i', $userAgent, $m)) $os = 'macOS ' . str_replace('_', '.', $m[1]);
        elseif (preg_match('/Android ([\d.]+)/i', $userAgent, $m)) $os = 'Android ' . $m[1];
        elseif (preg_match('/iPhone OS ([\d_]+)/i', $userAgent, $m)) $os = 'iOS ' . str_replace('_', '.', $m[1]);
        elseif (preg_match('/Linux/i', $userAgent)) $os = 'Linux';

        // --- Browser detection ---
        if (preg_match('/Edg\/([\d.]+)/i', $userAgent, $m)) $browser = 'Edge ' . $m[1];
        elseif (preg_match('/Chrome\/([\d.]+)/i', $userAgent, $m)) $browser = 'Chrome ' . $m[1];
        elseif (preg_match('/Firefox\/([\d.]+)/i', $userAgent, $m)) $browser = 'Firefox ' . $m[1];
        elseif (preg_match('/Safari\/([\d.]+)/i', $userAgent, $m) && !preg_match('/Chrome/i', $userAgent)) $browser = 'Safari ' . $m[1];
        elseif (preg_match('/OPR\/([\d.]+)/i', $userAgent, $m)) $browser = 'Opera ' . $m[1];

        return ['os' => $os, 'browser' => $browser];
    }

    /**
     * 🔴 Échec de connexion
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) return;

        $ip = $request->getClientIp() ?? '0.0.0.0';
        $userAgent = $request->headers->get('User-Agent', '');
        $ua = $this->parseUserAgent($userAgent);
        $token = $event->getAuthenticationToken();
        $username = method_exists($token, 'getUser') ? $token->getUser() : null;

        $log = new SecurityLog();
        $log->setIp($ip);
        $log->setUserAgent($userAgent);
        $log->setOs($ua['os']);
        $log->setBrowser($ua['browser']);
        $log->setSuccess(false);
        $log->setType('Connexion');
        $log->setMessage('Échec de connexion');
        $log->setCreatedAt(new \DateTimeImmutable());

        if (is_string($username)) {
            $log->setEmailAttempt($username);
            $user = $this->userRepository->findOneBy(['email' => $username]);
            if ($user) $log->setUser($user);
        } elseif ($username instanceof \App\Entity\User) {
            $log->setUser($username);
            $log->setEmailAttempt($username->getEmail());
        }

        $this->em->persist($log);
        $this->em->flush();
    }

    /**
     * 🟢 Connexion réussie
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) return;

        $ip = $request->getClientIp() ?? '0.0.0.0';
        $userAgent = $request->headers->get('User-Agent', '');
        $ua = $this->parseUserAgent($userAgent);
        $user = $event->getAuthenticationToken()->getUser();

        $log = new SecurityLog();
        $log->setIp($ip);
        $log->setUserAgent($userAgent);
        $log->setOs($ua['os']);
        $log->setBrowser($ua['browser']);
        $log->setSuccess(true);
        $log->setUser($user);
        $log->setEmailAttempt($user->getEmail());
        $log->setType('Connexion');
        $log->setMessage('Connexion réussie');
        $log->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($log);

        if (method_exists($user, 'setLastLoginAt')) {
            $user->setLastLoginAt(new \DateTimeImmutable());
        }
        if (method_exists($user, 'setLastLoginIp')) {
            $user->setLastLoginIp($ip);
        }

        // ✅ Stocke les infos de session pour détection future
        $session = $request->getSession();
        $session->set('login_ip', $ip);
        $session->set('login_agent', substr($userAgent, 0, 120));

        $this->em->flush();
    }

    /**
     * 👁️ Détection de changement de contexte de session (IP / appareil)
     */
    public function checkSessionContext(): void
    {
        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;
        if (!$user instanceof \App\Entity\User) return;

        $request = $this->requestStack->getCurrentRequest();
        if (!$request || !$request->hasSession()) return;

        $session = $request->getSession();
        $currentIp = $request->getClientIp();
        $currentAgent = substr($request->headers->get('User-Agent'), 0, 120);

        if (!$session->has('login_ip')) return;

        $initialIp = $session->get('login_ip');
        $initialAgent = $session->get('login_agent');

        if ($currentIp !== $initialIp || $currentAgent !== $initialAgent) {
            $log = new SecurityLog();
            $log->setIp($currentIp);
            $log->setUser($user);
            $log->setSuccess(true);
            $log->setType('Session');
            $log->setMessage(sprintf('Session suspecte : IP %s → %s ou appareil différent', $initialIp, $currentIp));
            $log->setCreatedAt(new \DateTimeImmutable());

            $this->em->persist($log);
            $this->em->flush();

            // Mise à jour des données pour éviter le spam de logs
            $session->set('login_ip', $currentIp);
            $session->set('login_agent', $currentAgent);
        }
    }
}
