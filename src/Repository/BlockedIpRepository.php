<?php

namespace App\Repository;

use App\Entity\BlockedIp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BlockedIpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlockedIp::class);
    }

    public function isBlocked(string $ip): bool
    {
        $entry = $this->findOneBy(['ip' => $ip]);
        return $entry && !$entry->isExpired();
    }

    public function purgeExpired(): int
    {
        $now = new \DateTimeImmutable();
        return $this->createQueryBuilder('b')
            ->delete()
            ->where('b.expiresAt IS NOT NULL')
            ->andWhere('b.expiresAt < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();
    }
}
