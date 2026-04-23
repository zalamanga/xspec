<?php
/**
 * EMAIL SMTP CONFIG TEMPLATE
 *
 * Copy file ini jadi config/email.php lalu isi credential SMTP asli.
 * File config/email.php di-GITIGNORE biar password gak ter-commit.
 */
return [
    'smtp_host'   => 'mail.xspectechnology.com', // cek cPanel -> Email Accounts -> Connect Devices
    'smtp_port'   => 465,                         // 465 untuk SSL, 587 untuk TLS
    'smtp_secure' => 'ssl',                       // 'ssl' atau 'tls'
    'smtp_user'   => 'no-replys@xspectechnology.com',
    'smtp_pass'   => 'GANTI_DENGAN_PASSWORD_ASLI',
    'from_email'  => 'no-replys@xspectechnology.com',
    'from_name'   => 'XSpec Website',
];
