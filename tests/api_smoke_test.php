<?php

declare(strict_types=1);

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

function make_request(string $baseUrl, string $method, string $path, ?array $jsonBody = null): array
{
    $headers = ['Accept: application/json'];
    if ($jsonBody !== null) {
        $headers[] = 'Content-Type: application/json';
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

    $pdo->exec('CREATE TABLE allowed_students (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_email TEXT NOT NULL UNIQUE,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE TABLE experiments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        public_name TEXT NOT NULL,
        description TEXT NULL,
        is_open INTEGER NOT NULL DEFAULT 0,
        eligibility_mode TEXT NOT NULL DEFAULT "selected",
        condition_mode TEXT NOT NULL DEFAULT "none",
        requires_time_slot INTEGER NOT NULL DEFAULT 0,
        sort_order INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE TABLE experiment_conditions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        experiment_id INTEGER NOT NULL,
        public_name TEXT NOT NULL,
        sort_order INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE TABLE experiment_eligibilities (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        experiment_id INTEGER NOT NULL,
        student_email TEXT NOT NULL,
        condition_id INTEGER NULL,
        source TEXT NOT NULL DEFAULT "manual",
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(experiment_id, student_email)
    )');
    $pdo->exec('CREATE TABLE access_fields (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        experiment_id INTEGER NOT NULL,
        condition_id INTEGER NULL,
        field_key TEXT NOT NULL,
        label TEXT NOT NULL,
        value_type TEXT NOT NULL DEFAULT "text",
        value_source TEXT NOT NULL DEFAULT "shared",
        shared_value TEXT NULL,
        is_visible INTEGER NOT NULL DEFAULT 1,
        sort_order INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE TABLE access_pool_rows (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        experiment_id INTEGER NOT NULL,
        condition_id INTEGER NULL,
        is_assigned INTEGER NOT NULL DEFAULT 0,
        assigned_participation_id INTEGER NULL,
        assigned_at TEXT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE TABLE access_pool_values (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        pool_row_id INTEGER NOT NULL,
        field_id INTEGER NOT NULL,
        field_value TEXT NOT NULL,
        UNIQUE(pool_row_id, field_id)
    )');
    $pdo->exec('CREATE TABLE participations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        experiment_id INTEGER NOT NULL,
        condition_id INTEGER NULL,
        student_email TEXT NOT NULL,
        access_pool_row_id INTEGER NULL,
        assigned_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        confirmed_at TEXT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(experiment_id, student_email),
        UNIQUE(access_pool_row_id)
    )');
    $pdo->exec('CREATE TABLE participation_field_values (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        participation_id INTEGER NOT NULL,
        field_id INTEGER NOT NULL,
        field_value TEXT NOT NULL,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE TABLE time_slots (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        experiment_id INTEGER NOT NULL,
        label TEXT NOT NULL,
        starts_at TEXT NULL,
        ends_at TEXT NULL,
        capacity INTEGER NOT NULL DEFAULT 1,
        is_active INTEGER NOT NULL DEFAULT 1,
        sort_order INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE TABLE slot_choices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        participation_id INTEGER NOT NULL UNIQUE,
        time_slot_id INTEGER NOT NULL,
        chosen_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE TABLE appointments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        participation_id INTEGER NOT NULL UNIQUE,
        appointment_text TEXT NOT NULL,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');

    foreach (['alice@students.zhaw.ch', 'bob@students.zhaw.ch', 'charlie@students.zhaw.ch'] as $email) {
        $pdo->prepare('INSERT INTO allowed_students (student_email) VALUES (?)')->execute([$email]);
    }

    $pdo->exec("INSERT INTO experiments
        (id, public_name, description, is_open, eligibility_mode, condition_mode, requires_time_slot, sort_order)
        VALUES
        (1, 'Experiment 1', 'Choice flow', 1, 'all_allowed', 'student_choice', 0, 10),
        (2, 'Experiment 3', 'Slot flow', 1, 'all_allowed', 'none', 1, 20)");
    $pdo->exec("INSERT INTO experiment_conditions (id, experiment_id, public_name, sort_order)
        VALUES (1, 1, 'Text', 10), (2, 1, 'Tablet', 20)");
    $pdo->exec("INSERT INTO access_fields
        (id, experiment_id, condition_id, field_key, label, value_type, value_source, shared_value, sort_order)
        VALUES
        (1, 1, 1, 'pid', 'Participant ID', 'pid', 'pool', NULL, 10),
        (2, 1, 1, 'survey', 'Umfrage', 'url', 'pool', NULL, 20),
        (3, 2, NULL, 'pid', 'Participant ID', 'pid', 'pool', NULL, 10)");
    $pdo->exec("INSERT INTO access_pool_rows (id, experiment_id, condition_id)
        VALUES (1, 1, 1), (2, 1, 1), (3, 2, NULL), (4, 2, NULL)");
    $pdo->exec("INSERT INTO access_pool_values (pool_row_id, field_id, field_value)
        VALUES
        (1, 1, 'T001'), (1, 2, 'https://example.test/survey/1'),
        (2, 1, 'T002'), (2, 2, 'https://example.test/survey/2'),
        (3, 3, 'S001'), (4, 3, 'S002')");
    $pdo->exec("INSERT INTO time_slots
        (id, experiment_id, label, starts_at, ends_at, capacity, is_active, sort_order)
        VALUES
        (1, 2, 'Montag Vormittag', '2026-06-01 08:00:00', '2026-06-01 12:00:00', 1, 1, 10)");
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

    $response = make_request($baseUrl, 'GET', '/api/bootstrap.php');
    assert_equals($response['status'], 200, 'bootstrap should return 200');
    assert_equals($response['body']['version'] ?? null, 2, 'bootstrap should expose V2');

    $response = make_request($baseUrl, 'GET', '/api/student_overview.php?email=alice%40students.zhaw.ch');
    assert_equals($response['status'], 200, 'overview should return 200');
    assert_equals(count($response['body']['experiments'] ?? []), 2, 'overview should include two experiments');

    $response = make_request($baseUrl, 'POST', '/api/claim.php', [
        'email' => 'alice@students.zhaw.ch',
        'experimentId' => 1,
    ]);
    assert_equals($response['status'], 422, 'condition choice should be required');
    assert_equals($response['body']['error_code'] ?? null, 'CONDITION_REQUIRED', 'missing condition should be explicit');

    $response = make_request($baseUrl, 'POST', '/api/claim.php', [
        'email' => 'alice@students.zhaw.ch',
        'experimentId' => 1,
        'conditionId' => 1,
    ]);
    assert_equals($response['status'], 200, 'claim should return 200');
    assert_equals($response['body']['reused'] ?? null, false, 'first claim should not be reused');
    $experiment = $response['body']['overview']['experiments'][0] ?? [];
    assert_equals($experiment['assigned'] ?? null, true, 'claimed experiment should be assigned');
    assert_equals(count($experiment['accessItems'] ?? []), 2, 'claimed experiment should include access items');

    $response = make_request($baseUrl, 'POST', '/api/claim.php', [
        'email' => 'alice@students.zhaw.ch',
        'experimentId' => 1,
        'conditionId' => 1,
    ]);
    assert_equals($response['body']['reused'] ?? null, true, 'second claim should reuse participation');

    $response = make_request($baseUrl, 'POST', '/api/claim.php', [
        'email' => 'bob@students.zhaw.ch',
        'experimentId' => 2,
    ]);
    assert_equals($response['status'], 200, 'slot experiment claim should return 200');

    $response = make_request($baseUrl, 'POST', '/api/choose_slot.php', [
        'email' => 'bob@students.zhaw.ch',
        'experimentId' => 2,
        'slotId' => 1,
    ]);
    assert_equals($response['status'], 200, 'slot choice should return 200');

    $response = make_request($baseUrl, 'POST', '/api/claim.php', [
        'email' => 'charlie@students.zhaw.ch',
        'experimentId' => 2,
    ]);
    assert_equals($response['status'], 200, 'second slot experiment claim should return 200');

    $response = make_request($baseUrl, 'POST', '/api/choose_slot.php', [
        'email' => 'charlie@students.zhaw.ch',
        'experimentId' => 2,
        'slotId' => 1,
    ]);
    assert_equals($response['status'], 409, 'full slot should return 409');
    assert_equals($response['body']['error_code'] ?? null, 'SLOT_FULL', 'full slot should be explicit');

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
