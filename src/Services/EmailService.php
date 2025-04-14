<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $smtpServer = 'smtp-relay.brevo.com';
    private $smtpPort = 587;
    private $smtpUser = '866fc7002@smtp-brevo.com'; // Identifiant
    private $smtpPassword = 'jA8yf9S0rB4UQgFE'; // cle SMTP
    private $senderEmail = 'orphelincare@gmail.com'; // Email d'expéditeur
    private $senderName = 'OrphenCare';

    public function __construct()
    {
        // You can do any necessary initialization here
    }

    public function sendEmail($recipient, $subject, $content)
    {
        try {
            // Create a new PHPMailer instance
            $mail = new PHPMailer(true);

            // Set mailer to use SMTP
            $mail->isSMTP();
            $mail->Host = $this->smtpServer;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUser;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtpPort;

            // Set the sender's details
            $mail->setFrom($this->senderEmail, $this->senderName);

            // Add a recipient
            $mail->addAddress($recipient);

            // Set email format to HTML
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $content;

            // Send the email
            $mail->send();
            echo 'Email sent successfully to ' . $recipient;
        } catch (Exception $e) {
            echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}
