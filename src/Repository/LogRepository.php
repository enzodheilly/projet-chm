<?php

namespace App\Repository;

use App\Entity\Log;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Log>
 */
class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }

    /**
     * 🕓 Récupère les derniers logs (par défaut 20)
     */
    public function findRecent(int $limit = 20): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 🔍 Recherche des logs selon un mot-clé dans le type ou le message
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.message LIKE :query OR l.type LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 📊 Compte le nombre total de logs enregistrés
     */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * 📈 Retourne le nombre de logs par type (utile pour les graphiques)
     */
    public function countByType(): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.type, COUNT(l.id) AS total')
            ->groupBy('l.type')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 🧹 Supprime les logs plus vieux que X jours
     */
    public function purgeOlderThan(int $days): int
    {
        $limitDate = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('l')
            ->delete()
            ->where('l.createdAt < :limit')
            ->setParameter('limit', $limitDate)
            ->getQuery()
            ->execute();
    }
}
