<?php

declare(strict_types=1);

$database = [
    'host' => 'e93ud.myd.infomaniak.com',
    'port' => '3306',
    'name' => 'e93ud_aydemmel',
    'charset' => 'utf8mb4',
    'username' => 'e93ud_aydemmel',
    'password' => 'eS9B008$nRc!.',
];

$dsnOverride = getenv('EXPERIMENT_DB_DSN');
$dsnOverride = is_string($dsnOverride) && $dsnOverride !== '' ? $dsnOverride : null;

$missing = [];
foreach (['host', 'name', 'username', 'password'] as $key) {
    if (trim((string) ($database[$key] ?? '')) === '') {
        $missing[] = 'config.db.' . $key;
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
        'username' => $dsnOverride === null ? $database['username'] : '',
        'password' => $dsnOverride === null ? $database['password'] : '',
        'missing' => $missing,
    ],
];
