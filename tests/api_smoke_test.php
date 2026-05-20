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
    $rawBody = @file_get_contents($baseUrl . $path, false, $context);
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
        'serverOutput' => read_server_output(),
    ];
}

function read_server_output(): string
{
    $output = '';
    foreach (['SERVER_STDOUT_PATH' => 'stdout', 'SERVER_STDERR_PATH' => 'stderr'] as $globalName => $label) {
        $path = $GLOBALS[$globalName] ?? null;
        if (is_string($path) && is_file($path)) {
            $chunk = file_get_contents($path);
            if (is_string($chunk) && $chunk !== '') {
                $output .= strtoupper($label) . ":\n" . $chunk . "\n";
            }
        }
    }

    return $output;
}

function experiment_by_id(array $overview, int $experimentId): array
{
    foreach ($overview['experiments'] ?? [] as $experiment) {
        if (($experiment['id'] ?? null) === $experimentId) {
            return $experiment;
        }
    }

    throw new RuntimeException('Experiment not found in overview: ' . $experimentId);
}

function dashboard_participation(array $dashboard, string $email, int $experimentId): array
{
    foreach ($dashboard['participations'] ?? [] as $participation) {
        if (($participation['email'] ?? '') === $email && ($participation['experimentId'] ?? null) === $experimentId) {
            return $participation;
        }
    }

    throw new RuntimeException('Participation not found in dashboard: ' . $email);
}

function dashboard_access_field_by_key(array $dashboard, int $experimentId, string $key): array
{
    foreach ($dashboard['experiments'] ?? [] as $experiment) {
        if (($experiment['id'] ?? null) !== $experimentId) {
            continue;
        }
        foreach ($experiment['accessFields'] ?? [] as $field) {
            if (($field['key'] ?? '') === $key) {
                return $field;
            }
        }
    }

    throw new RuntimeException('Access field not found in dashboard: ' . $key);
}

function dashboard_assigned_condition_count(array $dashboard, int $experimentId): int
{
    $experiment = experiment_by_id($dashboard, $experimentId);
    $count = 0;
    foreach ($experiment['eligibilities'] ?? [] as $eligibility) {
        if (($eligibility['conditionId'] ?? null) !== null) {
            $count++;
        }
    }

    return $count;
}

