<?php

declare(strict_types=1);

const TEST_COOKIE_NAME = 'experiment_assignment_token';

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assert_equals(mixed $actual, mixed $expected, string $message): void
{
    if ($actual !== $expected) {
        throw new RuntimeException(
            $message . ' | expected=' . var_export($expected, true) . ' actual=' . var_export($actual, true)
        );
    }
}

function make_request(string $baseUrl, string $method, string $path, ?array $jsonBody = null, ?string &$cookie = null): array
{
    $headers = [
        'Accept: application/json',
    ];

    if ($jsonBody !== null) {
        $headers[] = 'Content-Type: application/json';
    }

    if ($cookie !== null) {
        $headers[] = 'Cookie: ' . TEST_COOKIE_NAME . '=' . $cookie;
    }

    $options = [
        'http' => [
            'method' => strtoupper($method),
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true,
            'timeout' => 10,
        ],
    ];

    if ($jsonBody !== null) {
        $options['http']['content'] = json_encode($jsonBody, JSON_THROW_ON_ERROR);
    }

    $context = stream_context_create($options);
    $rawBody = file_get_contents($baseUrl . $path, false, $context);
    $rawBody = is_string($rawBody) ? $rawBody : '';

    $responseHeaders = $http_response_header ?? [];
    $status = 0;
    if (isset($responseHeaders[0]) && preg_match('/\s(\d{3})\s/', $responseHeaders[0], $matches) === 1) {
        $status = (int) $matches[1];
    }

    foreach ($responseHeaders as $headerLine) {
        if (preg_match('/^Set-Cookie:\s*' . TEST_COOKIE_NAME . '=([^;]+)/i', $headerLine, $matches) === 1) {
            $cookie = $matches[1];
        }
    }

    $decoded = json_decode($rawBody, true);

    return [
        'status' => $status,
        'body' => is_array($decoded) ? $decoded : null,
        'raw' => $rawBody,
    ];
}

function setup_sqlite_database(string $dbPath): void
{
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec(
        'CREATE TABLE allowed_students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_email TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );
    $pdo->exec(
        'CREATE TABLE participant_credits (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_email TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );
    $pdo->exec(
        'CREATE TABLE assignment_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            pool TEXT NOT NULL,
            survey_url TEXT NOT NULL,
            pin TEXT NOT NULL,
            agent_url TEXT NULL,
            is_assigned INTEGER NOT NULL DEFAULT 0,
            assigned_at TEXT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(pool, pin)
        )'
    );
    $pdo->exec(
        'CREATE TABLE participant_assignment_links (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_email TEXT NOT NULL UNIQUE,
            assignment_item_id INTEGER NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (assignment_item_id) REFERENCES assignment_items(id)
        )'
    );
    $pdo->exec(
        'CREATE TABLE browser_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            token_hash TEXT NOT NULL UNIQUE,
            assignment_item_id INTEGER NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_seen_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (assignment_item_id) REFERENCES assignment_items(id)
        )'
    );

    $insert = $pdo->prepare(
        'INSERT INTO assignment_items (pool, survey_url, pin, agent_url) VALUES (:pool, :survey_url, :pin, :agent_url)'
    );
    $allowedInsert = $pdo->prepare(
        'INSERT INTO allowed_students (student_email) VALUES (:student_email)'
    );

    foreach (['alice@students.zhaw.ch', 'bob@students.zhaw.ch', 'charlie@students.zhaw.ch'] as $email) {
        $allowedInsert->execute([
            'student_email' => $email,
        ]);
    }

    $insert->execute([
        'pool' => 'text',
        'survey_url' => 'https://example.test/survey/text-1',
        'pin' => 'T001',
        'agent_url' => 'https://example.test/agent/1',
    ]);
    $insert->execute([
        'pool' => 'text',
        'survey_url' => 'https://example.test/survey/text-2',
        'pin' => 'T002',
        'agent_url' => 'https://example.test/agent/2',
    ]);
    $insert->execute([
        'pool' => 'tablet',
        'survey_url' => 'https://example.test/survey/tablet-1',
        'pin' => 'TAB01',
        'agent_url' => null,
    ]);
}

function wait_for_server(string $baseUrl): void
{
    $tries = 0;
    while ($tries < 50) {
        $response = @file_get_contents($baseUrl . '/api/bootstrap.php');
        if ($response !== false) {
            return;
        }
        usleep(100_000);
        $tries++;
    }

    throw new RuntimeException('PHP test server did not start in time.');
}

$dbPath = tempnam(sys_get_temp_dir(), 'experiment_smoke_');
if ($dbPath === false) {
    throw new RuntimeException('Could not create temporary DB file.');
}

$drivers = PDO::getAvailableDrivers();
if (!in_array('sqlite', $drivers, true)) {
    fwrite(
        STDOUT,
        'api_smoke_test.php: skipped (pdo_sqlite not available; installed PDO drivers: ' . implode(', ', $drivers) . ')' . PHP_EOL
    );
    exit(0);
}

$process = null;
$pipes = [];

