<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Try to load Composer autoloader if available
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

/**
 * Send HTML email via PHPMailer SMTP
 * Expects environment variables:
 *  SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_SECURE (tls/ssl), EMAIL_FROM
 *
 * @param string $to
 * @param string $subject
 * @param string $html
 * @param array $options ['from' => 'Name <addr>', 'cc' => [], 'replyTo' => 'addr']
 * @return array ['success' => bool, 'error' => ?string]
 */
function send_email_phpmailer(string $to, string $subject, string $html, array $options = []): array {
    // Fail fast if PHPMailer is not available
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return ['success' => false, 'error' => 'PHPMailer not installed. Run composer require phpmailer/phpmailer'];
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->Port       = (int)(getenv('SMTP_PORT') ?: 587);
        $mail->SMTPAuth   = true;
        // Prefer SMTP_* vars, fallback to GMAIL_* vars for convenience
        $mail->Username   = getenv('SMTP_USER') ?: (getenv('GMAIL_EMAIL') ?: '');
        $mail->Password   = getenv('SMTP_PASS') ?: (getenv('GMAIL_APP_PASSWORD') ?: '');
        $secure           = strtolower((string)(getenv('SMTP_SECURE') ?: 'tls'));
        $mail->SMTPSecure = $secure === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet    = 'UTF-8';

        $from = $options['from'] ?? (getenv('EMAIL_FROM') ?: 'PSAU Admissions <no-reply@psau-admission-system2.onrender.com>');
        if (preg_match('/^(.*)<(.+)>$/', $from, $m)) {
            $mail->setFrom(trim($m[2]), trim($m[1]));
        } else {
            $mail->setFrom($from, 'PSAU Admissions');
        }

        $mail->addAddress($to);

        if (!empty($options['cc'])) {
            foreach ((array)$options['cc'] as $cc) {
                $mail->addCC($cc);
            }
        }
        if (!empty($options['replyTo'])) {
            $mail->addReplyTo($options['replyTo']);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        error_log('PHPMailer send error: ' . $mail->ErrorInfo);
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}


