<?php

namespace App\Repository;

use App\Entity\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ZoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Zone::class);
    }

    // Tu peux ajouter tes méthodes personnalisées ici
    // Exemple : trouver toutes les zones par type
    /*
    public function findByType(string $type)
    {
        return $this->createQueryBuilder('z')
            ->andWhere('z.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }
    */
}
