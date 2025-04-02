<?php

namespace App\Service;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class MailerService
{
    private $mailer;
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $dsn = 'smtp://ahmedaloui577@gmail.com:ppasvpkotpiipwmd@smtp.gmail.com:587'; 
        $transport = Transport::fromDsn($dsn);
        $this->mailer = new Mailer($transport);
        $this->logger = $logger;
    }

    public function sendAvailabilityChangeEmail(string $recipientEmail, string $tuteurName, string $newStatus)
    {
        $email = (new Email())
            ->from('ahmedaloui577@gmail.com')
            ->to($recipientEmail)
            ->subject('Changement de disponibilité')
            ->html("
                <h2>Bonjour $tuteurName,</h2>
                <p>Votre disponibilité a été mise à jour : <strong>$newStatus</strong>.</p>
                <p>Merci de vérifier vos informations.</p>
                <p>Cordialement,</p>
                <p>L'équipe de gestion</p>
            ");

        try {
            $this->mailer->send($email);
            $this->logger->info("✅ E-mail envoyé avec succès à $recipientEmail.");
        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur lors de l'envoi de l'e-mail : " . $e->getMessage());
        }
    }
}
