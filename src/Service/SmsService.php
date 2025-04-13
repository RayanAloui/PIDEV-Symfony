<?php

namespace App\Service;

use Twilio\Rest\Client;
use Psr\Log\LoggerInterface;

class SmsService
{
    private Client $twilio;
    private string $from;
    private LoggerInterface $logger;

    /*public function __construct(LoggerInterface $logger)
    {
        // ⚠️ Mets ici tes propres credentials Twilio
        $sid = 'AC5e18bbd7fb8f38dc387e6cda806f64a7';
        $authToken = 'd217b002e3e739b8785dc6fa3b692b76';
        $this->from = '+17633015430';

        $this->twilio = new Client($sid, $authToken);
        $this->logger = $logger;
    }*/

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

