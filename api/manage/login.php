<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');

$payload = read_json_body();
$accessCode = trim((string) ($payload['accessCode'] ?? ''));
$configuredHash = app_config()['auth']['admin_access_code_hash'] ?? null;
if (!is_string($configuredHash) || $configuredHash === '' || (password_get_info($configuredHash)['algoName'] ?? 'unknown') === 'unknown') {
    fail(500, 'ADMIN_AUTHENTICATION_NOT_CONFIGURED', 'Die Administrator-Anmeldung ist nicht konfiguriert.');
}

$pdo = db();
if (authentication_is_throttled($pdo, 'admin', 'admin')) {
    throttled_authentication_failure();
}

if (strlen($accessCode) > 128 || !password_verify($accessCode, $configuredHash)) {
    record_authentication_failure($pdo, 'admin', 'admin');
    generic_authentication_failure();
}

clear_authentication_failures($pdo, 'admin', 'admin');
$auth = begin_admin_authentication();

json_response(200, [
    'authenticated' => true,
    'csrfToken' => $auth['csrf_token'],
]);
