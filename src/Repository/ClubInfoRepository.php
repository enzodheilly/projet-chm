<?php

namespace App\Repository;

use App\Entity\ClubInfo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClubInfoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClubInfo::class);
    }

    /**
     * Récupère une info par catégorie
     */
    public function findByCategory(string $category): ?ClubInfo
    {
        return $this->createQueryBuilder('c')
            ->andWhere('LOWER(c.category) = :cat')
            ->setParameter('cat', strtolower($category))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
