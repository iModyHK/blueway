<?php
declare(strict_types=1);

function admin_config(): array
{
    $file = dirname(__DIR__, 2) . '/site-config.php';
    return is_file($file) ? (array) (require $file) : [];
}

function b64url(string $s): string
{
    return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
}

function b64url_decode(string $s): string
{
    return (string) base64_decode(strtr($s, '-_', '+/'));
}

function verify_user(array $cfg, string $email, string $password): bool
{
    foreach ((array) $cfg['admin_users'] as $user => $hash) {
        if (strtolower((string) $user) === $email) {
            return password_verify($password, preg_replace('/^\$2[ab]\$/', '\$2y\$', (string) $hash));
        }
    }
    password_verify($password, '$2y$10$abcdefghijklmnopqrstuuWb5yRk5QF8dz1n0Yx4p1yJzv0Q6f3h2');
    return false;
}

function issue_token(array $cfg, string $email): string
{
    $payload = b64url(json_encode(['u' => $email, 'exp' => time() + 12 * 3600]));
    return $payload . '.' . b64url(hash_hmac('sha256', $payload, (string) $cfg['session_secret'], true));
}

function read_token(array $cfg, string $token): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        return null;
    }
    $sig = b64url(hash_hmac('sha256', $parts[0], (string) $cfg['session_secret'], true));
    if (!hash_equals($sig, $parts[1])) {
        return null;
    }
    $data = json_decode(b64url_decode($parts[0]), true);
    if (!is_array($data) || ($data['exp'] ?? 0) < time()) {
        return null;
    }
    $allowed = array_map('strtolower', array_keys((array) $cfg['admin_users']));
    return in_array(strtolower((string) ($data['u'] ?? '')), $allowed, true) ? $data : null;
}

function rate_limit(string $key, int $max, int $window): bool
{
    $file = sys_get_temp_dir() . '/site_rl_' . md5($key . __DIR__) . '.json';
    $now = time();
    $hits = array_values(array_filter((array) json_decode((string) @file_get_contents($file), true), fn($t) => is_int($t) && $t > $now - $window));
    if (count($hits) >= $max) {
        return false;
    }
    $hits[] = $now;
    @file_put_contents($file, json_encode($hits), LOCK_EX);
    return true;
}
