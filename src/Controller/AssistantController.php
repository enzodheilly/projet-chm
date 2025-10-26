<?php

namespace App\Controller;

use App\Entity\ClubInfo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AssistantController extends AbstractController
{
    private HttpClientInterface $client;
    private EntityManagerInterface $em;

    public function __construct(HttpClientInterface $client, EntityManagerInterface $em)
    {
        $this->client = $client;
        $this->em = $em;
    }

    #[Route('/assistant/chat', name: 'assistant_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $data = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        if (!$message) {
            return $this->json(['reply' => "Je n’ai pas compris 😅"]);
        }

        $history = $session->get('elios_history', []);

        // 🧩 Vérification si une info correspond dans la BDD
        $reply = $this->getInfoFromDatabase($message);
        if ($reply) {
            $history[] = ['role' => 'user', 'content' => $message];
            $history[] = ['role' => 'assistant', 'content' => $reply];
            $session->set('elios_history', array_slice($history, -10));

            return $this->json(['reply' => $reply]);
        }

        // 💬 Sinon, on appelle l’IA
        $apiUrl = $_ENV['DEEPSEEK_API_URL'] ?? 'http://localhost:11434/api/chat';
        $model = $_ENV['DEEPSEEK_MODEL'] ?? 'deepseek-r1:8b';

        $messages = array_merge(
            [['role' => 'system', 'content' => "Tu es Elios, assistant virtuel du club d’haltérophilie et musculation. Réponds en français, de façon claire et amicale."]],
            $history,
            [['role' => 'user', 'content' => $message]]
        );

        try {
            $response = $this->client->request('POST', $apiUrl, [
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'stream' => false,
                ],
            ]);

            $data = $response->toArray(false);
            $reply = $data['message']['content']
                ?? ($data['messages'][0]['content'] ?? "Je n’ai pas compris 😅");

            $history[] = ['role' => 'user', 'content' => $message];
            $history[] = ['role' => 'assistant', 'content' => $reply];
            $session->set('elios_history', array_slice($history, -10));

            return $this->json(['reply' => $reply]);
        } catch (\Throwable $e) {
            return $this->json([
                'reply' => "🤖 Elios n’est pas disponible pour le moment (serveur IA éteint). Il reviendra bientôt !",
            ]);
        }
    }

    /**
     * 🔍 Recherche dans la base une info correspondant au message utilisateur
     */
    private function getInfoFromDatabase(string $message): ?string
    {
        $categories = [
            'horaires' => ['horaire', 'ouvert', 'heures'],
            'tarifs'   => ['tarif', 'prix', 'abonnement'],
            'contact'  => ['contact', 'téléphone', 'mail', 'email'],
            'adresse'  => ['adresse', 'où se trouve', 'localisation'],
            'coach'    => ['coach', 'entraîneur'],
        ];

        foreach ($categories as $cat => $keywords) {
            foreach ($keywords as $word) {
                if (str_contains(mb_strtolower($message), $word)) {
                    $info = $this->em->getRepository(ClubInfo::class)->findOneBy(['category' => $cat]);
                    return $info ? $info->getContent() : null;
                }
            }
        }

        return null;
    }

    #[Route('/assistant/categories', name: 'assistant_categories', methods: ['GET'])]
    public function getCategories(EntityManagerInterface $em): JsonResponse
    {
        $categories = $em->getRepository(\App\Entity\ClubInfo::class)->findAll();

        $data = [];
        foreach ($categories as $cat) {
            $data[] = [
                'category' => $cat->getCategory(),
                'label' => ucfirst($cat->getCategory()),
            ];
        }

        return $this->json($data);
    }
}
