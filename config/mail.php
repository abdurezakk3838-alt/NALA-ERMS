<?php
// config/mail.php
// SMTP configuration for sending emails. Update with your SMTP provider details.
return [
    // Hostname of SMTP server (e.g., smtp.gmail.com)
    'host' => 'smtp.example.com',
    // SMTP port (587 for TLS, 465 for SSL)
    'port' => 587,
    // encryption: 'tls' or 'ssl' or empty for none
    'encryption' => 'tls',
    // SMTP authentication username and password
    'username' => 'your-smtp-username',
    'password' => 'your-smtp-password',
    // From address & name used in outgoing messages
    'from_email' => 'no-reply@yourdomain.tld',
    'from_name' => 'NALA ERMS',
];
