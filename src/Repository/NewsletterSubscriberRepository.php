<?php

namespace App\Repository;

use App\Entity\NewsletterSubscriber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NewsletterSubscriberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsletterSubscriber::class);
    }

    /**
     * Compte les abonnés confirmés à la newsletter
     */
    public function countConfirmed(): int
    {
        return $this->count(['isConfirmed' => true]);
    }

    /**
     * Récupère les abonnés récents
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('n')
            ->orderBy('n.subscribedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre d’abonnés par jour sur les X derniers jours
     * (version SQL native pour éviter l’erreur DQL)
     */
    public function countByDay(int $days = 7): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT DATE(subscribed_at) AS day, COUNT(id) AS count
            FROM newsletter_subscriber
            WHERE subscribed_at >= :date
            GROUP BY day
            ORDER BY day ASC
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'date' => (new \DateTimeImmutable("-{$days} days"))->format('Y-m-d H:i:s')
        ]);

        $rows = $result->fetchAllAssociative();

        $data = [];
        foreach ($rows as $r) {
            $data[$r['day']] = (int) $r['count'];
        }

        return $data;
    }
}
