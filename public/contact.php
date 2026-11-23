<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

require __DIR__ . '/lib/phpmailer/Exception.php';
require __DIR__ . '/lib/phpmailer/PHPMailer.php';
require __DIR__ . '/lib/phpmailer/SMTP.php';

$configFile = dirname(__DIR__) . '/site-config.php';
$cfg = is_file($configFile) ? (require $configFile) : [];
$site = json_decode((string) @file_get_contents(__DIR__ . '/contact-config.json'), true) ?: [];

$lang = ($_POST['lang'] ?? 'ar') === 'en' ? 'en' : 'ar';
$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
$msg = [
    'ar' => ['invalid' => 'يرجى تعبئة الحقول المطلوبة بشكل صحيح.', 'rate' => 'محاولات كثيرة، حاول بعد قليل.', 'captcha' => 'فشل التحقق، حاول مرة أخرى.', 'files' => 'الملفات غير مدعومة أو حجمها كبير.', 'fail' => 'تعذر الإرسال، حاول مرة أخرى.'],
    'en' => ['invalid' => 'Please fill in the required fields correctly.', 'rate' => 'Too many attempts, please try again shortly.', 'captcha' => 'Verification failed, please try again.', 'files' => 'Unsupported or oversized files.', 'fail' => 'Sending failed, please try again.'],
][$lang];

function respond(bool $ok, string $message, string $lang, bool $json): never
{
    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    } else {
        header('Location: ' . ($lang === 'en' ? '/en/' : '/') . '?sent=' . ($ok ? '1' : '0') . '#contact');
    }
    exit;
}

function clean(string $key, int $max): string
{
    $v = trim(strip_tags((string) ($_POST[$key] ?? '')));
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v) ?? '';
    return mb_substr($v, 0, $max);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, $msg['invalid'], $lang, $wantsJson);
}
if (!empty($_POST['website'])) {
    respond(true, '', $lang, $wantsJson);
}

$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0';
$rlFile = sys_get_temp_dir() . '/site_rl_' . md5($ip . __DIR__) . '.json';
$now = time();
$hits = array_values(array_filter((array) json_decode((string) @file_get_contents($rlFile), true), fn($t) => is_int($t) && $t > $now - 600));
if (count($hits) >= 5) {
    respond(false, $msg['rate'], $lang, $wantsJson);
}
$hits[] = $now;
@file_put_contents($rlFile, json_encode($hits), LOCK_EX);

$schema = $site['forms'][$lang] ?? [];
if (!$schema) {
    $schema = [
        ['name' => 'name', 'label' => $lang === 'en' ? 'Name' : 'الاسم', 'type' => 'text', 'required' => true],
        ['name' => 'email', 'label' => $lang === 'en' ? 'Email' : 'البريد', 'type' => 'email', 'required' => true],
        ['name' => 'message', 'label' => $lang === 'en' ? 'Message' : 'الرسالة', 'type' => 'textarea', 'required' => true],
    ];
}

$values = [];
$fileFields = [];
$replyEmail = '';
$replyName = '';
foreach ($schema as $f) {
    $key = preg_replace('/[^a-z0-9_]/i', '', (string) ($f['name'] ?? ''));
    $type = (string) ($f['type'] ?? 'text');
    if ($key === '') {
        continue;
    }
    if ($type === 'file') {
        $fileFields[] = $f + ['key' => $key];
        continue;
    }
    $v = clean($key, $type === 'textarea' ? 5000 : 300);
    if ($type !== 'textarea') {
        $v = str_replace(["\r", "\n"], ' ', $v);
    }
    if ($type === 'email' && $v !== '') {
        $v = filter_var($v, FILTER_VALIDATE_EMAIL) ?: '';
        if ($v === '') {
            respond(false, $msg['invalid'], $lang, $wantsJson);
        }
        $replyEmail = $replyEmail ?: $v;
    }
    if (($type === 'select' || $type === 'services') && $v !== '' && !empty($f['options']) && !in_array($v, $f['options'], true)) {
        $v = '';
    }
    if (!empty($f['required']) && $v === '') {
        respond(false, $msg['invalid'], $lang, $wantsJson);
    }
    if ($type === 'text' && $replyName === '') {
        $replyName = $v;
    }
    if ($v !== '') {
        $values[] = [(string) ($f['label'] ?? $key), $v];
    }
}
$name = $replyName !== '' ? $replyName : ($replyEmail !== '' ? $replyEmail : 'Website');
$type = 'form';

