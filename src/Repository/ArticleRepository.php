<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Récupère les articles filtrés par catégorie et/ou date
     */
    public function findFilteredArticles(?int $categorieId, ?string $dateFrom, ?string $dateTo, int $page = 1, int $limit = 16): array
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.publishedAt', 'DESC');

        // 🔹 Filtre par catégorie
        if ($categorieId) {
            $qb->andWhere('a.categorie = :cat')
                ->setParameter('cat', $categorieId);
        }

        // 🔹 Filtre date "De"
        if (!empty($dateFrom)) {
            try {
                $qb->andWhere('a.publishedAt >= :from')
                    ->setParameter('from', new \DateTime($dateFrom));
            } catch (\Exception $e) {
            }
        }

        // 🔹 Filtre date "À"
        if (!empty($dateTo)) {
            try {
                $qb->andWhere('a.publishedAt <= :to')
                    ->setParameter('to', new \DateTime($dateTo));
            } catch (\Exception $e) {
            }
        }

        // ✅ Compte total avant pagination
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();

        // 🔹 Pagination ensuite
        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $articles = $qb->getQuery()->getResult();

        return [
            'data' => $articles,
            'total' => $total,
        ];
    }
}
