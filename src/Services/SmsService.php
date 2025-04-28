<?php

namespace App\Services;

use Twilio\Rest\Client;
use Psr\Log\LoggerInterface;

class SmsService
{
    private Client $twilio;
    private string $from;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        // ⚠️ Mets ici tes propres credentials Twilio
        $sid='AC27d8b84a832fd8912d7b2adffaccaac8';
        $authToken='63bef66e28f5d5fa8c5deb76d335c3be';
        $this->from='+13526767081';

        $this->twilio = new Client($sid, $authToken);
        $this->logger = $logger;
    }

    public function sendSms(string $to, string $message)
    {
        try {
            $this->twilio->messages->create($to, [
                'from' => $this->from,
                'body' => $message
            ]);
            $this->logger->info("✅ SMS envoyé avec succès à $to.");
        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur d'envoi SMS : " . $e->getMessage());
        }
    }
}

