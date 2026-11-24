<?php
declare(strict_types=1);

require __DIR__ . '/lib.php';

$cfg = admin_config();
$site = json_decode((string) @file_get_contents(dirname(__DIR__) . '/contact-config.json'), true) ?: [];
$siteName = (string) ($site['name'] ?? 'Admin');
$origin = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http') . '://' . preg_replace('/[^a-z0-9.\-:]/i', '', $_SERVER['HTTP_HOST'] ?? '');
$error = '';

$missing = [];
if (!$cfg) {
    $missing[] = 'ملف site-config.php غير موجود في المجلد الرئيسي للاستضافة (بجانب public_html)';
} else {
    if (empty($cfg['admin_users'])) $missing[] = 'لا يوجد مستخدم للوحة التحكم';
    if (empty($cfg['session_secret'])) $missing[] = 'session_secret غير محدد';
    if (empty($cfg['github_repo'])) $missing[] = 'مستودع GitHub غير محدد';
    if (empty($cfg['github_token'])) $missing[] = 'توكن GitHub غير محدد';
}
if ($missing) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>لوحة التحكم غير مُعدّة</title><style>body{font-family:system-ui,Tahoma,sans-serif;background:#f1f5f9;color:#0f172a;display:grid;place-items:center;min-height:100vh;margin:0;padding:20px}.c{background:#fff;border-radius:16px;padding:28px 30px;max-width:460px;box-shadow:0 10px 30px rgb(0 0 0/.08)}h1{font-size:19px;margin:0 0 8px}p{color:#64748b;margin:0 0 14px;line-height:1.8}li{margin:6px 0;color:#b45309}</style></head><body><div class="c"><h1>لوحة التحكم غير مُعدّة بعد</h1><p>يحتاج المطوّر لإكمال الإعداد من برنامج JA Site Studio ثم رفع ملف site-config.php:</p><ul>';
    foreach ($missing as $m) echo '<li>' . htmlspecialchars($m) . '</li>';
    echo '</ul></div></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0';
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    if (!rate_limit('login_' . $ip, 5, 900)) {
        $error = 'محاولات كثيرة، حاول بعد 15 دقيقة.';
    } elseif (!verify_user($cfg, $email, $password)) {
        $error = 'البريد أو كلمة المرور غير صحيحة.';
    } else {
        $token = issue_token($cfg, $email);
        $msg = json_encode('authorization:github:success:' . json_encode(['token' => $token, 'provider' => 'github']));
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html><body><script>(function(){var o=' . json_encode($origin) . ';function r(e){if(e.origin!==o)return;window.opener.postMessage(' . $msg . ',o);window.removeEventListener("message",r);setTimeout(function(){window.close()},300)}window.addEventListener("message",r);window.opener.postMessage("authorizing:github",o)})();</script></body></html>';
        exit;
    }
}

header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex" />
  <title>تسجيل الدخول — <?= htmlspecialchars($siteName) ?></title>
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; display: grid; place-items: center; font: 15px/1.6 Tahoma, system-ui, sans-serif; background: linear-gradient(135deg, #0b1a4a, #1e56a8); color: #0f172a; padding: 20px; }
    form { width: 100%; max-width: 360px; background: #fff; border-radius: 20px; padding: 32px 28px; box-shadow: 0 20px 60px rgb(0 0 0 / .3); }
    img { display: block; width: 64px; height: 64px; object-fit: contain; margin: 0 auto 12px; }
    h1 { margin: 0 0 4px; font-size: 20px; text-align: center; }
    p.sub { margin: 0 0 24px; text-align: center; color: #64748b; font-size: 13px; }
    label { display: block; font-size: 13px; font-weight: 700; margin: 14px 0 6px; }
    input { width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font: inherit; direction: ltr; text-align: left; }
    input:focus { outline: none; border-color: #1e56a8; box-shadow: 0 0 0 3px rgb(30 86 168 / .15); }
    button { width: 100%; margin-top: 22px; padding: 12px; border: 0; border-radius: 10px; background: #1e56a8; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
    button:hover { background: #0b1a4a; }
    .err { margin-top: 16px; padding: 10px 12px; border-radius: 10px; background: #fef2f2; color: #b91c1c; font-size: 13px; }
  </style>
</head>
<body>
  <form method="post" autocomplete="on">
    <img src="/favicon.png" alt="" />
    <h1>لوحة التحكم</h1>
    <p class="sub"><?= htmlspecialchars($siteName) ?></p>
    <label for="email">البريد الإلكتروني</label>
    <input id="email" name="email" type="email" required autofocus value="<?= htmlspecialchars((string) ($_POST['email'] ?? '')) ?>" />
    <label for="password">كلمة المرور</label>
    <input id="password" name="password" type="password" required />
    <button>دخول</button>
    <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  </form>
</body>
</html>
