<?php

namespace App\EventSubscriber;

use App\Service\SystemLoggerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(private SystemLoggerService $logger) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (method_exists($user, 'getUserIdentifier')) {
            $this->logger->add(
                'Connexion réussie',
                sprintf('L’utilisateur %s s’est connecté avec succès.', $user->getUserIdentifier())
            );
        }
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        if ($user && method_exists($user, 'getUserIdentifier')) {
            $this->logger->add(
                'Déconnexion',
                sprintf('L’utilisateur %s s’est déconnecté.', $user->getUserIdentifier())
            );
        }
    }
}
