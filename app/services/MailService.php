<?php

declare(strict_types=1);

namespace App\Services;

class MailService
{
    public static function send(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null
    ): void {
        $fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@iot4b.ca';
        $fromName = $_ENV['MAIL_FROM_NAME'] ?? 'World Cup Pool';
        $textBody = $textBody ?? strip_tags($htmlBody);

        if (!empty($_ENV['MAIL_HOST'])) {
            self::sendSmtp($to, $subject, $htmlBody, $textBody, $fromAddress, $fromName);
            return;
        }

        self::sendMail($to, $subject, $htmlBody, $textBody, $fromAddress, $fromName);
    }

    private static function sendMail(
        string $to,
        string $subject,
        string $htmlBody,
        string $textBody,
        string $fromAddress,
        string $fromName
    ): void {
        $boundary = 'wc_' . bin2hex(random_bytes(8));
        $headers = [
            'From: ' . self::formatAddress($fromAddress, $fromName),
            'Reply-To: ' . $fromAddress,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'X-Mailer: WorldCupPool',
        ];

        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $body .= $textBody . "\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $body .= $htmlBody . "\r\n\r\n";
        $body .= "--{$boundary}--";

        $ok = mail(
            $to,
            '=?UTF-8?B?' . base64_encode($subject) . '?=',
            $body,
            implode("\r\n", $headers)
        );

        if (!$ok) {
            throw new \RuntimeException('Unable to send email (mail() failed). Configure SMTP in .env.');
        }
    }

    private static function sendSmtp(
        string $to,
        string $subject,
        string $htmlBody,
        string $textBody,
        string $fromAddress,
        string $fromName
    ): void {
        $host = $_ENV['MAIL_HOST'];
        $port = (int) ($_ENV['MAIL_PORT'] ?? 587);
        $user = $_ENV['MAIL_USERNAME'] ?? '';
        $pass = $_ENV['MAIL_PASSWORD'] ?? '';
        $encryption = strtolower($_ENV['MAIL_ENCRYPTION'] ?? 'tls');

        $remote = $encryption === 'ssl'
            ? "ssl://{$host}:{$port}"
            : "{$host}:{$port}";

        $socket = @stream_socket_client(
            $remote,
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            throw new \RuntimeException("SMTP connection failed: {$errstr}");
        }

        stream_set_timeout($socket, 15);
        self::smtpExpect($socket, [220]);

        $ehloHost = parse_url($_ENV['APP_URL'] ?? 'http://localhost', PHP_URL_HOST) ?: 'localhost';
        self::smtpCommand($socket, "EHLO {$ehloHost}\r\n");
        self::smtpExpect($socket, [250]);

        if ($encryption === 'tls') {
            self::smtpCommand($socket, "STARTTLS\r\n");
            self::smtpExpect($socket, [220]);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            self::smtpCommand($socket, "EHLO {$ehloHost}\r\n");
            self::smtpExpect($socket, [250]);
        }

        if ($user !== '') {
            self::smtpCommand($socket, "AUTH LOGIN\r\n");
            self::smtpExpect($socket, [334]);
            self::smtpCommand($socket, base64_encode($user) . "\r\n");
            self::smtpExpect($socket, [334]);
            self::smtpCommand($socket, base64_encode($pass) . "\r\n");
            self::smtpExpect($socket, [235]);
        }

        self::smtpCommand($socket, 'MAIL FROM:<' . $fromAddress . ">\r\n");
        self::smtpExpect($socket, [250]);
        self::smtpCommand($socket, 'RCPT TO:<' . $to . ">\r\n");
        self::smtpExpect($socket, [250, 251]);
        self::smtpCommand($socket, "DATA\r\n");
        self::smtpExpect($socket, [354]);

        $boundary = 'wc_' . bin2hex(random_bytes(8));
        $message = 'From: ' . self::formatAddress($fromAddress, $fromName) . "\r\n";
        $message .= "To: {$to}\r\n";
        $message .= 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= 'Content-Type: multipart/alternative; boundary="' . $boundary . "\"\r\n\r\n";
        $message .= "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$textBody}\r\n\r\n";
        $message .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$htmlBody}\r\n\r\n";
        $message .= "--{$boundary}--\r\n.\r\n";

        fwrite($socket, $message);
        self::smtpExpect($socket, [250]);
        self::smtpCommand($socket, "QUIT\r\n");
        fclose($socket);
    }

    private static function formatAddress(string $email, string $name): string
    {
        if ($name === '') {
            return $email;
        }

        return '=?UTF-8?B?' . base64_encode($name) . "?= <{$email}>";
    }

    /** @param resource $socket */
    private static function smtpCommand($socket, string $command): void
    {
        fwrite($socket, $command);
    }

    /** @param resource $socket */
    /** @param list<int> $codes */
    private static function smtpExpect($socket, array $codes): void
    {
        $response = '';

        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);

        if (!in_array($code, $codes, true)) {
            throw new \RuntimeException('SMTP error: ' . trim($response));
        }
    }
}
