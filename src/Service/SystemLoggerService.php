<?php

namespace App\Service;

use App\Entity\Log;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class SystemLoggerService
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    public function add(string $type, string $message): void
    {
        $user = $this->security->getUser();

        $log = new Log();
        $log->setType($type)
            ->setMessage($message)
            ->setUser($user ? $user->getUserIdentifier() : 'Système');

        $this->em->persist($log);
        $this->em->flush();
    }
}
