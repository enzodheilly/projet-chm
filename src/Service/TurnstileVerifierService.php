<?php

namespace App\Service;

class TurnstileVerifierService
{
    public function __construct(
        private readonly string $secretKey // 🔒 clé secrète injectée via services.yaml
    ) {}

    /**
     * Vérifie la validité d'un token Turnstile envoyé par le client.
     *
     * @param string|null $token Le token renvoyé par Cloudflare Turnstile
     * @param string|null $ip    (Optionnel) IP de l'utilisateur
     * @return bool              True si validé, False sinon
     */
    public function verify(?string $token, ?string $ip = null): bool
    {
        // 🚫 Aucun token => on refuse immédiatement
        if (empty($token)) {
            return false;
        }

        // Prépare les données à envoyer à l'API Cloudflare
        $postData = http_build_query([
            'secret'   => $this->secretKey,
            'response' => $token,
            'remoteip' => $ip,
        ]);

        // Configure la requête POST HTTP
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => $postData,
                'timeout' => 5,
            ],
        ];

        $context = stream_context_create($options);
        $result  = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);

        // ⚠️ Si échec de connexion à l’API Cloudflare → on refuse par sécurité
        if ($result === false) {
            return false;
        }

        // Analyse de la réponse JSON
        $data = json_decode($result, true);

        // ✅ Retourne true uniquement si success = true
        return !empty($data['success']);
    }
}
