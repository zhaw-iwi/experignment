<?php

declare(strict_types=1);

$host = getenv('EXPERIMENT_DB_HOST') ?: 'e93ud.myd.infomaniak.com';
$port = getenv('EXPERIMENT_DB_PORT') ?: '3306';
$name = getenv('EXPERIMENT_DB_NAME') ?: 'e93ud_aydemmel';
$charset = getenv('EXPERIMENT_DB_CHARSET') ?: 'utf8mb4';
$dsnOverride = getenv('EXPERIMENT_DB_DSN');

$dsn = is_string($dsnOverride) && $dsnOverride !== ''
    ? $dsnOverride
    : sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $name, $charset);

$username = getenv('EXPERIMENT_DB_USER') ?: 'e93ud_aydemmel';
$password = getenv('EXPERIMENT_DB_PASSWORD') ?: 'w@M90Aq26KU&k.%';

if (is_string($dsnOverride) && $dsnOverride !== '') {
    $dbUsername = $username !== false ? $username : '';
    $dbPassword = $password !== false ? $password : '';
} else {
    $dbUsername = $username !== false ? $username : 'replace_me';
    $dbPassword = $password !== false ? $password : 'replace_me';
}

$GLOBALS['APP_CONFIG'] = [
    'db' => [
        'dsn' => $dsn,
        'username' => $dbUsername,
        'password' => $dbPassword,
    ],
];
