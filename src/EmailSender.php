<?php

namespace App;

use PDO;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

class EmailSender {
    private $config;
    private $pdo;

    public function __construct(array $config =null){
        if ($config === null) {
            $config = include __DIR__ . '/../config.php';
        }
        $this->config = $config;

        $dbConfig = $this->config['database'];
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";

        $this->pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    public function queue(string $to, string $subject, string $bodyHTML, string $bodyText = ''): array
    {
        try {
            $sql = "INSERT INTO email_queue (to_email, subject, body_html, body_text) 
                    VALUES (:to, :subject, :body_html, :body_text)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':to'        => $to,
                ':subject'   => $subject,
                ':body_html' => $bodyHTML,
                ':body_text' => $bodyText ?: null
            ]);

            return ['success' => true, 'message' => 'Email queued successfully! Will be sent shortly.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function processQueue(int $batch = 15): void
    {
        $sql = "SELECT * FROM email_queue 
                WHERE (status = 'pending' OR (status = 'failed' AND attempts < 5))
                ORDER BY id ASC 
                LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $batch, PDO::PARAM_INT);
        $stmt->execute();

        while ($email = $stmt->fetch()) {
            $success = $this->sendOne($email);

            if ($success) {
                $this->markAsSent($email['id']);
            } else {
                $this->markAsFailed($email['id'], $this->lastError);
            }

            //  Gmail limits: ~100-150 emails/hour safe
            // sleep(rand(4, 8));  // 4-8 seconds between emails
        }
    }

    private string $lastError = '';

    private function sendOne(array $email): bool
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $this->config['smtp']['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['smtp']['username'];
            $mail->Password   = $this->config['smtp']['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->config['smtp']['port'];

            $mail->setFrom($this->config['smtp']['from'], $this->config['smtp']['from_name'] ?? 'Dream HR');
            $mail->addAddress($email['to_email']);
            $mail->isHTML(true);
            $mail->Subject = $email['subject'];
            $mail->Body    = $email['body_html'];
            $mail->AltBody = $email['body_text'] ?? strip_tags($email['body_html']);

            $mail->send();
            return true;
        } catch (Exception $e) {
            $this->lastError = $mail->ErrorInfo ?? $e->getMessage();
            return false;
        }
    }

    private function markAsSent(int $id): void
    {
        $sql = "UPDATE email_queue SET status = 'sent', sent_at = NOW(), attempts = attempts + 1 WHERE id = :id";
        $this->pdo->prepare($sql)->execute([':id' => $id]);
    }

    private function markAsFailed(int $id, string $error): void
    {
        $sql = "UPDATE email_queue SET 
                status = 'failed', 
                attempts = attempts + 1, 
                error = :error 
                WHERE id = :id";
        $this->pdo->prepare($sql)->execute([':id' => $id, ':error' => substr($error, 0, 1000)]);
    }
}