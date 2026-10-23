<?php
// Minimal mail helper: uses PHPMailer + SMTP when available, otherwise PHP's mail().

function load_phpmailer(): bool
{
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return true;
    }
    // Option 1: installed with Composer
    $autoload = ROOT_PATH . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
        return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
    }
    // Option 2: PHPMailer "src" folder copied to includes/PHPMailer/src
    $dir = __DIR__ . '/PHPMailer/src';
    if (is_file($dir . '/PHPMailer.php') && is_file($dir . '/SMTP.php') && is_file($dir . '/Exception.php')) {
        require_once $dir . '/Exception.php';
        require_once $dir . '/PHPMailer.php';
        require_once $dir . '/SMTP.php';
        return true;
    }
    return false;
}

function send_mail(string $to, string $subject, string $textBody): bool
{
    if (SMTP_HOST !== '' && load_phpmailer()) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = (SMTP_PORT === 465)
                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $textBody;
            $mail->send();
            return true;
        } catch (Throwable $t) {
            error_log('Mail error: ' . $t->getMessage());
            return false;
        }
    }

    // Fallback: only works if the server has a mail system configured
    $headers = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . ">\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";
    return @mail($to, $subject, $textBody, $headers);
}