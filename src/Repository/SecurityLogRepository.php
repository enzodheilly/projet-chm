<?php

namespace App\Repository;

use App\Entity\SecurityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SecurityLog>
 */
class SecurityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SecurityLog::class);
    }

    /**
     * 🕓 Récupère les logs triés du plus récent au plus ancien
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 🔍 Recherche des logs selon un mot-clé (nom d’utilisateur, IP, action, statut)
     */
    public function searchLogs(?string $term): array
    {
        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC');

        if ($term) {
            $qb->andWhere('
                l.username LIKE :term 
                OR l.ipAddress LIKE :term 
                OR l.action LIKE :term
                OR l.status LIKE :term
            ')
                ->setParameter('term', '%' . $term . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 🕓 Récupère les dernières tentatives (succès ou échecs)
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 📈 Compte le nombre total de connexions réussies
     */
    public function countSuccessful(): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.success = :success')
            ->setParameter('success', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * ❌ Compte le nombre d’échecs récents (ex: 24h)
     */
    public function countFailedSince(\DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.success = false')
            ->andWhere('l.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * 📊 Retourne les 7 derniers jours pour les statistiques
     * (utile pour ton graphique Chart.js)
     */
    public function getSuccessCountByDay(int $days = 7): array
    {
        $from = new \DateTimeImmutable("-{$days} days");

        $logs = $this->createQueryBuilder('l')
            ->where('l.success = true')
            ->andWhere('l.createdAt >= :from')
            ->setParameter('from', $from)
            ->orderBy('l.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = (new \DateTimeImmutable("-{$i} days"))->format('Y-m-d');
            $result[$day] = 0;
        }

        foreach ($logs as $log) {
            /** @var \App\Entity\SecurityLog $log */
            $day = $log->getCreatedAt()->format('Y-m-d');
            if (isset($result[$day])) {
                $result[$day]++;
            }
        }

        return $result;
    }

    /**
     * 🧹 Supprime les logs plus vieux que X jours
     */
    public function purgeOlderThan(int $days): int
    {
        $limit = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('l')
            ->delete()
            ->where('l.createdAt < :limit')
            ->setParameter('limit', $limit)
            ->getQuery()
            ->execute();
    }
}
