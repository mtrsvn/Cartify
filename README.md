# Cartify (SCP)

Email/OTP setup
- Dev mode: Set `MAIL_DEV_MODE=1` to log OTPs to `otp_logs.txt` and allow OTP flow without sending emails. Useful on localhost/XAMPP where `mail()` is disabled.
	- Windows (XAMPP): add `SetEnv MAIL_DEV_MODE 1` under your vhost in `apache\conf\extra\httpd-vhosts.conf`, then restart Apache.
	- Or set a user/system environment variable `MAIL_DEV_MODE=1` and restart Apache.
- SMTP config: Edit `includes/smtp_creds.php` with your SMTP provider settings.
	- Mailtrap example is included. Replace `username`/`password` with your inbox credentials.
	- Gmail: use an App Password, `host=smtp.gmail.com`, `port=587`, `secure='tls'`, `username=your@gmail.com`, `password=<app-password>`.
	- Outlook/Office365: `host=smtp.office365.com`, `port=587`, `secure='tls'`.

Troubleshooting
- “SMTP Error: Could not authenticate”: credentials or host/port/secure mismatch. Verify `includes/smtp_creds.php`.
- If all sending fails, OTPs are still logged to `otp_logs.txt` and on localhost the flow continues so you can test.
