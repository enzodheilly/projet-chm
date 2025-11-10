<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Categorie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class ArticleFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $sport = new Categorie();
        $sport->setName('Sport');

        $evenement = new Categorie();
        $evenement->setName('Événement');

        $formation = new Categorie();
        $formation->setName('Formation');

        $manager->persist($sport);
        $manager->persist($evenement);
        $manager->persist($formation);

        $articles = [
            [
                'title' => 'Retour sur le Conseil des Ligues du 20 septembre',
                'summary' => 'Le samedi 20 septembre s’est déroulée la nouvelle édition du Conseil des Ligues...',
                'image' => 'images/articles/article-13.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $evenement,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Championnats du Monde 2025 : découvrez le programme',
                'summary' => 'À quelques jours du début des Championnats du Monde Seniors 2025...',
                'image' => 'images/articles/article-15.png',
                'publishedAt' => new \DateTime('2025-09-25'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Collectif U18 : convocation pour le stage national',
                'summary' => 'Dans le cadre du stage national U18 de la FFHM...',
                'image' => 'images/articles/article-17.png',
                'publishedAt' => new \DateTime('2025-09-24'),
                'categorie' => $formation,
            ],
            [
                'title' => 'Collectif U18 : convocation pour le stage national',
                'summary' => 'Dans le cadre du stage national U18 de la FFHM...',
                'image' => 'images/articles/article-17.png',
                'publishedAt' => new \DateTime('2025-09-24'),
                'categorie' => $formation,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/articles/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
                'categorie' => $sport,
            ],
        ];

        foreach ($articles as $data) {
            $article = new Article();
            $article->setTitle($data['title'])
                ->setSummary($data['summary'])
                ->setImage($data['image'])
                ->setPublishedAt($data['publishedAt'])
                ->setCategorie($data['categorie']);
            $manager->persist($article);
        }

        $manager->flush();
    }

    /**
     * ✅ Groupe pour exécuter uniquement ces fixtures
     */
    public static function getGroups(): array
    {
        return ['articles'];
    }
}
