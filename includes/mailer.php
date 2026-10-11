<?php
// includes/mailer.php
// Lightweight mail helper: uses PHPMailer (if installed via composer) or falls
// back to PHP's mail() function. Call send_html_email() to send messages.

function send_html_email($to, $subject, $htmlBody, $textBody = '', $fromEmail = null, $fromName = null) {
    $configPath = __DIR__ . '/../config/mail.php';
    $config = file_exists($configPath) ? include $configPath : [];
    $fromEmail = $fromEmail ?: ($config['from_email'] ?? 'no-reply@localhost');
    $fromName = $fromName ?: ($config['from_name'] ?? 'NALA ERMS');

    // If Composer autoload and PHPMailer are available, prefer SMTP sending
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $config['host'] ?? 'smtp.example.com';
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'] ?? '';
            $mail->Password = $config['password'] ?? '';
            $mail->SMTPSecure = $config['encryption'] ?? 'tls';
            $mail->Port = $config['port'] ?? 587;
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody ?: strip_tags($htmlBody);
            return $mail->send();
        } catch (Exception $e) {
            error_log('PHPMailer error: ' . $e->getMessage());
            // fallthrough to mail() fallback
        }
    }

    // Fallback to PHP mail()
    $headers = "From: {$fromName} <{$fromEmail}>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    return (bool) @mail($to, $subject, $htmlBody, $headers);
}
