<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EdenAiService
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, ParameterBagInterface $params)
    {
        $this->client = $client;
        $this->apiKey = $params->get('edenai_api_key'); // API key définie dans .env
    }

    public function summarizeText(string $text): ?string
    {
        $response = $this->client->request('POST', 'https://api.edenai.run/v2/text/summarize', [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'providers' => 'openai',
                'text' => "Résume ce texte en français :\n\n" . $text, // On précise que c’est un résumé
                'language' => 'fr',
                'length' => 'short',
            ],
        ]);

        $data = $response->toArray();

        return $data['openai']['result'] ?? null;
    }

    /*public function translateToEnglish(string $text): string
    {
        $response = $this->client->request('POST', 'https://api.edenai.run/v2/text/summarize', [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'providers' => 'openai',
                'text' => $text,
                'language' => 'en',
                'length' => 'short',
            ],
        ]);

        $data = $response->toArray();

        return $data['openai']['result'] ?? 'Erreur de traduction';
    }*/

    public function translateToEnglish(string $text): string
    {
        try {
            $response = $this->client->request('POST', 'https://api.edenai.run/v2/translation/automatic_translation', [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'providers' => 'openai',
                    'text' => $text,
                    'source_language' => 'fr', 
                    'target_language' => 'en', 
                ],
            ]);

            $data = $response->toArray();

            return $data['openai']['text'] ?? 'Erreur de traduction';
        } catch (\Exception $e) {
            return 'Erreur de traduction';
        }
    }


    public function extractKeywords(string $text): array
    {
        $response = $this->client->request('POST', 'https://api.edenai.run/v2/text/keyword_extraction', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'providers' => 'openai',
                'language' => 'fr',
                'text' => $text,
            ],
        ]);

        $data = $response->toArray();

        $keywords = [];
        foreach ($data['openai']['items'] ?? [] as $item) {
            $keywords[] = $item['keyword'];
        }

        return $keywords;
    }

    public function synthesize(string $text): ?string
    {
        try {
            $response = $this->client->request('POST', 'https://api.edenai.run/v2/audio/text_to_speech', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'providers' => 'openai',
                    'language' => 'fr',
                    'option' => 'FEMALE',
                    'text' => $text,
                ],
            ]);

            // Utilise toArray() pour récupérer la réponse JSON
            $data = $response->toArray();

            return $data['openai']['audio_resource_url'] ?? null;
        } catch (\Exception $e) {
            // Tu peux logger ici si tu veux
            return null;
        }
    }

    public function askChatbot(string $question): ?string
    {
        try {
            $response = $this->client->request('POST', 'https://api.edenai.run/v2/text/chat', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'providers' => 'openai',
                    'text' => $question,
                    'chatbot_global_action' => "Réponds de manière simple, pédagogique et encourageante à un enfant.",
                    'temperature' => 0.3,
                ],
            ]);

            $data = $response->toArray();

            return $data['openai']['generated_text'] ?? null;
        } catch (\Exception $e) {
            return "Désolé, je n'ai pas compris. Peux-tu reformuler ?";
        }
    }
}
