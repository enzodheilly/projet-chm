<?php

namespace App\Repository;

use App\Entity\NewsletterSubscriber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsletterSubscriber>
 *
 * @method NewsletterSubscriber|null find($id, $lockMode = null, $lockVersion = null)
 * @method NewsletterSubscriber|null findOneBy(array $criteria, array $orderBy = null)
 * @method NewsletterSubscriber[]    findAll()
 * @method NewsletterSubscriber[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NewsletterSubscriberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsletterSubscriber::class);
    }

    public function add(NewsletterSubscriber $subscriber, bool $flush = true): void
    {
        $this->_em->persist($subscriber);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(NewsletterSubscriber $subscriber, bool $flush = true): void
    {
        $this->_em->remove($subscriber);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // Exemple de méthode personnalisée
    public function findByEmail(string $email): ?NewsletterSubscriber
    {
        return $this->findOneBy(['email' => $email]);
    }
}