try {
    setup_sqlite_database($dbPath);

    $docRoot = realpath(__DIR__ . '/..');
    assert_true(is_string($docRoot) && $docRoot !== '', 'Invalid docroot for smoke test.');

    $port = random_int(18080, 18999);
    $baseUrl = 'http://127.0.0.1:' . $port;
    $command = '"' . PHP_BINARY . '" -S 127.0.0.1:' . $port . ' -t "' . $docRoot . '"';

    $env = $_ENV;
    $env['EXPERIMENT_DB_DSN'] = 'sqlite:' . $dbPath;
    $env['EXPERIMENT_DB_USER'] = '';
    $env['EXPERIMENT_DB_PASSWORD'] = '';

    $process = proc_open(
        $command,
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        null,
        $env
    );

    assert_true(is_resource($process), 'Failed to start PHP built-in server.');
    wait_for_server($baseUrl);

    $cookieA = null;
    $response = make_request($baseUrl, 'GET', '/api/bootstrap.php', null, $cookieA);
    assert_equals($response['status'], 200, 'bootstrap without cookie should return 200');
    assert_equals($response['body']['assignment'] ?? null, null, 'bootstrap without cookie should return null assignment');

    $response = make_request($baseUrl, 'POST', '/api/claim.php', [
        'email' => 'alice@students.zhaw.ch',
        'pool' => 'text',
        'tabletConfirmed' => false,
    ], $cookieA);
    assert_equals($response['status'], 200, 'text claim should return 200');
    assert_equals($response['body']['reused'] ?? null, false, 'first claim must not be reused');
    assert_equals($response['body']['assignment']['pool'] ?? null, 'text', 'text claim should return text assignment');
    assert_true(($response['body']['assignment']['agentUrl'] ?? null) !== null, 'text assignment should include agentUrl');
    assert_true(is_string($cookieA) && $cookieA !== '', 'claim should set assignment cookie');
    $assignmentA = $response['body']['assignment'] ?? null;
    assert_true(is_array($assignmentA), 'claim should return assignment payload');

    $response = make_request($baseUrl, 'GET', '/api/bootstrap.php', null, $cookieA);
    assert_equals($response['status'], 200, 'bootstrap with cookie should return 200');
    assert_equals($response['body']['assignment'] ?? null, $assignmentA, 'bootstrap should recover same assignment');

    $response = make_request($baseUrl, 'POST', '/api/claim.php', [
        'email' => 'ignored@students.zhaw.ch',
        'pool' => 'tablet',
        'tabletConfirmed' => true,
    ], $cookieA);
    assert_equals($response['status'], 200, 'claim with existing cookie should return 200');
    assert_equals($response['body']['reused'] ?? null, true, 'claim with existing cookie should be reused');
    assert_equals($response['body']['assignment'] ?? null, $assignmentA, 'reused claim should return same assignment');

    $cookieB = null;
    $response = make_request($baseUrl, 'POST', '/api/claim.php', [
        'email' => 'mallory@students.zhaw.ch',
        'pool' => 'text',
        'tabletConfirmed' => false,
    ], $cookieB);
    assert_equals($response['status'], 422, 'unknown email should return 422');
    assert_equals($response['body']['error_code'] ?? null, 'EMAIL_NOT_RECOGNIZED', 'unknown email should return EMAIL_NOT_RECOGNIZED');

    $cookieB = null;
    $response = make_request($baseUrl, 'POST', '/api/claim.php', [
        'email' => 'alice@students.zhaw.ch',
        'pool' => 'text',
        'tabletConfirmed' => false,
    ], $cookieB);
    assert_equals($response['status'], 409, 'duplicate email should return 409');
    assert_equals($response['body']['error_code'] ?? null, 'EMAIL_ALREADY_USED', 'duplicate email should return EMAIL_ALREADY_USED');

    $cookieC = null;
    $response = make_request($baseUrl, 'POST', '/api/claim.php', [
        'email' => 'bob@students.zhaw.ch',
        'pool' => 'tablet',
        'tabletConfirmed' => false,
    ], $cookieC);
    assert_equals($response['status'], 422, 'tablet claim without confirmation should return 422');
    assert_equals(
        $response['body']['error_code'] ?? null,
        'TABLET_CONFIRMATION_REQUIRED',
        'tablet without confirmation should return TABLET_CONFIRMATION_REQUIRED'
    );

    $response = make_request($baseUrl, 'POST', '/api/claim.php', [
        'email' => 'bob@students.zhaw.ch',
        'pool' => 'tablet',
        'tabletConfirmed' => true,
    ], $cookieC);
    assert_equals($response['status'], 200, 'tablet claim with confirmation should return 200');
    assert_equals($response['body']['reused'] ?? null, false, 'tablet first claim must not be reused');
    assert_equals($response['body']['assignment']['pool'] ?? null, 'tablet', 'tablet claim should return tablet assignment');
    assert_equals($response['body']['assignment']['agentUrl'] ?? null, null, 'tablet assignment should not include agentUrl');

    $cookieD = null;
    $response = make_request($baseUrl, 'POST', '/api/claim.php', [
        'email' => 'charlie@students.zhaw.ch',
        'pool' => 'tablet',
        'tabletConfirmed' => true,
    ], $cookieD);
    assert_equals($response['status'], 409, 'exhausted tablet pool should return 409');
    assert_equals($response['body']['error_code'] ?? null, 'POOL_EXHAUSTED', 'exhausted pool should return POOL_EXHAUSTED');

    fwrite(STDOUT, 'api_smoke_test.php: ok' . PHP_EOL);
} finally {
    if (is_resource($process)) {
        proc_terminate($process);
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        proc_close($process);
    }

    if (is_file($dbPath)) {
        unlink($dbPath);
    }
}
