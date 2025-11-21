<?php
session_start();
require '../vendor/autoload.php';

use App\EmailSender;
$config = include __DIR__ . '/../config.php';

$status = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = filter_var($_POST['to'], FILTER_VALIDATE_EMAIL);
    $subject = trim($_POST['subject'] ?? '');
    $body_message = trim($_POST['body_message'] ?? '');
    
    if (!$to || !$subject || !$body_message){
        $message = 'Please fill in all fields.';
        $status  = 'error';
        goto show_form;
    }

    $ip = $_SERVER['REMOTE_ADDR'];
    $_SESSION['last_sent'][$ip] ??= 0;
    if (time() - $_SESSION['last_sent'][$ip] < 60) {
        $message = 'Please wait 60 seconds before sending another email.';
        $status  = 'error';
        goto show_form;
    }

    $mailer = new EmailSender();
    $result = $mailer->sendEmail(
        to: $to,
        subject: $subject,
        bodyHTML: nl2br(htmlspecialchars($body_message)),
        body_message: $body_message
    );
    $message = $result['message'];
    $status  = $result['success'] ? 'success' : 'error';
    if ($result['success']) {
        $_SESSION['last_sent'][$ip] = time();
    }

}
show_form:
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>Email Sender - PHPMailer</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {font-family: Tahoma, Arial; max-width: 600px; margin: 40px auto; padding: 20px; background:#f9f9f9;}
    h2 {color: #2c3e50;}
    input, textarea, button {width: 100%; padding: 12px; margin: 8px 0; font-size: 1em; border: 1px solid #ccc; border-radius: 4px;}
    button {background: #27ae60; color: white; font-weight: bold; cursor: pointer;}
    button:hover {background: #1e8449;}
    .success {background:#d5f5e3; color:#27ae60; padding:10px; border-radius:4px;}
    .error   {background:#fadbd8; color:#c0392b; padding:10px; border-radius:4px;}
  </style>
</head>
<body>

<h2>Send Email with PHPMailer</h2>

<?php if ($message): ?>
  <div class="<?= $status ?>"><strong><?= htmlspecialchars($message) ?></strong></div>
<?php endif; ?>

<form method="post">
  <label>To:</label>
  <input type="email" name="to" required placeholder="example@gmail.com">

  <label>Subject:</label>
  <input type="text" name="subject" required placeholder="Notification / News">

  <label>Message:</label>
  <textarea name="body_message" required rows="6" placeholder="Type your message here..."></textarea>

  <button type="submit">Send Email</button>
</form>

</body>
</html>