<?php
namespace App\Services;
use Twilio\Rest\Client;
use Psr\Log\LoggerInterface;
class sms
{
    private Client $twilio;
    private string $from;
    private LoggerInterface $logger;
    public function __construct(LoggerInterface $logger)
    {
      
        $sid = 'ACfbbe8419fc3056427eaf1e073f2702cd';
        $authToken = 'd5bc25613b2d0dcd898ab393861c8b9c';
        $this->from = '+19515400968';

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

