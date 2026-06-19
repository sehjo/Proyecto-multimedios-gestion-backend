<?php

class Mailer
{
    public static function sendPasswordReset(string $toEmail, string $resetUrl): void
    {
        $subject = 'Reestablecer contraseña';
        $html = self::passwordResetTemplate($resetUrl);

        self::send($toEmail, $subject, $html);
    }

    private static function send(string $toEmail, string $subject, string $html): void
    {
        $driver = config('mail.mailer', 'log');

        if ($driver === 'log') {
            self::logMail($toEmail, $subject, $html);

            return;
        }

        $fromAddress = config('mail.from_address');
        $fromName = config('mail.from_name');

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$fromAddress}>\r\n";

        mail($toEmail, $subject, $html, $headers);
    }

    private static function logMail(string $toEmail, string $subject, string $html): void
    {
        $logPath = config('mail.log_path');
        $dir = dirname($logPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $entry = sprintf(
            "[%s] To: %s | Subject: %s\n%s\n\n",
            date('Y-m-d H:i:s'),
            $toEmail,
            $subject,
            $html
        );

        file_put_contents($logPath, $entry, FILE_APPEND);
    }

    private static function passwordResetTemplate(string $resetUrl): string
    {
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <body style="font-family: Arial, sans-serif;">
            <p>Hola,</p>
            <p>Recibimos una solicitud para restablecer tu contraseña. Haz clic en el siguiente enlace para continuar:</p>
            <p><a href="{$safeUrl}">{$safeUrl}</a></p>
            <p>Si no solicitaste este cambio, puedes ignorar este correo.</p>
        </body>
        </html>
        HTML;
    }
}
