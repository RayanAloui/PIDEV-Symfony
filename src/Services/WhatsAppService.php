<?php

namespace App\Services;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WhatsAppService
{
    private HttpClientInterface $client;
    private string $apiKey;
    private string $phone;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->apiKey = '4323731'; // ⚠️ Mets ici ta vraie clé CallMeBot
        $this->phone = '+21629226335'; // ⚠️ Ton numéro WhatsApp autorisé
    }

    public function sendConfirmationMessage(string $message): void
    {
        $this->client->request('GET', 'https://api.callmebot.com/whatsapp.php', [
            'query' => [
                'phone' => $this->phone,
                'text' => $message,
                'apikey' => $this->apiKey,
            ]
        ]);
    }
}
