<?php
// Local SMTP credentials for Mailtrap. Do NOT commit real passwords.
// Fill in your Mailtrap SMTP password (not the API token).
return [
    'host' => 'sandbox.smtp.mailtrap.io',
    'port' => 2525, // 25, 465, 587 or 2525 (Mailtrap recommends 2525/587)
    'username' => 'b0e3df60376877',
    'password' => 'c4e57784be87fc', // Mailtrap SMTP password (not API token)
    'secure' => 'tls', // STARTTLS on supported ports
    'from' => 'no-reply@cartify.local',
    'from_name' => 'Cartify',
];
