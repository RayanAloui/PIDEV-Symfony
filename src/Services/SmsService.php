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
        $sid='AC0d13074ebae3e47408ba30303cfa62e6';
        $authToken='2369387bd3ef807720b865953e784f4a';
        $this->from='+16614622383';

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