$secret = (string) ($cfg['turnstile_secret'] ?? '');
if ($secret !== '') {
    $token = (string) ($_POST['cf-turnstile-response'] ?? '');
    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => http_build_query(['secret' => $secret, 'response' => $token, 'remoteip' => $ip]),
    ]);
    $verify = json_decode((string) curl_exec($ch), true);
    curl_close($ch);
    if (empty($verify['success'])) {
        respond(false, $msg['captcha'], $lang, $wantsJson);
    }
}

$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
$attachments = [];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$total = 0;
foreach ($fileFields as $ff) {
    $files = $_FILES[$ff['key']] ?? null;
    $names = $files['name'] ?? [];
    if (!is_array($names) || !array_filter($names)) {
        if (!empty($ff['required'])) {
            respond(false, $msg['invalid'], $lang, $wantsJson);
        }
        continue;
    }
    foreach ($files['tmp_name'] as $i => $tmp) {
        $err = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
            respond(false, $msg['files'], $lang, $wantsJson);
        }
        $mime = (string) $finfo->file($tmp);
        $total += (int) $files['size'][$i];
        if (!isset($allowed[$mime]) || count($attachments) >= 5 || $total > 10 * 1024 * 1024) {
            respond(false, $msg['files'], $lang, $wantsJson);
        }
        $attachments[] = [$tmp, 'attachment-' . (count($attachments) + 1) . '.' . $allowed[$mime]];
    }
}

$to = (string) ($cfg['to'] ?? $site['to'] ?? '');
if ($to === '') {
    respond(false, $msg['fail'], $lang, $wantsJson);
}

$rows = '';
$text = '';
foreach ($values as [$label, $v]) {
    $rows .= '<tr><td style="padding:8px 12px;background:#f1f5f9;font-weight:bold;white-space:nowrap;vertical-align:top">' . htmlspecialchars($label) . '</td><td style="padding:8px 12px">' . nl2br(htmlspecialchars($v)) . '</td></tr>';
    $text .= $label . ': ' . $v . "\n";
}
$siteName = (string) ($site['name'] ?? ($_SERVER['HTTP_HOST'] ?? ''));
$subject = ($lang === 'en' ? 'New website request' : 'طلب جديد من الموقع') . ' — ' . $name;
$html = '<div dir="' . ($lang === 'en' ? 'ltr' : 'rtl') . '" style="font-family:Tahoma,Arial,sans-serif;font-size:14px;color:#0f172a"><h2 style="margin:0 0 12px">' . htmlspecialchars($subject) . '</h2><table style="border-collapse:collapse;width:100%;max-width:640px">' . $rows . '</table><p style="color:#64748b;font-size:12px;margin-top:16px">' . htmlspecialchars($siteName) . ' · ' . date('Y-m-d H:i') . '</p></div>';

$host = preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
$mail = new PHPMailer(true);
try {
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    if (!empty($cfg['smtp_host'])) {
        $mail->isSMTP();
        $mail->Host = (string) $cfg['smtp_host'];
        $mail->Port = (int) ($cfg['smtp_port'] ?? 587);
        $mail->SMTPAuth = !empty($cfg['smtp_user']);
        $mail->Username = (string) ($cfg['smtp_user'] ?? '');
        $mail->Password = (string) ($cfg['smtp_pass'] ?? '');
        $secure = (string) ($cfg['smtp_secure'] ?? 'tls');
        $mail->SMTPSecure = $secure === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : ($secure === 'none' ? '' : PHPMailer::ENCRYPTION_STARTTLS);
        $mail->Timeout = 15;
    } else {
        $mail->isMail();
    }
    $mail->setFrom((string) ($cfg['from_email'] ?? $cfg['smtp_user'] ?? "no-reply@$host"), (string) ($cfg['from_name'] ?? $siteName));
    $mail->addAddress($to);
    if ($replyEmail !== '') {
        $mail->addReplyTo($replyEmail, $name);
    }
    $mail->Subject = $subject;
    $mail->isHTML(true);
    $mail->Body = $html;
    $mail->AltBody = $text;
    foreach ($attachments as [$path, $filename]) {
        $mail->addAttachment($path, $filename);
    }
    $mail->send();
    respond(true, '', $lang, $wantsJson);
} catch (MailException $e) {
    error_log('contact.php: ' . $mail->ErrorInfo);
    respond(false, $msg['fail'], $lang, $wantsJson);
}
