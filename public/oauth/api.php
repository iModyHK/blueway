<?php
declare(strict_types=1);

require __DIR__ . '/lib.php';

function fail(int $code, string $message): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['message' => $message]);
    exit;
}

$cfg = admin_config();
if (empty($cfg['github_token']) || empty($cfg['github_repo']) || empty($cfg['session_secret'])) {
    fail(500, 'Admin API is not configured');
}

$auth = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
if ($auth === '' && function_exists('getallheaders')) {
    foreach (getallheaders() as $k => $v) {
        if (strtolower($k) === 'authorization') {
            $auth = (string) $v;
        }
    }
}
$session = read_token($cfg, (string) preg_replace('/^(token|bearer)\s+/i', '', $auth));
if (!$session) {
    fail(401, 'Bad credentials');
}

$uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
$pos = strpos($uri, 'api.php');
$rest = $pos === false ? '' : substr($uri, $pos + strlen('api.php'));
$path = (string) parse_url($rest, PHP_URL_PATH);
$query = (string) parse_url($rest, PHP_URL_QUERY);
$path = '/' . ltrim($path, '/');

$repo = strtolower(trim((string) $cfg['github_repo'], '/'));
$isUser = $path === '/user';
$isRepo = str_starts_with(strtolower($path), "/repos/$repo") && (strlen($path) === strlen("/repos/$repo") || $path[strlen("/repos/$repo")] === '/');
if ((!$isUser && !$isRepo) || str_contains($path, '..')) {
    fail(403, 'Forbidden');
}
if ($isRepo && preg_match('#^/repos/[^/]+/[^/]+/(hooks|keys|collaborators|actions/secrets|environments|deployments|invitations)#i', $path)) {
    fail(403, 'Forbidden');
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true) || ($isUser && $method !== 'GET')) {
    fail(405, 'Method not allowed');
}

$headers = [
    'Authorization: Bearer ' . $cfg['github_token'],
    'User-Agent: site-admin-proxy',
    'Accept: ' . ($_SERVER['HTTP_ACCEPT'] ?? 'application/vnd.github+json'),
    'X-GitHub-Api-Version: 2022-11-28',
];
if (!empty($_SERVER['CONTENT_TYPE'])) {
    $headers[] = 'Content-Type: ' . $_SERVER['CONTENT_TYPE'];
}
if (!empty($_SERVER['HTTP_IF_NONE_MATCH'])) {
    $headers[] = 'If-None-Match: ' . $_SERVER['HTTP_IF_NONE_MATCH'];
}

$responseHeaders = [];
$ch = curl_init('https://api.github.com' . $path . ($query !== '' ? '?' . $query : ''));
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$responseHeaders) {
        $parts = explode(':', $line, 2);
        if (count($parts) === 2) {
            $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
        }
        return strlen($line);
    },
]);
if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, (string) file_get_contents('php://input'));
}
$body = curl_exec($ch);
$status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($body === false) {
    fail(502, 'Upstream error: ' . $err);
}

if ($isUser && $status === 200) {
    $user = json_decode((string) $body, true) ?: [];
    $user['name'] = $session['u'];
    $user['email'] = $session['u'];
    $body = json_encode($user);
}

http_response_code($status);
foreach (['content-type', 'etag', 'link', 'cache-control', 'last-modified'] as $h) {
    if (isset($responseHeaders[$h])) {
        $value = $h === 'link' ? str_replace('https://api.github.com', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . substr($uri, 0, (int) $pos + strlen('api.php')), $responseHeaders[$h]) : $responseHeaders[$h];
        header(ucwords($h, '-') . ': ' . $value);
    }
}
echo $body;
