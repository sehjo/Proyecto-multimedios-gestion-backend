<?php

require_once __DIR__ . '/env.php';

class Mailer
{
    private string $host;
    private int    $port;
    private string $user;
    private string $pass;
    private string $from;
    private string $fromName;

    public function __construct()
    {
        $this->host     = Env::get('MAIL_HOST',         'smtp.gmail.com');
        $this->port     = (int) Env::get('MAIL_PORT',   '587');
        $this->user     = Env::get('MAIL_USERNAME',     '');
        $this->pass     = Env::get('MAIL_PASSWORD',     '');
        $this->from     = Env::get('MAIL_FROM_ADDRESS', $this->user);
        $this->fromName = Env::get('MAIL_FROM_NAME',    'API');
    }

    /**
     * Sends an email. Throws RuntimeException on failure.
     *
     * @param string $to      Recipient
     * @param string $subject Subject line
     * @param string $body    HTML body
     */
    public function send(string $to, string $subject, string $body): void
    {
        $sock = fsockopen($this->host, $this->port, $errno, $errstr, 10);
        if (!$sock) throw new RuntimeException("SMTP connect failed: $errstr ($errno)");

        $this->expect($sock, '220');

        $this->cmd($sock, "EHLO localhost");
        $this->expect($sock, '250');

        $this->cmd($sock, "STARTTLS");
        $this->expect($sock, '220');

        stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

        $this->cmd($sock, "EHLO localhost");
        $this->expect($sock, '250');

        $this->cmd($sock, "AUTH LOGIN");
        $this->expect($sock, '334');

        $this->cmd($sock, base64_encode($this->user));
        $this->expect($sock, '334');

        $this->cmd($sock, base64_encode($this->pass));
        $this->expect($sock, '235');

        $this->cmd($sock, "MAIL FROM:<{$this->from}>");
        $this->expect($sock, '250');

        $this->cmd($sock, "RCPT TO:<{$to}>");
        $this->expect($sock, '250');

        $this->cmd($sock, "DATA");
        $this->expect($sock, '354');

        $headers  = "From: {$this->fromName} <{$this->from}>\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        fwrite($sock, $headers . "\r\n" . $body . "\r\n.\r\n");
        $this->expect($sock, '250');

        $this->cmd($sock, "QUIT");
        fclose($sock);
    }

    private function cmd($sock, string $cmd): void
    {
        fwrite($sock, $cmd . "\r\n");
    }

    private function expect($sock, string $code): void
    {
        $response = '';
        while ($line = fgets($sock, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break; // last line of the SMTP response block
        }
        if (!str_starts_with(trim($response), $code)) {
            throw new RuntimeException("SMTP error (expected $code): $response");
        }
    }
}
