<?php
// src/DataFixtures/ArticleFixtures.php
namespace App\DataFixtures;

use App\Entity\Article;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ArticleFixtures extends Fixture
{
    // Ajout du ": void" ici
    public function load(ObjectManager $manager): void
    {
        $articles = [
            [
                'title' => 'Retour sur le Conseil des Ligues du 20 septembre',
                'summary' => 'Le samedi 20 septembre s’est déroulée la nouvelle édition du Conseil des Ligues...',
                'image' => 'images/article-13.png',
                'publishedAt' => new \DateTime('2025-09-26'),
            ],
            [
                'title' => 'Octobre Rose 2025 – Haltér’Ose',
                'summary' => 'À l’occasion d’Octobre Rose 2025...',
                'image' => 'images/article-14.png',
                'publishedAt' => new \DateTime('2025-09-26'),
            ],
            [
                'title' => 'Championnats du Monde 2025 : découvrez le programme',
                'summary' => 'A quelques jours du début des Championnats du Monde Seniors 2025...',
                'image' => 'images/article-15.png',
                'publishedAt' => new \DateTime('2025-09-25'),
            ],
            [
                'title' => 'Collectif U18 : convocation pour le stage national',
                'summary' => 'Dans cadre du stage national U18 de la FFHM...',
                'image' => 'images/article-17.png',
                'publishedAt' => new \DateTime('2025-09-24'),
            ],
        ];

        foreach ($articles as $data) {
            $article = new Article();
            $article->setTitle($data['title'])
                ->setSummary($data['summary'])
                ->setImage($data['image'])
                ->setPublishedAt($data['publishedAt']);
            $manager->persist($article);
        }

        $manager->flush();
    }
}
