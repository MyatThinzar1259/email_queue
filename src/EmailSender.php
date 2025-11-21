<?php

namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

class EmailSender {
    private $config;

    public function __construct(array $config =null){
        if ($config === null) {
            $config = include __DIR__ . '/../config.php';
        }
        $this->config = $config;
    }

    public function sendEmail($to, $subject, $bodyHTML, $body_message = ''){
        $mail = new PHPMailer(true);

        try{
            //Server settings
            $mail->isSMTP();
            $mail->Host       = $this->config['smtp']['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['smtp']['username'];
            $mail->Password   = $this->config['smtp']['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->config['smtp']['port'];
            $mail->SMTPDebug  = $this->config['smtp']['debug'];

            // Recipients
            $mail->setFrom($this->config['smtp']['from'], $this->config['smtp']['from_name']);
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $bodyHTML;
            if (!empty($body_message)) {
                $mail->AltBody = $body_message;
            }

            $mail->send();
            return ['success' => true, 'message' => 'Message has been sent'];
        }catch (Exception $e){
            return ['success' => false, 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"];
        }
    }
}