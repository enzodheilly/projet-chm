<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 *
 * @method Payment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payment[]    findAll()
 * @method Payment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * Sauvegarde ou met à jour un paiement.
     */
    public function save(Payment $payment, bool $flush = false): void
    {
        $this->_em->persist($payment);

        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Supprime un paiement.
     */
    public function remove(Payment $payment, bool $flush = false): void
    {
        $this->_em->remove($payment);

        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Exemple : récupérer tous les paiements d’un utilisateur donné.
     */
    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $userId)
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Exemple : récupérer les paiements validés.
     */
    public function findValidatedPayments(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'Validé')
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
