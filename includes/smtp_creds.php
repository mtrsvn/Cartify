<?php
// Local SMTP credentials for Mailtrap. Do NOT commit real passwords.
// Fill in your Mailtrap SMTP password (not the API token).
return [
    'host' => 'sandbox.smtp.mailtrap.io',
    'port' => 2525,
    'username' => '68931922db36ab',
    'password' => 'bc24d8c02beb7e', // Mailtrap SMTP password (not API token)
    'secure' => '', // '' for port 2525; use 'tls' with port 587
    'from' => 'no-reply@scp.local',
    'from_name' => 'SCP',
];
