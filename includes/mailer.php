<?php
function send_otp_email(string $toEmail, string $toName, string $otp): array {
    $devMode = getenv('MAIL_DEV_MODE') ?: false;
    
    if ($devMode) {
        $logFile = __DIR__ . '/../otp_logs.txt';
        $logEntry = sprintf(
            "[%s] Email: %s | Name: %s | OTP: %s\n",
            date('Y-m-d H:i:s'),
            $toEmail,
            $toName,
            $otp
        );
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        return ['success' => true, 'error' => null, 'dev_mode' => true];
    }
    
    $autoload = __DIR__ . '/../vendor/autoload.php';
    $usePHPMailer = false;
    $phpmailerError = null;
    
    if (file_exists($autoload)) {
        require_once $autoload;
        $usePHPMailer = true;
    } else {
        $base = __DIR__ . '/PHPMailer/src';
        if (file_exists($base.'/PHPMailer.php') && file_exists($base.'/SMTP.php') && file_exists($base.'/Exception.php')) {
            require_once $base.'/PHPMailer.php';
            require_once $base.'/SMTP.php';
            require_once $base.'/Exception.php';
            $usePHPMailer = true;
        }
    }

    if ($usePHPMailer) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            $host   = getenv('SMTP_HOST') ?: 'sandbox.smtp.mailtrap.io';
            $user   = getenv('SMTP_USER') ?: (getenv('MAILTRAP_USER') ?: '');
            $pass   = getenv('SMTP_PASS') ?: (getenv('MAILTRAP_PASS') ?: '');
            $port   = (int)(getenv('SMTP_PORT') ?: 2525);
            $secure = getenv('SMTP_SECURE') ?: '';
            $from   = getenv('SMTP_FROM') ?: 'no-reply@scp.local';
            $fromName = getenv('SMTP_FROM_NAME') ?: 'SCP';

            $fileCfgPath = __DIR__ . '/smtp_creds.php';
            if (file_exists($fileCfgPath)) {
                $cfg = include $fileCfgPath;
                if (is_array($cfg)) {
                    $host = $cfg['host'] ?? $host;
                    $port = isset($cfg['port']) ? (int)$cfg['port'] : $port;
                    $user = $cfg['username'] ?? $user;
                    $pass = $cfg['password'] ?? $pass;
                    $secure = $cfg['secure'] ?? $secure;
                    $from = $cfg['from'] ?? $from;
                    $fromName = $cfg['from_name'] ?? $fromName;
                }
            }

            $configIssues = [];
            if (!$host) { $configIssues[] = 'host missing'; }
            if (!$user) { $configIssues[] = 'username missing'; }
            if (!$pass) { $configIssues[] = 'password missing'; }
            if (is_string($pass) && stripos($pass, 'REPLACE_WITH') !== false) {
                $configIssues[] = 'placeholder password not replaced';
            }
            if (!empty($configIssues)) {
                $msg = sprintf('[%s] SMTP config error: %s (host=%s, port=%d, user=%s)\n',
                    date('Y-m-d H:i:s'),
                    implode(', ', $configIssues),
                    (string)$host,
                    (int)$port,
                    (string)$user
                );
                @file_put_contents(__DIR__ . '/../smtp_errors.log', $msg, FILE_APPEND);
                throw new \Exception('SMTP configuration invalid: ' . implode(', ', $configIssues));
            }

            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;
            $mail->SMTPAuth = true;
            $mail->SMTPAutoTLS = false;
            $mail->AuthType = 'LOGIN';
            $mail->Username = $user;
            $mail->Password = $pass;
            $mail->SMTPSecure = $secure;

            $mail->setFrom($from, $fromName);
            $mail->addAddress($toEmail, $toName ?: $toEmail);

            $mail->isHTML(true);
            $mail->Subject = 'Your OTP Code - SCP';
            $mail->Body    = "<p>Hello <strong>{$toName}</strong>,</p><p>Your verification code is: <strong style='font-size:1.5em;'>{$otp}</strong></p><p>This code will expire in 10 minutes.</p><p>If you did not request this code, please ignore this email.</p>";
            $mail->AltBody = "Hello {$toName},\n\nYour verification code is: {$otp}\n\nThis code will expire in 10 minutes.";

            $mail->send();
            return ['success' => true, 'error' => null];
        } catch (\Exception $e) {
            $phpmailerError = $e->getMessage();
            $errLine = sprintf("[%s] PHPMailer error: %s\n", date('Y-m-d H:i:s'), $phpmailerError);
            @file_put_contents(__DIR__ . '/../smtp_errors.log', $errLine, FILE_APPEND);
        }
    }
    
    $to = $toEmail;
    $subject = 'Your OTP Code - SCP';
    $message = "Hello {$toName},\n\n";
    $message .= "Your verification code is: {$otp}\n\n";
    $message .= "This code will expire in 10 minutes.\n\n";
    $message .= "If you did not request this code, please ignore this email.\n\n";
    $message .= "Best regards,\nSCP Team";
    
    $headers = "From: SCP <no-reply@scp.local>\r\n";
    $headers .= "Reply-To: no-reply@scp.local\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    if (@mail($to, $subject, $message, $headers)) {
        return ['success' => true, 'error' => null];
    }
    
    $errorMsg = 'Email could not be sent. ';
    if ($phpmailerError) {
        $errorMsg .= 'PHPMailer error: ' . $phpmailerError . ' | ';
    }
    $errorMsg .= 'PHP mail() also failed. Please configure SMTP or enable development mode.';
    
    $logFile = __DIR__ . '/../otp_logs.txt';
    $logEntry = sprintf("[%s] Fallback OTP (send failed). Email: %s | Name: %s | OTP: %s\n",
        date('Y-m-d H:i:s'), $toEmail, $toName, $otp);
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    return ['success' => false, 'error' => $errorMsg];
}
