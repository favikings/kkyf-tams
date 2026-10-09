<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

/**
 * Sends application mail through the configured transport.
 *
 * The log transport is deliberately restricted to APP_DEBUG=true. Production
 * uses authenticated SMTP through PHPMailer; pages never call either transport
 * directly.
 */
function sendMail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $textBody
): void {
    $transport = strtolower(trim((string) env('MAIL_TRANSPORT', '')));

    if ($transport === 'log') {
        sendMailToDebugLog($toEmail, $toName, $subject, $htmlBody, $textBody);

        return;
    }

    if ($transport === 'smtp') {
        sendMailWithSmtp($toEmail, $toName, $subject, $htmlBody, $textBody);

        return;
    }

    throw new RuntimeException('MAIL_TRANSPORT must be set to log or smtp.');
}

function sendMailToDebugLog(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $textBody
): void {
    $debugEnabled = in_array(
        strtolower((string) env('APP_DEBUG', '')),
        ['1', 'true', 'yes', 'on'],
        true
    );

    if (!$debugEnabled) {
        throw new RuntimeException('The log mail transport is disabled outside debug mode.');
    }

    $logDirectory = dirname(__DIR__) . '/storage/logs';
    if (!is_dir($logDirectory) && !mkdir($logDirectory, 0700, true) && !is_dir($logDirectory)) {
        throw new RuntimeException('Could not create the protected mail log directory.');
    }

    $entry = json_encode([
        'sent_at' => date(DATE_ATOM),
        'to_email' => $toEmail,
        'to_name' => $toName,
        'subject' => $subject,
        'html_body' => $htmlBody,
        'text_body' => $textBody,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($entry === false || file_put_contents(
        $logDirectory . '/mail.log',
        $entry . PHP_EOL,
        FILE_APPEND | LOCK_EX
    ) === false) {
        throw new RuntimeException('Could not write the protected mail log.');
    }
}

function sendMailWithSmtp(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $textBody
): void {
    $autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (!is_file($autoloadPath)) {
        throw new RuntimeException('SMTP dependencies are not installed.');
    }

    require_once $autoloadPath;

    $host = trim((string) env('SMTP_HOST', ''));
    $username = trim((string) env('SMTP_USERNAME', ''));
    $password = (string) env('SMTP_PASSWORD', '');
    $fromAddress = trim((string) env('MAIL_FROM_ADDRESS', ''));
    $fromName = trim((string) env('MAIL_FROM_NAME', APP_NAME));
    $encryption = strtolower(trim((string) env('SMTP_ENCRYPTION', 'tls')));
    $port = filter_var(env('SMTP_PORT', '587'), FILTER_VALIDATE_INT);

    if (
        $host === ''
        || $username === ''
        || $password === ''
        || !filter_var($fromAddress, FILTER_VALIDATE_EMAIL)
        || $port === false
        || $port < 1
        || $port > 65535
        || !in_array($encryption, ['tls', 'ssl'], true)
    ) {
        throw new RuntimeException('SMTP configuration is incomplete or invalid.');
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $host;
    $mail->Port = (int) $port;
    $mail->SMTPAuth = true;
    $mail->Username = $username;
    $mail->Password = $password;
    $mail->SMTPSecure = $encryption === 'ssl'
        ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet = PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
    $mail->Timeout = 15;
    $mail->setFrom($fromAddress, $fromName);
    $mail->addAddress($toEmail, $toName);
    $mail->Subject = $subject;
    $mail->isHTML(true);
    $mail->Body = $htmlBody;
    $mail->AltBody = $textBody;
    $mail->send();
}
