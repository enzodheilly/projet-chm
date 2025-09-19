<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        // Données pour les statistiques
        $stats = [
            ['icon' => 'users', 'label' => 'Adhérents', 'value' => '150+'],
            ['icon' => 'trophy', 'label' => 'Compétitions', 'value' => '20+'],
            ['icon' => 'dumbbell', 'label' => 'Années d\'expérience', 'value' => '25+'],
        ];

        // Valeurs du club
        $values = [
            [
                'icon' => 'trophy',
                'title' => 'Excellence',
                'description' => 'Formation de champions et accompagnement vers les plus hauts niveaux de compétition.'
            ],
            [
                'icon' => 'target',
                'title' => 'Technique',
                'description' => 'Apprentissage rigoureux des mouvements d\'haltérophilie avec un encadrement professionnel.'
            ],
            [
                'icon' => 'heart',
                'title' => 'Passion',
                'description' => 'Un environnement où la passion du sport et le dépassement de soi sont au cœur de notre philosophie.'
            ],
            [
                'icon' => 'users',
                'title' => 'Communauté',
                'description' => 'Une famille de sportifs qui s\'entraident et progressent ensemble dans un esprit d\'équipe.'
            ]
        ];

        // Programmes de l'école
        $programs = [
            [
                'title' => 'École d\'Haltérophilie',
                'age' => '8-16 ans',
                'icon' => 'graduation-cap',
                'description' => 'Initiation et perfectionnement aux techniques d\'haltérophilie pour les jeunes talents.',
                'features' => [
                    'Apprentissage progressif des mouvements',
                    'Développement de la coordination',
                    'Préparation aux compétitions jeunes',
                    'Encadrement par des experts'
                ]
            ],
            [
                'title' => 'Perfectionnement',
                'age' => '16+ ans',
                'icon' => 'award',
                'description' => 'Formation avancée pour les athlètes souhaitant atteindre les plus hauts niveaux.',
                'features' => [
                    'Technique de haut niveau',
                    'Préparation physique spécialisée',
                    'Suivi individualisé',
                    'Compétitions régionales et nationales'
                ]
            ],
            [
                'title' => 'Musculation',
                'age' => 'Tous niveaux',
                'icon' => 'zap',
                'description' => 'Programmes de musculation adaptés à tous les objectifs et tous les niveaux.',
                'features' => [
                    'Programmes personnalisés',
                    'Équipements modernes',
                    'Suivi des progrès',
                    'Ambiance conviviale'
                ]
            ]
        ];

        // Horaires
        $schedules = [
            [
                'day' => 'Lundi',
                'sessions' => [
                    ['time' => '18h00 - 20h00', 'type' => 'École + Adultes', 'level' => 'Tous niveaux']
                ]
            ],
            [
                'day' => 'Mercredi',
                'sessions' => [
                    ['time' => '16h00 - 18h00', 'type' => 'École', 'level' => '8-16 ans'],
                    ['time' => '18h00 - 20h00', 'type' => 'Adultes', 'level' => 'Tous niveaux']
                ]
            ],
            [
                'day' => 'Vendredi',
                'sessions' => [
                    ['time' => '18h00 - 20h00', 'type' => 'École + Adultes', 'level' => 'Tous niveaux']
                ]
            ],
            [
                'day' => 'Samedi',
                'sessions' => [
                    ['time' => '10h00 - 12h00', 'type' => 'Libre', 'level' => 'Tous niveaux']
                ]
            ]
        ];

        // Compétitions
        $competitions = [
            [
                'title' => 'Championnat Régional',
                'date' => '15 Mars 2025',
                'location' => 'Amiens',
                'category' => 'Toutes catégories',
                'status' => 'À venir'
            ],
            [
                'title' => 'Coupe Départementale',
                'date' => '22 Février 2025',
                'location' => 'Saleux',
                'category' => 'Jeunes',
                'status' => 'Inscription ouverte'
            ],
            [
                'title' => 'Championnat de France Masters',
                'date' => '10 Mai 2025',
                'location' => 'Paris',
                'category' => 'Masters',
                'status' => 'Sélection'
            ]
        ];

        // Performances
        $achievements = [
            ['icon' => 'trophy', 'title' => '25+', 'subtitle' => 'Médailles cette saison', 'description' => 'Nos athlètes brillent en compétition'],
            ['icon' => 'medal', 'title' => '12', 'subtitle' => 'Champions régionaux', 'description' => 'Excellence reconnue'],
            ['icon' => 'target', 'title' => '8', 'subtitle' => 'Records battus', 'description' => 'Performance exceptionnelle']
        ];

        // Tarifs
        $pricing = [
            [
                'title' => 'École (8-16 ans)',
                'price' => '120€',
                'period' => '/an',
                'popular' => false,
                'features' => [
                    '3 séances par semaine',
                    'Encadrement spécialisé',
                    'Matériel fourni',
                    'Suivi personnalisé'
                ]
            ],
            [
                'title' => 'Adulte Loisir',
                'price' => '180€',
                'period' => '/an',
                'popular' => true,
                'features' => [
                    'Accès libre aux créneaux',
                    'Programmes personnalisés',
                    'Équipements professionnels',
                    'Ambiance conviviale'
                ]
            ],
            [
                'title' => 'Compétition',
                'price' => '250€',
                'period' => '/an',
                'popular' => false,
                'features' => [
                    'Entraînements spécialisés',
                    'Préparation compétitions',
                    'Suivi technique avancé',
                    'Déplacements compétitions'
                ]
            ]
        ];

        // Informations de contact
        $contactInfo = [
            [
                'icon' => 'map-pin',
                'title' => 'Adresse',
                'details' => [
                    '8 rue Marx Dormoy',
                    '(en face de la mairie)',
                    '80480 Saleux'
                ]
            ],
            [
                'icon' => 'phone',
                'title' => 'Téléphone',
                'details' => ['+33 3 22 95 XX XX']
            ],
            [
                'icon' => 'mail',
                'title' => 'Email',
                'details' => [
                    'contact@chm-saleux.fr',
                    'ecole@chm-saleux.fr'
                ]
            ],
            [
                'icon' => 'clock',
                'title' => 'Secrétariat',
                'details' => [
                    'Lundi : 18h00 - 20h00',
                    'Mercredi : 16h00 - 20h00',
                    'Vendredi : 18h00 - 20h00'
                ]
            ]
        ];

        return $this->render('home/index.html.twig', [
            'stats' => $stats,
            'values' => $values,
            'programs' => $programs,
            'schedules' => $schedules,
            'competitions' => $competitions,
            'achievements' => $achievements,
            'pricing' => $pricing,
            'contactInfo' => $contactInfo,
        ]);
    }

    #[Route('/contact', name: 'contact_form', methods: ['POST'])]
    public function submitContact(): Response
    {
        // Traitement du formulaire de contact
        // Ici vous pouvez ajouter la logique pour envoyer l'email

        $this->addFlash('success', 'Votre message a été envoyé avec succès !');

        return $this->redirectToRoute('home', ['_fragment' => 'contact']);
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('about/index.html.twig');
    }

    #[Route('/legal', name: 'app_legal')]
    public function legal(): Response
    {
        return $this->render('legal/index.html.twig');
    }

    #[Route('/privacy', name: 'app_privacy')]
    public function privacy(): Response
    {
        return $this->render('privacy/index.html.twig');
    }
}
