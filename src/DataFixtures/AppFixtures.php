<?php

namespace App\DataFixtures;

use App\Entity\ClubInfo;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $infos = [
            [
                'category' => 'horaires',
                'content' => "🏋️ Le club est ouvert du lundi au samedi, de 9h à 21h, et fermé le dimanche."
            ],
            [
                'category' => 'tarifs',
                'content' => "💸 Nos abonnements commencent à 25€/mois sans engagement, ou 50€ la carte 10 entrées."
            ],
            [
                'category' => 'contact',
                'content' => "📞 Vous pouvez nous joindre à contact@votreclub.fr ou au 01 23 45 67 89."
            ],
            [
                'category' => 'adresse',
                'content' => "📍 Le club se situe à Cléry-sous-Choisel, près de la mairie. Parking gratuit."
            ],
            [
                'category' => 'coach',
                'content' => "💪 Nos coachs certifiés sont disponibles du lundi au samedi pour vous accompagner."
            ],
            [
                'category' => 'license',
                'content' => "🎟️ Retrouve ton numéro de licence en toute sécurité. Elios te guidera étape par étape pour confirmer ton identité."
            ],
        ];

        foreach ($infos as $i) {
            $info = new ClubInfo();
            $info->setCategory($i['category']);
            $info->setContent($i['content']);
            $manager->persist($info);
        }

        $manager->flush();
    }
}
