<?php
// worker.php
require 'vendor/autoload.php';
use App\EmailSender;

echo "[" . date('Y-m-d H:i:s') . "] Email worker started...\n";

$config = include 'config.php';
$mailer = new EmailSender($config);

$mailer->processQueue(15);  // send up to 15 emails per run

echo "Worker finished.\n";