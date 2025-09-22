<?php
// Simple mail helper. For production, configure SMTP or a transactional provider.
// Usage: sendSystemEmail($toEmail, $subject, $htmlBody, $textBodyOptional)
// Load optional local config overrides
if (file_exists(__DIR__ . '/env.local.php')) { include_once __DIR__ . '/env.local.php'; }

function sendSystemEmail($to, $subject, $html, $text = '') {
    $to = (string)$to;
    if ($to === '') return false;
    $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $from = 'no-reply@' . preg_replace('/^www\./', '', $domain);
    $fromName = 'Jetlouge Travels';
    // Allow env overrides for better deliverability (use a verified sender)
    $envFrom = getenv('MAIL_FROM');
    if ($envFrom && filter_var($envFrom, FILTER_VALIDATE_EMAIL)) { $from = $envFrom; }
    elseif (defined('MAIL_FROM') && filter_var(MAIL_FROM, FILTER_VALIDATE_EMAIL)) { $from = MAIL_FROM; }
    $envFromName = getenv('MAIL_FROM_NAME');
    if ($envFromName && is_string($envFromName)) { $fromName = $envFromName; }
    elseif (defined('MAIL_FROM_NAME')) { $fromName = MAIL_FROM_NAME; }
    // Sender domain (used for EHLO and Message-ID)
    $fromDomain = (strpos($from, '@') !== false) ? substr($from, strpos($from, '@') + 1) : preg_replace('/^www\./', '', $domain);
    // Create plain text version when not provided
    $text = $text !== '' ? $text : strip_tags(preg_replace('/<br\s*\/>/i', "\n", $html));

    // Try SendGrid API first if configured
    $sgKey = getenv('SENDGRID_API_KEY'); if (!$sgKey && defined('SENDGRID_API_KEY')) { $sgKey = SENDGRID_API_KEY; }
    if ($sgKey && function_exists('curl_init')) {
        $payload = [
            'personalizations' => [[ 'to' => [[ 'email' => $to ]] ]],
            'from' => [ 'email' => $from, 'name' => $fromName ],
            'subject' => $subject,
            'content' => [
                [ 'type' => 'text/plain', 'value' => $text ],
                [ 'type' => 'text/html',  'value' => $html ],
            ],
        ];
        $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $sgKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $resp = curl_exec($ch);
        $errno = curl_errno($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($errno === 0 && $status >= 200 && $status < 300) {
            return true; // SendGrid success (202 Accepted)
        }
        error_log('[MAIL] SendGrid failed status=' . $status . ' err=' . $errno . ' resp=' . substr((string)$resp, 0, 300));
        // fall through to mail() fallback
    }
    // Try direct SMTP (TLS/SSL) if configured via env or constants
    $smtpHost = getenv('SMTP_HOST');
    $smtpUser = getenv('SMTP_USERNAME') ?: getenv('SMTP_USER');
    $smtpPass = getenv('SMTP_PASSWORD') ?: getenv('SMTP_PASS');
    $smtpPort = (int)(getenv('SMTP_PORT') ?: 587);
    $smtpSecure = strtolower((string)(getenv('SMTP_SECURE') ?: 'tls')); // tls|ssl|none
    if (!$smtpHost && defined('SMTP_HOST')) { $smtpHost = SMTP_HOST; }
    if (!$smtpUser && defined('SMTP_USERNAME')) { $smtpUser = SMTP_USERNAME; }
    if (!$smtpPass && defined('SMTP_PASSWORD')) { $smtpPass = SMTP_PASSWORD; }
    if ((int)$smtpPort === 0 && defined('SMTP_PORT')) { $smtpPort = (int)SMTP_PORT; }
    if (!$smtpSecure && defined('SMTP_SECURE')) { $smtpSecure = strtolower(SMTP_SECURE); }
    if ($smtpHost && $smtpUser && $smtpPass) {
        $target = ($smtpSecure === 'ssl') ? ('ssl://' . $smtpHost) : $smtpHost;
        $fp = @stream_socket_client($target . ':' . $smtpPort, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
        if ($fp) {
            stream_set_timeout($fp, 20);
            $read = function() use ($fp) { $d=''; while(($l=fgets($fp,515))!==false){ $d.=$l; if(strlen($l)<4||$l[3]!=='-') break; } return $d; };
            $expect = function($code) use ($read) { $r=$read(); return ((int)substr($r,0,3)===(int)$code); };
            if ($expect(220)) {
                $ehlo = preg_replace('/^www\./','',($fromDomain ?: ($_SERVER['HTTP_HOST']??'localhost')));
                fwrite($fp, "EHLO $ehlo\r\n"); if(!$expect(250)){ fwrite($fp, "HELO $ehlo\r\n"); $expect(250);}            
                if ($smtpSecure === 'tls') { fwrite($fp, "STARTTLS\r\n"); if($expect(220)){ @stream_socket_enable_crypto($fp,true,STREAM_CRYPTO_METHOD_TLS_CLIENT); fwrite($fp, "EHLO $ehlo\r\n"); $expect(250);} }
                fwrite($fp, "AUTH LOGIN\r\n"); $expect(334);
                fwrite($fp, base64_encode($smtpUser)."\r\n"); $expect(334);
                fwrite($fp, base64_encode($smtpPass)."\r\n"); $expect(235);
                fwrite($fp, 'MAIL FROM: <'.$from.">\r\n"); $expect(250);
                fwrite($fp, 'RCPT TO: <'.$to.">\r\n"); $expect(250);
                fwrite($fp, "DATA\r\n"); $expect(354);
                $b = md5(uniqid('',true));
                $hdr  = 'From: '.($fromName?('=?UTF-8?B?'.base64_encode($fromName).'?= <'.$from.'>'):$from)."\r\n";
                $hdr .= 'To: <'.$to.'>' . "\r\n";
                $hdr .= 'Reply-To: '.$from."\r\n";
                $hdr .= 'Date: ' . date('r') . "\r\n";
                $hdr .= 'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $fromDomain . '>' . "\r\n";
                $hdr .= 'Subject: ' . '=?UTF-8?B?' . base64_encode($subject) . '?=' . "\r\n";
                $hdr .= "MIME-Version: 1.0\r\n";
                $hdr .= 'Content-Type: multipart/alternative; boundary="'.$b.'"' . "\r\n";
                $msg  = "\r\n";
                $msg .= '--'.$b."\r\n".'Content-Type: text/plain; charset="utf-8"' . "\r\n".'Content-Transfer-Encoding: 7bit' . "\r\n\r\n" . $text . "\r\n\r\n";
                $msg .= '--'.$b."\r\n".'Content-Type: text/html; charset="utf-8"' . "\r\n".'Content-Transfer-Encoding: 7bit' . "\r\n\r\n" . $html . "\r\n\r\n";
                $msg .= '--'.$b."--\r\n\r\n";
                fwrite($fp, $hdr.$msg.".\r\n"); $expect(250);
                fwrite($fp, "QUIT\r\n");
                @fclose($fp);
                return true;
            }
            @fclose($fp);
        }
    }

    // Fallback: PHP mail()
    $boundary = md5(uniqid(time(), true));
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "From: {$fromName} <{$from}>\r\n";
    $headers .= "To: <{$to}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= 'Date: ' . date('r') . "\r\n";
    $headers .= 'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $fromDomain . ">\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

    $message  = "--{$boundary}\r\n";
    $message .= "Content-Type: text/plain; charset=\"utf-8\"\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= $text . "\r\n\r\n";
    $message .= "--{$boundary}\r\n";
    $message .= "Content-Type: text/html; charset=\"utf-8\"\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= $html . "\r\n\r\n";
    $message .= "--{$boundary}--\r\n";

    $ok = @mail($to, $subject, $message, $headers);
    if (!$ok) {
        error_log('[MAIL] Failed to send to ' . $to . ' subject=' . $subject);
    }
    return $ok;
}
