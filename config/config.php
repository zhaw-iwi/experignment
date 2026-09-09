<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

load_environment_file(dirname(__DIR__) . '/.env');

$dsnOverride = environment_value('EXPERIMENT_DB_DSN');
$dsnOverride = $dsnOverride !== null && trim($dsnOverride) !== '' ? $dsnOverride : null;
$database = [
    'host' => environment_value('EXPERIMENT_DB_HOST'),
    'port' => environment_value('EXPERIMENT_DB_PORT', '3306'),
    'name' => environment_value('EXPERIMENT_DB_NAME'),
    'charset' => environment_value('EXPERIMENT_DB_CHARSET', 'utf8mb4'),
    'username' => environment_value('EXPERIMENT_DB_USER'),
    'password' => environment_value('EXPERIMENT_DB_PASSWORD'),
];

$missing = [];
if ($dsnOverride === null) {
    foreach (['host', 'name', 'username', 'password'] as $key) {
        if ($database[$key] === null || trim($database[$key]) === '') {
            $missing[] = 'EXPERIMENT_DB_' . strtoupper($key === 'username' ? 'USER' : $key);
        }
    }
}

$dsn = $dsnOverride ?? (
    $missing === []
        ? sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $database['host'],
            $database['port'],
            $database['name'],
            $database['charset']
        )
        : ''
);

$GLOBALS['APP_CONFIG'] = [
    'db' => [
        'dsn' => $dsn,
        'username' => $dsnOverride === null ? ($database['username'] ?? '') : '',
        'password' => $dsnOverride === null ? ($database['password'] ?? '') : '',
        'missing' => $missing,
    ],
    'auth' => [
        'admin_access_code_hash' => environment_value('ADMIN_ACCESS_CODE_HASH'),
        'admin_idle_seconds' => (int) (environment_value('ADMIN_SESSION_IDLE_SECONDS', '7200') ?? '7200'),
        'student_idle_seconds' => (int) (environment_value('STUDENT_SESSION_IDLE_SECONDS', '28800') ?? '28800'),
    ],
    'session' => [
        'name' => environment_value('APP_SESSION_NAME', 'experiment_assignment_v3') ?? 'experiment_assignment_v3',
        'secure' => environment_bool('APP_SESSION_SECURE'),
    ],
];
