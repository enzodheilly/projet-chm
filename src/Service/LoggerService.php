<?php

namespace App\Service;

use App\Entity\SecurityLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class LoggerService
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    public function add(string $type, string $message, bool $success = true): void
    {
        $user = $this->security->getUser();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        $log = new SecurityLog();
        $log->setType($type);
        $log->setMessage($message);
        $log->setUser($user ? $user->getUserIdentifier() : 'Système');
        $log->setEmail($user ? $user->getUserIdentifier() : null);
        $log->setIpAddress($ip);
        $log->setCreatedAt(new \DateTimeImmutable());
        $log->setSuccess($success);

        $this->em->persist($log);
        $this->em->flush();
    }
}
