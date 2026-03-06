<?php
// ============================================================
//  Y2J Funeral Service — Gmail SMTP Configuration
// ============================================================

define('SMTP_USER', 'hannanicolec.tan@gmail.com');
define('SMTP_PASS', 'izku uzol fhnt wqad');
define('MAIL_FROM', 'hannanicolec.tan@gmail.com');
define('MAIL_TO',   'hannanicolec.tan@gmail.com');

function sendSmtpMail($to, $subject, $body) {
    $smtp_host = 'smtp.gmail.com';
    $smtp_port = 587;
    $smtp_user = SMTP_USER;
    $smtp_pass = str_replace(' ', '', SMTP_PASS);
    $from      = MAIL_FROM;
    $from_name = 'Y2J Funeral Service';

    try {
        $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 15);
        if (!$socket) return false;

        $read = function() use ($socket) { return fgets($socket, 515); };
        $send = function($cmd) use ($socket) { fputs($socket, $cmd . "\r\n"); };

        $read();
        $send("EHLO localhost");
        while ($line = $read()) { if (substr($line, 3, 1) === ' ') break; }

        $send("STARTTLS");
        $read();

        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

        $send("EHLO localhost");
        while ($line = $read()) { if (substr($line, 3, 1) === ' ') break; }

        $send("AUTH LOGIN");              $read();
        $send(base64_encode($smtp_user)); $read();
        $send(base64_encode($smtp_pass)); $read();
        $send("MAIL FROM:<$from>");       $read();
        $send("RCPT TO:<$to>");           $read();
        $send("DATA");                    $read();

        $msg  = "From: $from_name <$from>\r\n";
        $msg .= "To: <$to>\r\n";
        $msg .= "Subject: $subject\r\n";
        $msg .= "MIME-Version: 1.0\r\n";
        $msg .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $msg .= $body . "\r\n.";

        $send($msg);
        $resp = $read();
        $send("QUIT");
        fclose($socket);

        return strpos($resp, '250') !== false;
    } catch (Exception $e) {
        return false;
    }
}