function access_item_by_key(array $experiment, string $key): array
{
    foreach ($experiment['accessItems'] ?? [] as $item) {
        if (($item['key'] ?? '') === $key) {
            return $item;
        }
    }

    throw new RuntimeException('Access item not found: ' . $key);
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
    $pdo->exec('CREATE TABLE eligibility_field_values (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        eligibility_id INTEGER NOT NULL,
        field_id INTEGER NOT NULL,
        field_value TEXT NOT NULL,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(eligibility_id, field_id)
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
    $pdo->exec('CREATE TABLE randomization_runs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        experiment_id INTEGER NOT NULL,
        seed TEXT NOT NULL,
        total_students INTEGER NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE TABLE randomization_run_allocations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        run_id INTEGER NOT NULL,
        condition_id INTEGER NOT NULL,
        percentage REAL NOT NULL,
        assigned_count INTEGER NOT NULL
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
$serverStdoutPath = tempnam(sys_get_temp_dir(), 'experiment_server_stdout_');
$serverStderrPath = tempnam(sys_get_temp_dir(), 'experiment_server_stderr_');
if ($serverStdoutPath === false || $serverStderrPath === false) {
    throw new RuntimeException('Could not create temporary server log files.');
}
$GLOBALS['SERVER_STDOUT_PATH'] = $serverStdoutPath;
$GLOBALS['SERVER_STDERR_PATH'] = $serverStderrPath;

try {
    setup_sqlite_database($dbPath);

    $docRoot = realpath(__DIR__ . '/..');
    assert_true(is_string($docRoot) && $docRoot !== '', 'Invalid docroot for smoke test.');

    $port = random_int(18080, 18999);
    $baseUrl = 'http://127.0.0.1:' . $port;
    $command = [PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', $docRoot];

    $env = getenv();
    $env = is_array($env) ? $env : [];
    $env['EXPERIMENT_DB_DSN'] = 'sqlite:' . $dbPath;
    $env['EXPERIMENT_DB_USER'] = '';
    $env['EXPERIMENT_DB_PASSWORD'] = '';

    $process = proc_open(
        $command,
        [
            0 => ['pipe', 'r'],
            1 => ['file', $serverStdoutPath, 'a'],
            2 => ['file', $serverStderrPath, 'a'],
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

    $response = make_request($baseUrl, 'GET', '/api/manage/dashboard.php');
    assert_equals($response['status'], 200, 'dashboard should load after first claim');
    $aliceParticipation = dashboard_participation($response['body'] ?? [], 'alice@students.zhaw.ch', 1);
    assert_equals(count($aliceParticipation['accessItems'] ?? []), 2, 'dashboard participation should include access items');
    assert_equals(access_item_by_key($aliceParticipation, 'survey')['valueType'] ?? null, 'url', 'dashboard should expose URL access field type');
    assert_equals(access_item_by_key($aliceParticipation, 'survey')['label'] ?? null, 'Umfrage', 'dashboard should expose URL access field label');

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

    $response = make_request($baseUrl, 'GET', '/api/manage/dashboard.php');
    assert_equals($response['status'], 200, 'management dashboard should return 200');
    assert_equals($response['body']['allowedStudentCount'] ?? null, 3, 'dashboard should count allowed students');
    assert_equals(count($response['body']['allowedStudents'] ?? []), 3, 'dashboard should include allowed student list');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'delete_allowed_student',
        'email' => 'alice@students.zhaw.ch',
    ]);
    assert_equals($response['status'], 409, 'allowed student with participation should not be removed');
    assert_equals($response['body']['error_code'] ?? null, 'ALLOWED_STUDENT_HAS_PARTICIPATIONS', 'allowlist removal guard should be explicit');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'add_allowed_student',
        'email' => 'eve@students.zhaw.ch',
    ]);
    assert_equals($response['status'], 201, 'management should add removable allowed student');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'delete_allowed_student',
        'email' => 'eve@students.zhaw.ch',
    ]);
    assert_equals($response['status'], 200, 'management should remove allowed student without participations');
    assert_equals($response['body']['deleted'] ?? null, true, 'allowlist removal should report deletion');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'add_allowed_student',
        'email' => 'dana@students.zhaw.ch',
    ]);
    assert_equals($response['status'], 201, 'management should add allowed student');
    assert_equals($response['body']['created'] ?? null, true, 'allowed student should be created');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'add_allowed_student',
        'email' => 'erik@students.zhaw.ch',
    ]);
    assert_equals($response['status'], 201, 'management should add second allowed student');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_experiment',
        'name' => 'Conditionless Assigned Experiment',
        'description' => 'Condition mode selected before any conditions exist',
        'eligibilityMode' => 'all_allowed',
        'conditionMode' => 'assigned',
        'requiresTimeSlot' => false,
        'isOpen' => false,
        'sortOrder' => 25,
    ]);
    assert_equals($response['status'], 201, 'management should create conditionless assigned experiment');
    $conditionlessExperimentId = (int) ($response['body']['experimentId'] ?? 0);
    assert_true($conditionlessExperimentId > 0, 'conditionless experiment id should be present');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_access_field',
        'experimentId' => $conditionlessExperimentId,
        'conditionId' => null,
        'label' => 'Conditionless PID',
        'fieldKey' => 'conditionless_pid',
        'valueType' => 'pid',
        'valueSource' => 'pool',
        'isVisible' => true,
        'sortOrder' => 10,
    ]);
    assert_equals($response['status'], 201, 'management should create conditionless pool field');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'import_pool_rows',
        'experimentId' => $conditionlessExperimentId,
        'conditionId' => null,
        'table' => "conditionless_pid\nC001",
    ]);
    assert_equals($response['status'], 201, 'conditionless experiment should allow experiment-wide pool import');
    assert_equals($response['body']['imported'] ?? null, 1, 'one conditionless pool row should be imported');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_experiment',
        'name' => 'Managed Experiment',
        'description' => 'Created through management API',
        'eligibilityMode' => 'selected',
        'conditionMode' => 'assigned',
        'requiresTimeSlot' => false,
        'isOpen' => true,
        'sortOrder' => 30,
    ]);
    assert_equals($response['status'], 201, 'management should create experiment');
    $managedExperimentId = (int) ($response['body']['experimentId'] ?? 0);
    assert_true($managedExperimentId > 0, 'created experiment id should be present');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_condition',
        'experimentId' => $managedExperimentId,
        'name' => 'Managed A',
        'sortOrder' => 10,
    ]);
    assert_equals($response['status'], 201, 'management should create condition');
    $managedConditionId = (int) ($response['body']['conditionId'] ?? 0);
    assert_true($managedConditionId > 0, 'created condition id should be present');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_condition',
        'experimentId' => $managedExperimentId,
        'name' => 'Managed B',
        'sortOrder' => 20,
    ]);
    assert_equals($response['status'], 201, 'management should create second condition');
    $managedSecondConditionId = (int) ($response['body']['conditionId'] ?? 0);
    assert_true($managedSecondConditionId > 0, 'created second condition id should be present');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_eligibility_selection',
        'experimentId' => $managedExperimentId,
        'mode' => 'selected',
        'emails' => ['dana@students.zhaw.ch', 'erik@students.zhaw.ch'],
    ]);
    assert_equals($response['status'], 200, 'management should save selected participant subset');
    assert_equals($response['body']['selectedCount'] ?? null, 2, 'selected participant subset should include two students');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_condition_assignments',
        'experimentId' => $managedExperimentId,
        'source' => 'manual',
        'assignments' => [
            ['email' => 'dana@students.zhaw.ch', 'conditionId' => $managedConditionId],
            ['email' => 'erik@students.zhaw.ch', 'conditionId' => $managedSecondConditionId],
        ],
    ]);
    assert_equals($response['status'], 200, 'management should save condition assignments for selected students');
    assert_equals($response['body']['assignedCount'] ?? null, 2, 'two condition assignments should be saved');

    $response = make_request($baseUrl, 'GET', '/api/manage/dashboard.php');
    assert_equals($response['status'], 200, 'dashboard should load after participant and condition assignment');
    $managedDashboardExperiment = experiment_by_id($response['body'] ?? [], $managedExperimentId);
    assert_equals($managedDashboardExperiment['counts']['eligibilities'] ?? null, 2, 'dashboard should report selected participant count');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'clear_condition_assignments',
        'experimentId' => $managedExperimentId,
    ]);
    assert_equals($response['status'], 200, 'management should clear condition assignments before participations');

    $response = make_request($baseUrl, 'GET', '/api/manage/dashboard.php');
    assert_equals($response['status'], 200, 'dashboard should load after condition assignment clearing');
    $managedDashboardExperiment = experiment_by_id($response['body'] ?? [], $managedExperimentId);
    assert_equals($managedDashboardExperiment['counts']['eligibilities'] ?? null, 2, 'condition clearing should keep participant selection');
    assert_equals(dashboard_assigned_condition_count($response['body'] ?? [], $managedExperimentId), 0, 'condition clearing should remove every assigned condition');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_condition_assignments',
        'experimentId' => $managedExperimentId,
        'source' => 'manual',
        'assignments' => [
            ['email' => 'dana@students.zhaw.ch', 'conditionId' => $managedConditionId],
            ['email' => 'erik@students.zhaw.ch', 'conditionId' => $managedSecondConditionId],
        ],
    ]);
    assert_equals($response['status'], 200, 'management should restore condition assignments after clearing');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'clear_eligibility_selection',
        'experimentId' => $managedExperimentId,
    ]);
    assert_equals($response['status'], 200, 'management should clear participant selection before participations');
    assert_equals($response['body']['selectedCount'] ?? null, 0, 'participant clearing should report zero selected students');

    $response = make_request($baseUrl, 'GET', '/api/manage/dashboard.php');
    assert_equals($response['status'], 200, 'dashboard should load after participant selection clearing');
    $managedDashboardExperiment = experiment_by_id($response['body'] ?? [], $managedExperimentId);
    assert_equals($managedDashboardExperiment['eligibilityMode'] ?? null, 'selected', 'participant clearing should leave experiment in selected mode');
    assert_equals($managedDashboardExperiment['counts']['eligibilities'] ?? null, 0, 'participant clearing should remove experiment eligibilities');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_eligibility_selection',
        'experimentId' => $managedExperimentId,
        'mode' => 'selected',
        'emails' => ['dana@students.zhaw.ch', 'erik@students.zhaw.ch'],
    ]);
    assert_equals($response['status'], 200, 'management should restore participant subset after clearing');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_condition_assignments',
        'experimentId' => $managedExperimentId,
        'source' => 'manual',
        'assignments' => [
            ['email' => 'dana@students.zhaw.ch', 'conditionId' => $managedConditionId],
            ['email' => 'erik@students.zhaw.ch', 'conditionId' => $managedSecondConditionId],
        ],
    ]);
    assert_equals($response['status'], 200, 'management should restore assignments after participant selection clearing');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_access_field',
        'experimentId' => $managedExperimentId,
        'conditionId' => null,
        'label' => 'Global Pool ID',
        'fieldKey' => 'global_pool_id',
        'valueType' => 'pid',
        'valueSource' => 'pool',
        'isVisible' => true,
        'sortOrder' => 5,
    ]);
    assert_equals($response['status'], 201, 'management should create experiment-wide pool field');
    $globalPoolFieldId = (int) ($response['body']['fieldId'] ?? 0);
    assert_true($globalPoolFieldId > 0, 'experiment-wide pool field id should be present');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'import_pool_rows',
        'experimentId' => $managedExperimentId,
        'conditionId' => null,
        'table' => "global_pool_id\nG001",
    ]);
    assert_equals($response['status'], 422, 'conditioned experiments should reject experiment-wide pool imports');
    assert_equals($response['body']['error_code'] ?? null, 'CONDITION_POOL_REQUIRED', 'conditioned pool import guard should be explicit');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'delete_access_field',
        'fieldId' => $globalPoolFieldId,
    ]);
    assert_equals($response['status'], 200, 'unused experiment-wide pool field should be removable');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_access_field',
        'experimentId' => $managedExperimentId,
        'conditionId' => $managedConditionId,
        'label' => 'Managed PID',
        'fieldKey' => 'managed_pid',
        'valueType' => 'pid',
        'valueSource' => 'pool',
        'sharedValue' => 'should-not-be-stored',
        'isVisible' => true,
        'sortOrder' => 10,
    ]);
    assert_equals($response['status'], 201, 'management should create access field');
    $managedFieldId = (int) ($response['body']['fieldId'] ?? 0);
    assert_true($managedFieldId > 0, 'created field id should be present');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_access_field',
        'experimentId' => $managedExperimentId,
        'conditionId' => $managedConditionId,
        'label' => 'Staff Code',
        'fieldKey' => 'staff_code',
        'valueType' => 'text',
        'valueSource' => 'staff_entry',
        'sharedValue' => 'should-not-be-stored',
        'isVisible' => true,
        'sortOrder' => 20,
    ]);
    assert_equals($response['status'], 201, 'management should create staff-entry access field');
    $managedStaffFieldId = (int) ($response['body']['fieldId'] ?? 0);
    assert_true($managedStaffFieldId > 0, 'created staff-entry field id should be present');

    $response = make_request($baseUrl, 'GET', '/api/manage/dashboard.php');
    assert_equals($response['status'], 200, 'dashboard should load after access field creation');
    $managedField = dashboard_access_field_by_key($response['body'] ?? [], $managedExperimentId, 'managed_pid');
    assert_equals($managedField['sharedValue'] ?? null, null, 'pool fields should not store shared values');
    $managedStaffField = dashboard_access_field_by_key($response['body'] ?? [], $managedExperimentId, 'staff_code');
    assert_equals($managedStaffField['sharedValue'] ?? null, null, 'staff-entry fields should not store shared values');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_staff_eligibility_field_values',
        'experimentId' => $managedExperimentId,
        'rows' => [
            [
                'email' => 'dana@students.zhaw.ch',
                'values' => [
                    ['fieldId' => $managedStaffFieldId, 'value' => 'Staff-001'],
                ],
            ],
        ],
    ]);
    assert_equals($response['status'], 200, 'management should prepare staff-entered access field values');
    assert_equals($response['body']['savedCount'] ?? null, 1, 'one staff-entered access field should be prepared');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_staff_eligibility_field_values',
        'experimentId' => $managedExperimentId,
        'rows' => [
            [
                'email' => 'dana@students.zhaw.ch',
                'values' => [
                    ['fieldId' => $managedFieldId, 'value' => 'not-editable'],
                ],
            ],
        ],
    ]);
    assert_equals($response['status'], 422, 'pool fields should not be editable as staff-entry fields');
    assert_equals($response['body']['error_code'] ?? null, 'FIELD_NOT_STAFF_ENTRY', 'staff-entry field guard should be explicit');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'import_pool_rows',
        'experimentId' => $managedExperimentId,
        'conditionId' => $managedConditionId,
        'table' => "managed_pid\nM001",
    ]);
    assert_equals($response['status'], 201, 'management should import access pool rows');
    assert_true(is_array($response['body']), 'pool import response should be JSON: ' . $response['raw']);
    assert_equals($response['body']['imported'] ?? null, 1, 'one pool row should be imported');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'clear_access_pool_rows',
        'experimentId' => $managedExperimentId,
    ]);
    assert_equals($response['status'], 200, 'management should clear unassigned access pool rows');
    assert_equals($response['body']['deletedCount'] ?? null, 1, 'one unassigned pool row should be cleared');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'import_pool_rows',
        'experimentId' => $managedExperimentId,
        'conditionId' => $managedConditionId,
        'table' => "managed_pid\nM001",
    ]);
    assert_equals($response['status'], 201, 'management should re-import access pool rows after clearing');
    assert_equals($response['body']['imported'] ?? null, 1, 'one pool row should be re-imported');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'assign_student',
        'experimentId' => $managedExperimentId,
        'email' => 'dana@students.zhaw.ch',
        'conditionId' => $managedConditionId,
    ]);
    assert_equals($response['status'], 200, 'management should assign student eligibility');

    $response = make_request($baseUrl, 'POST', '/api/claim.php', [
        'email' => 'dana@students.zhaw.ch',
        'experimentId' => $managedExperimentId,
    ]);
    assert_equals($response['status'], 200, 'assigned student should claim managed experiment');
    $managedExperiment = experiment_by_id($response['body']['overview'] ?? [], $managedExperimentId);
    assert_equals($managedExperiment['condition']['id'] ?? null, $managedConditionId, 'claim should use assigned condition');
    assert_equals(access_item_by_key($managedExperiment, 'managed_pid')['value'] ?? null, 'M001', 'claim should expose imported pool value');
    assert_equals(access_item_by_key($managedExperiment, 'staff_code')['value'] ?? null, 'Staff-001', 'claim should expose prepared staff-entered value');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'clear_access_pool_rows',
        'experimentId' => $managedExperimentId,
    ]);
    assert_equals($response['status'], 409, 'management should not clear assigned access pool rows');
    assert_equals($response['body']['error_code'] ?? null, 'POOL_HAS_ASSIGNMENTS', 'assigned pool clear guard should be explicit');

    $response = make_request($baseUrl, 'GET', '/api/manage/dashboard.php');
    assert_equals($response['status'], 200, 'dashboard should load after managed claim');
    $managedParticipation = dashboard_participation($response['body'] ?? [], 'dana@students.zhaw.ch', $managedExperimentId);
    $managedParticipationId = (int) $managedParticipation['id'];
    assert_true(
        is_string($managedParticipation['assignedAt'] ?? null) && $managedParticipation['assignedAt'] !== '',
        'dashboard should expose the access reveal time for grading'
    );

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_staff_eligibility_field_values',
        'experimentId' => $managedExperimentId,
        'rows' => [
            [
                'email' => 'dana@students.zhaw.ch',
                'values' => [
                    ['fieldId' => $managedStaffFieldId, 'value' => 'late-change'],
                ],
            ],
        ],
    ]);
    assert_equals($response['status'], 409, 'prepared staff values should be locked after access reveal');
    assert_equals($response['body']['error_code'] ?? null, 'STAFF_VALUES_HAVE_PARTICIPATIONS', 'prepared staff value lock should be explicit');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_eligibility_selection',
        'experimentId' => $managedExperimentId,
        'mode' => 'selected',
        'emails' => ['erik@students.zhaw.ch'],
    ]);
    assert_equals($response['status'], 409, 'participant subset should keep students with participations');
    assert_equals($response['body']['error_code'] ?? null, 'ELIGIBILITY_SELECTION_HAS_PARTICIPATIONS', 'participant subset guard should be explicit');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_condition_assignments',
        'experimentId' => $managedExperimentId,
        'source' => 'manual',
        'assignments' => [
            ['email' => 'dana@students.zhaw.ch', 'conditionId' => $managedSecondConditionId],
        ],
    ]);
    assert_equals($response['status'], 409, 'condition assignment should not change after participation exists');
    assert_equals($response['body']['error_code'] ?? null, 'CONDITION_ASSIGNMENT_HAS_PARTICIPATION', 'condition assignment guard should be explicit');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'clear_eligibility_selection',
        'experimentId' => $managedExperimentId,
    ]);
    assert_equals($response['status'], 409, 'participant selection clearing should be blocked after participation exists');
    assert_equals($response['body']['error_code'] ?? null, 'ELIGIBILITY_SELECTION_HAS_PARTICIPATIONS', 'participant clearing guard should be explicit');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'clear_condition_assignments',
        'experimentId' => $managedExperimentId,
    ]);
    assert_equals($response['status'], 409, 'condition assignment clearing should be blocked after participation exists');
    assert_equals($response['body']['error_code'] ?? null, 'CONDITION_ASSIGNMENT_HAS_PARTICIPATIONS', 'condition clearing guard should be explicit');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'toggle_confirmation',
        'participationId' => $managedParticipationId,
    ]);
    assert_equals($response['status'], 200, 'management should toggle confirmation');
    assert_equals($response['body']['confirmed'] ?? null, true, 'confirmation should be enabled');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_appointment',
        'participationId' => $managedParticipationId,
        'appointmentText' => '09:30',
    ]);
    assert_equals($response['status'], 200, 'management should save appointment text');

    $response = make_request($baseUrl, 'GET', '/api/student_overview.php?email=dana%40students.zhaw.ch');
    assert_equals($response['status'], 200, 'student should retrieve managed assignment');
    $managedExperiment = experiment_by_id($response['body'] ?? [], $managedExperimentId);
    assert_equals($managedExperiment['confirmed'] ?? null, true, 'student overview should show confirmation');
    assert_equals($managedExperiment['canViewAccess'] ?? null, false, 'confirmed participations should not expose access button');
    assert_equals(count($managedExperiment['accessItems'] ?? []), 0, 'confirmed participations should not expose access data');
    assert_equals($managedExperiment['appointmentText'] ?? null, '09:30', 'student overview should show appointment text');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'delete_condition',
        'conditionId' => $managedConditionId,
    ]);
    assert_equals($response['status'], 409, 'condition with participation should not be deleted ' . $response['serverOutput']);
    assert_equals($response['body']['error_code'] ?? null, 'CONDITION_HAS_PARTICIPATIONS', 'condition delete guard should be explicit');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'delete_access_field',
        'fieldId' => $managedFieldId,
    ]);
    assert_equals($response['status'], 409, 'assigned access field should not be deleted');
    assert_equals($response['body']['error_code'] ?? null, 'FIELD_HAS_RUNTIME_VALUES', 'field delete guard should be explicit');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'reset_participation',
        'participationId' => $managedParticipationId,
        'releaseAccess' => true,
    ]);
    assert_equals($response['status'], 200, 'management should reset participation');
    assert_equals($response['body']['releasedAccess'] ?? null, true, 'reset should release access row');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'delete_access_field',
        'fieldId' => $managedFieldId,
    ]);
    assert_equals($response['status'], 200, 'unused access field should be deleted after reset');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_experiment',
        'name' => 'Randomized Experiment',
        'description' => 'Created through management API',
        'eligibilityMode' => 'selected',
        'conditionMode' => 'assigned',
        'requiresTimeSlot' => false,
        'isOpen' => false,
        'sortOrder' => 40,
    ]);
    assert_equals($response['status'], 201, 'management should create randomization experiment');
    $randomExperimentId = (int) ($response['body']['experimentId'] ?? 0);

    $conditionIds = [];
    foreach (['A', 'B'] as $conditionName) {
        $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
            'action' => 'save_condition',
            'experimentId' => $randomExperimentId,
            'name' => $conditionName,
            'sortOrder' => count($conditionIds) * 10,
        ]);
        assert_equals($response['status'], 201, 'management should create randomization condition');
        $conditionIds[] = (int) ($response['body']['conditionId'] ?? 0);
    }

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'randomize',
        'experimentId' => $randomExperimentId,
        'seed' => 'fixed-seed',
        'allocations' => [
            ['conditionId' => $conditionIds[0], 'percentage' => 50],
            ['conditionId' => $conditionIds[1], 'percentage' => 50],
        ],
    ]);
    assert_equals($response['status'], 200, 'management should randomize eligible students');
    assert_equals($response['body']['totalStudents'] ?? null, 5, 'randomization should include all allowed students');

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
    if (is_file($serverStdoutPath)) {
        unlink($serverStdoutPath);
    }
    if (is_file($serverStderrPath)) {
        unlink($serverStderrPath);
    }
}
