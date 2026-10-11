<?php
// tools/test_email_send.php
// Quick tester for the mail helper. Call in browser or CLI with ?to=you@domain.tld
require_once __DIR__ . '/../includes/mailer.php';

$to = $_GET['to'] ?? ($argv[1] ?? '');
if (empty($to)) {
    echo "Usage: visit this script with ?to=you@domain.tld or run `php tools/test_email_send.php you@domain.tld`\n";
    exit(0);
}

$subject = 'NALA ERMS: test email ' . date('Y-m-d H:i:s');
$html = "<p>This is a test email from NALA ERMS sent at " . date('Y-m-d H:i:s') . "</p>";
$text = strip_tags($html);
$sent = send_html_email($to, $subject, $html, $text);
if ($sent) {
    echo "Email send attempted to {$to} — check inbox/spam.\n";
} else {
    echo "Failed to send email to {$to}. Check mail configuration and logs.\n";
}
