<?php

namespace App\Repository;

use App\Entity\Forfait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Forfait>
 */
class ForfaitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Forfait::class);
    }

    // 🧠 Tu peux ajouter des méthodes personnalisées ici, par exemple :
    // public function findByCategory(string $category): array
    // {
    //     return $this->createQueryBuilder('f')
    //         ->andWhere('f.categorie = :val')
    //         ->setParameter('val', $category)
    //         ->orderBy('f.prix', 'ASC')
    //         ->getQuery()
    //         ->getResult();
    // }
}
