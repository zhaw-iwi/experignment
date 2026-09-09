<?php

declare(strict_types=1);

try {
    require_once __DIR__ . '/../config/config.php';
} catch (Throwable $exception) {
    http_response_code(404);
    exit;
}

$config = $GLOBALS['APP_CONFIG'];
if (($config['operations']['preflight_enabled'] ?? false) !== true) {
    http_response_code(404);
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
header('X-Robots-Tag: noindex, nofollow, noarchive');

function preflight_request_is_https(): bool
{
    if (strtolower((string) ($_SERVER['HTTPS'] ?? '')) === 'on') {
        return true;
    }

    return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function preflight_request_is_local(): bool
{
    return in_array((string) ($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1', '::1'], true);
}

function preflight_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function render_preflight_form(?string $message = null): void
{
    $csrfToken = (string) ($_SESSION['preflight_csrf'] ?? '');
    $httpsReady = preflight_request_is_https() || preflight_request_is_local();
    ?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Deployment preflight</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; }
        body { margin: 0; background: #f4f7fb; color: #172033; }
        main { box-sizing: border-box; width: min(680px, calc(100% - 2rem)); margin: 4rem auto; padding: 2rem; background: #fff; border: 1px solid #dce2ea; border-radius: .75rem; box-shadow: 0 1rem 2.5rem rgba(23,32,51,.08); }
        h1 { margin-top: 0; }
        label { display: block; margin: 1.25rem 0 .4rem; font-weight: 650; }
        input[type=password] { box-sizing: border-box; width: 100%; padding: .75rem; border: 1px solid #aab4c3; border-radius: .4rem; font: inherit; }
        .check { display: flex; gap: .6rem; align-items: flex-start; font-weight: 400; }
        button { margin-top: 1.25rem; padding: .75rem 1rem; border: 0; border-radius: .4rem; background: #0d6efd; color: #fff; font: inherit; font-weight: 650; cursor: pointer; }
        .notice { padding: .8rem 1rem; border-radius: .4rem; background: #fff3cd; color: #664d03; }
        .error { background: #f8d7da; color: #842029; }
        code { background: #eef1f5; padding: .1rem .25rem; border-radius: .2rem; }
    </style>
</head>
<body>
<main>
    <h1>Deployment preflight</h1>
    <p>This temporary page runs the same configuration, schema, empty-state, and rolled-back permission checks as the command-line preflight.</p>
    <?php if ($message !== null): ?>
        <p class="notice error"><?= preflight_escape($message) ?></p>
    <?php endif; ?>
    <?php if (!$httpsReady): ?>
        <p class="notice error">HTTPS is required before the administrator code can be submitted.</p>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= preflight_escape($csrfToken) ?>">
        <label for="access_code">Administrator access code</label>
        <input id="access_code" name="access_code" type="password" minlength="5" maxlength="128" required autocomplete="current-password">
        <label class="check">
            <input name="expect_empty" type="checkbox" value="1" checked>
            <span>Require all semester and runtime tables to be empty.</span>
        </label>
        <button type="submit"<?= $httpsReady ? '' : ' disabled' ?>>Run preflight</button>
    </form>
    <p class="notice">After a successful check, set <code>PREFLIGHT_ENABLED=false</code> again.</p>
</main>
</body>
</html><?php
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    header('Allow: GET, POST');
    exit;
}

$secureSetting = $config['session']['secure'] ?? null;
$secureCookie = preflight_request_is_https() || $secureSetting === true;
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
session_name((string) ($config['session']['name'] ?? 'experiment_assignment_v3'));
session_start([
    'cookie_lifetime' => 0,
    'cookie_path' => '/',
    'cookie_secure' => $secureCookie,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
    'use_only_cookies' => true,
]);

if (!is_string($_SESSION['preflight_csrf'] ?? null) || $_SESSION['preflight_csrf'] === '') {
    $_SESSION['preflight_csrf'] = bin2hex(random_bytes(32));
}

if ($method === 'GET') {
    header('Content-Type: text/html; charset=utf-8');
    render_preflight_form();
    exit;
}

if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 4096) {
    http_response_code(413);
    exit;
}

if (!preflight_request_is_https() && !preflight_request_is_local()) {
    http_response_code(400);
    header('Content-Type: text/html; charset=utf-8');
    render_preflight_form('HTTPS is required.');
    exit;
}

$providedCsrf = (string) ($_POST['csrf_token'] ?? '');
$expectedCsrf = (string) ($_SESSION['preflight_csrf'] ?? '');
if ($providedCsrf === '' || $expectedCsrf === '' || !hash_equals($expectedCsrf, $providedCsrf)) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    render_preflight_form('The security token is invalid. Reload the page and try again.');
    exit;
}

$now = time();
$lockedUntil = (int) ($_SESSION['preflight_locked_until'] ?? 0);
if ($lockedUntil > $now) {
    http_response_code(429);
    header('Retry-After: ' . ($lockedUntil - $now));
    header('Content-Type: text/html; charset=utf-8');
    render_preflight_form('Too many failed attempts. Try again later.');
    exit;
}

$configuredHash = $config['auth']['admin_access_code_hash'] ?? null;
$validHash = is_string($configuredHash)
    && $configuredHash !== ''
    && (password_get_info($configuredHash)['algoName'] ?? 'unknown') !== 'unknown';
$verificationHash = $validHash
    ? $configuredHash
    : '$2y$10$tRO.hRCW83UkMe2vgz1CsOJcy4Af4LZMr3csBRX2xQAoe7TZGxnS2';
$accessCode = trim((string) ($_POST['access_code'] ?? ''));
$authenticated = strlen($accessCode) <= 128
    && password_verify($accessCode, $verificationHash)
    && $validHash;
unset($accessCode);

if (!$authenticated) {
    $failures = (int) ($_SESSION['preflight_failures'] ?? 0) + 1;
    $_SESSION['preflight_failures'] = $failures;
    if ($failures >= 5) {
        $_SESSION['preflight_locked_until'] = $now + 900;
    }
    usleep(250_000);
    http_response_code(401);
    header('Content-Type: text/html; charset=utf-8');
    render_preflight_form('Authentication failed.');
    exit;
}

unset($_SESSION['preflight_failures'], $_SESSION['preflight_locked_until']);
$_SESSION['preflight_csrf'] = bin2hex(random_bytes(32));
session_regenerate_id(true);
session_write_close();

header('Content-Type: text/plain; charset=utf-8');
define('EXPERIMENT_AUTHORIZED_BROWSER_PREFLIGHT', true);
$argv = ['deployment_preflight.php'];
if (isset($_POST['expect_empty'])) {
    $argv[] = '--expect-empty';
}
require __DIR__ . '/../scripts/deployment_preflight.php';
