<?php

// src/Repository/ArticleRepository.php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Categorie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Récupère les articles filtrés par catégorie (nom), date et pagination.
     */
    public function findFilteredArticles(?int $categorieId, ?string $dateFrom, ?string $dateTo, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.categorie', 'c')
            ->addSelect('c')
            ->orderBy('a.publishedAt', 'DESC');

        // 🔹 Filtre par catégorie (par nom, pas ID)
        if ($categorieId) {
            $em = $this->getEntityManager();
            $categorie = $em->getRepository(Categorie::class)->find($categorieId);

            if ($categorie) {
                $qb->andWhere('LOWER(c.name) = LOWER(:catname)')
                    ->setParameter('catname', $categorie->getName());
            }
        }

        // 🔹 Filtre par date "de"
        if (!empty($dateFrom)) {
            $qb->andWhere('a.publishedAt >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom));
        }

        // 🔹 Filtre par date "à"
        if (!empty($dateTo)) {
            $qb->andWhere('a.publishedAt <= :dateTo')
                ->setParameter('dateTo', (new \DateTime($dateTo))->setTime(23, 59, 59));
        }

        // 🔹 Pagination
        $offset = ($page - 1) * $limit;

        // Total avant pagination
        $countQb = clone $qb;
        $total = count($countQb->getQuery()->getResult());

        // Résultats paginés
        $data = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'data' => $data,
            'total' => $total,
        ];
    }
}
