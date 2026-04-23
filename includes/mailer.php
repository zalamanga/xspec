<?php
/**
 * Mailer helper: kirim email pakai PHPMailer + SMTP authenticated.
 * Dipake di process-contact.php dan process-download.php.
 */

require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Kirim email via SMTP.
 *
 * @param string $to           Recipient email
 * @param string $subject      Email subject
 * @param string $html_body    HTML body
 * @param array  $opts         Optional: ['reply_to' => [email, name], 'reply_to_name' => '...']
 * @return array ['ok' => bool, 'error' => string|null]
 */
function send_smtp_email($to, $subject, $html_body, $opts = []) {
    $cfg_file = __DIR__ . '/../config/email.php';
    if (!file_exists($cfg_file)) {
        error_log('mailer: config/email.php tidak ditemukan (copy dari email.example.php)');
        return ['ok' => false, 'error' => 'Email config missing'];
    }
    $cfg = require $cfg_file;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['smtp_user'];
        $mail->Password   = $cfg['smtp_pass'];
        $mail->SMTPSecure = $cfg['smtp_secure'];
        $mail->Port       = (int) $cfg['smtp_port'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($cfg['from_email'], $cfg['from_name']);
        $mail->addAddress($to);

        if (!empty($opts['reply_to'])) {
            $rt_name = $opts['reply_to_name'] ?? '';
            $mail->addReplyTo($opts['reply_to'], $rt_name);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;
        $mail->AltBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $html_body));

        $mail->send();
        return ['ok' => true, 'error' => null];
    } catch (PHPMailerException $e) {
        error_log('mailer send error: ' . $mail->ErrorInfo);
        return ['ok' => false, 'error' => $mail->ErrorInfo];
    }
}
