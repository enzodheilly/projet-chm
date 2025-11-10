<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\PasswordHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PasswordHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordHistory::class);
    }

    /**
     * 🔹 Récupère les N derniers mots de passe d’un utilisateur (par défaut 5)
     */
    public function findLast(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('ph')
            ->andWhere('ph.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ph.changedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 🔹 Supprime tous les historiques au-delà des 5 derniers
     */
    public function pruneOldPasswords(User $user, int $keep = 5): void
    {
        $histories = $this->createQueryBuilder('ph')
            ->andWhere('ph.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ph.changedAt', 'DESC')
            ->getQuery()
            ->getResult();

        if (count($histories) > $keep) {
            $toRemove = array_slice($histories, $keep);
            $em = $this->getEntityManager();
            foreach ($toRemove as $old) {
                $em->remove($old);
            }
            $em->flush();
        }
    }
}
