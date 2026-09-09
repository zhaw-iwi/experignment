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

function make_request(
    string $baseUrl,
    string $method,
    string $path,
    ?array $jsonBody = null,
    bool $includeAuthHeaders = true
): array
{
    $headers = ['Accept: application/json'];
    $cookies = $GLOBALS['HTTP_COOKIES'] ?? [];
    if (is_array($cookies) && $cookies !== []) {
        $cookieValues = [];
        foreach ($cookies as $name => $value) {
            $cookieValues[] = $name . '=' . $value;
        }
        $headers[] = 'Cookie: ' . implode('; ', $cookieValues);
    }
    if ($jsonBody !== null) {
        $headers[] = 'Content-Type: application/json';
    }
    if ($includeAuthHeaders && strtoupper($method) !== 'GET') {
        $tokenKey = str_starts_with($path, '/api/manage/') ? 'ADMIN_CSRF_TOKEN' : 'STUDENT_CSRF_TOKEN';
        $csrfToken = $GLOBALS[$tokenKey] ?? '';
        if (is_string($csrfToken) && $csrfToken !== '') {
            $headers[] = 'X-CSRF-Token: ' . $csrfToken;
        }
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
    foreach ($responseHeaders as $responseHeader) {
        if (preg_match('/^Set-Cookie:\s*([^=;\s]+)=([^;]*)/i', $responseHeader, $matches) !== 1) {
            continue;
        }
        if ($matches[2] === '') {
            unset($GLOBALS['HTTP_COOKIES'][$matches[1]]);
        } else {
            $GLOBALS['HTTP_COOKIES'][$matches[1]] = $matches[2];
        }
    }

    $decoded = json_decode($rawBody, true);

    return [
        'status' => $status,
        'body' => is_array($decoded) ? $decoded : null,
        'raw' => $rawBody,
        'serverOutput' => read_server_output(),
    ];
}

function make_form_request(string $baseUrl, string $path, array $fields): array
{
    $headers = [
        'Accept: text/html,text/plain',
        'Content-Type: application/x-www-form-urlencoded',
    ];
    $cookies = $GLOBALS['HTTP_COOKIES'] ?? [];
    if (is_array($cookies) && $cookies !== []) {
        $cookieValues = [];
        foreach ($cookies as $name => $value) {
            $cookieValues[] = $name . '=' . $value;
        }
        $headers[] = 'Cookie: ' . implode('; ', $cookieValues);
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => http_build_query($fields),
            'ignore_errors' => true,
            'timeout' => 10,
        ],
    ]);
    $rawBody = @file_get_contents($baseUrl . $path, false, $context);
    $rawBody = is_string($rawBody) ? $rawBody : '';
    $responseHeaders = $http_response_header ?? [];
    $status = 0;
    if (isset($responseHeaders[0]) && preg_match('/\s(\d{3})\s/', $responseHeaders[0], $matches) === 1) {
        $status = (int) $matches[1];
    }
    foreach ($responseHeaders as $responseHeader) {
        if (preg_match('/^Set-Cookie:\s*([^=;\s]+)=([^;]*)/i', $responseHeader, $matches) !== 1) {
            continue;
        }
        if ($matches[2] === '') {
            unset($GLOBALS['HTTP_COOKIES'][$matches[1]]);
        } else {
            $GLOBALS['HTTP_COOKIES'][$matches[1]] = $matches[2];
        }
    }

    return [
        'status' => $status,
        'raw' => $rawBody,
        'serverOutput' => read_server_output(),
    ];
}

function login_student(string $baseUrl, string $email, string $accessCode): array
{
    $response = make_request($baseUrl, 'POST', '/api/student_login.php', [
        'email' => $email,
        'accessCode' => $accessCode,
    ]);
    assert_equals($response['status'], 200, 'student login should return 200');
    $GLOBALS['STUDENT_CSRF_TOKEN'] = (string) ($response['body']['csrfToken'] ?? '');
    assert_true($GLOBALS['STUDENT_CSRF_TOKEN'] !== '', 'student login should return a CSRF token');

    return $response;
}

function login_admin(string $baseUrl, string $accessCode): array
{
    $response = make_request($baseUrl, 'POST', '/api/manage/login.php', [
        'accessCode' => $accessCode,
    ]);
    assert_equals($response['status'], 200, 'admin login should return 200');
    $GLOBALS['ADMIN_CSRF_TOKEN'] = (string) ($response['body']['csrfToken'] ?? '');
    assert_true($GLOBALS['ADMIN_CSRF_TOKEN'] !== '', 'admin login should return a CSRF token');

    return $response;
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

function report_row_by_code(array $report, string $studentCode): array
{
    foreach ($report['rows'] ?? [] as $row) {
        if (($row['studentCode'] ?? '') === $studentCode) {
            return $row;
        }
    }

    throw new RuntimeException('Report row not found: ' . $studentCode);
}

function report_value_for_experiment(array $report, array $row, int $experimentId): int
{
    foreach ($report['columns'] ?? [] as $column) {
        if (($column['experimentId'] ?? null) === $experimentId) {
            return (int) (($row['values'] ?? [])[$column['key']] ?? 0);
        }
    }

    throw new RuntimeException('Report column not found for experiment: ' . $experimentId);
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

function overview_has_experiment(array $overview, int $experimentId): bool
{
    foreach ($overview['experiments'] ?? [] as $experiment) {
        if (($experiment['id'] ?? null) === $experimentId) {
            return true;
        }
    }

    return false;
}

function dashboard_group_by_name(array $dashboard, string $name): array
{
    foreach ($dashboard['studentGroups'] ?? [] as $group) {
        if (($group['name'] ?? '') === $name) {
            return $group;
        }
    }

    throw new RuntimeException('Student group not found in dashboard: ' . $name);
}

function dashboard_student_by_email(array $dashboard, string $email): array
{
    foreach ($dashboard['allowedStudents'] ?? [] as $student) {
        if (($student['email'] ?? '') === $email) {
            return $student;
        }
    }

    throw new RuntimeException('Student not found in dashboard: ' . $email);
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

    $pdo->exec('CREATE TABLE student_groups (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        max_credits REAL NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE TABLE allowed_students (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_email TEXT NOT NULL UNIQUE,
        group_id INTEGER NOT NULL,
        login_code_hash TEXT NULL,
        login_code_version INTEGER NOT NULL DEFAULT 0,
        login_code_set_at TEXT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE TABLE authentication_throttles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        subject_type TEXT NOT NULL,
        subject_hash TEXT NOT NULL,
        failed_attempts INTEGER NOT NULL DEFAULT 0,
        window_started_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        locked_until TEXT NULL,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(subject_type, subject_hash)
    )');
    $pdo->exec('CREATE TABLE experiments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        public_name TEXT NOT NULL,
        description TEXT NULL,
        admin_notes TEXT NULL,
        is_open INTEGER NOT NULL DEFAULT 0,
        opens_at TEXT NULL,
        closes_at TEXT NULL,
        max_participants INTEGER NULL,
        reward_credits REAL NOT NULL DEFAULT 1,
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
    $pdo->exec('CREATE TABLE experiment_group_eligibilities (
        experiment_id INTEGER NOT NULL,
        group_id INTEGER NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(experiment_id, group_id)
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
        reward_credits_snapshot REAL NULL,
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
        is_undated INTEGER NOT NULL DEFAULT 0,
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
    $pdo->exec('CREATE TABLE audit_events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        actor_type TEXT NOT NULL,
        actor_identifier TEXT NULL,
        action TEXT NOT NULL,
        entity_type TEXT NULL,
        entity_identifier TEXT NULL,
        details_json TEXT NULL,
        ip_address TEXT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec("INSERT INTO student_groups (id, name, max_credits) VALUES (1, 'Course A', 4), (2, 'Course B', 6)");
    $studentCodes = [
        'alice@students.zhaw.ch' => ['code' => 'alice1', 'groupId' => 1],
        'bob@students.zhaw.ch' => ['code' => 'bob22', 'groupId' => 1],
        'charlie@students.zhaw.ch' => ['code' => 'charlie3', 'groupId' => 2],
    ];
    foreach ($studentCodes as $email => $studentSetup) {
        $pdo->prepare(
            'INSERT INTO allowed_students
                (student_email, group_id, login_code_hash, login_code_version, login_code_set_at)
             VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP)'
        )->execute([$email, $studentSetup['groupId'], password_hash($studentSetup['code'], PASSWORD_DEFAULT)]);
    }

    $pdo->exec("INSERT INTO experiments
        (id, public_name, description, is_open, max_participants, reward_credits, eligibility_mode, condition_mode, requires_time_slot, sort_order)
        VALUES
        (1, 'Experiment 1', 'Choice flow', 1, NULL, 1.5, 'all_allowed', 'student_choice', 0, 10),
        (2, 'Experiment 3', 'Slot flow', 1, 2, 2, 'all_allowed', 'none', 1, 20)");
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
        (id, experiment_id, label, starts_at, ends_at, capacity, is_active, is_undated, sort_order)
        VALUES
        (1, 2, 'Montag Vormittag', '2026-06-01 08:00:00', '2026-06-01 12:00:00', 1, 1, 0, 10)");
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
$GLOBALS['HTTP_COOKIES'] = [];
$GLOBALS['ADMIN_CSRF_TOKEN'] = '';
$GLOBALS['STUDENT_CSRF_TOKEN'] = '';

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
    $env['ADMIN_ACCESS_CODE_HASH'] = password_hash('AdminAccess123', PASSWORD_DEFAULT);
    $env['APP_SESSION_NAME'] = 'experiment_assignment_smoke_test';
    $env['APP_SESSION_SECURE'] = '0';
    $env['PREFLIGHT_ENABLED'] = '1';

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
    assert_equals($response['body']['version'] ?? null, 3, 'bootstrap should expose V3');

    $response = make_request($baseUrl, 'GET', '/scripts/deployment_preflight.php');
    assert_equals($response['status'], 404, 'CLI preflight should reject direct web access');

    $response = make_request($baseUrl, 'GET', '/preflight/index.php');
    assert_equals($response['status'], 200, 'enabled browser preflight should show its login form');
    assert_true(str_contains($response['raw'], 'Deployment preflight'), 'browser preflight should render its heading');
    assert_true(
        preg_match('/name="csrf_token" value="([a-f0-9]{64})"/', $response['raw'], $preflightCsrfMatch) === 1,
        'browser preflight should render a CSRF token'
    );
    $preflightCsrf = $preflightCsrfMatch[1];

    $response = make_form_request($baseUrl, '/preflight/index.php', [
        'csrf_token' => $preflightCsrf,
        'access_code' => 'WrongAdmin1',
        'expect_empty' => '1',
    ]);
    assert_equals($response['status'], 401, 'browser preflight should reject the wrong administrator code');
    assert_true(str_contains($response['raw'], 'Authentication failed.'), 'browser preflight failure should be generic');

    $response = make_form_request($baseUrl, '/preflight/index.php', [
        'csrf_token' => $preflightCsrf,
        'access_code' => 'AdminAccess123',
        'expect_empty' => '1',
    ]);
    assert_equals($response['status'], 200, 'browser preflight should run after administrator authentication');
    assert_true(str_contains($response['raw'], 'Preflight result:'), 'browser preflight should render the shared result');
    assert_true(
        str_contains($response['raw'], 'Production database driver must be mysql; detected sqlite.'),
        'browser preflight should run the same driver check as the CLI'
    );

    $response = make_request($baseUrl, 'GET', '/api/student_overview.php');
    assert_equals($response['status'], 401, 'overview should require student authentication');

    $response = make_request($baseUrl, 'POST', '/api/student_login.php', [
        'email' => 'alice@students.zhaw.ch',
        'accessCode' => 'wrong1',
    ]);
    assert_equals($response['status'], 401, 'incorrect student code should be rejected');
    assert_equals($response['body']['error_code'] ?? null, 'AUTHENTICATION_FAILED', 'student login failure should be generic');

    login_student($baseUrl, 'alice@students.zhaw.ch', 'alice1');
    $response = make_request($baseUrl, 'GET', '/api/student_overview.php?email=bob%40students.zhaw.ch');
    assert_equals($response['status'], 200, 'overview should return 200');
    assert_equals($response['body']['email'] ?? null, 'alice@students.zhaw.ch', 'overview identity should come from the session');
    assert_equals(count($response['body']['experiments'] ?? []), 2, 'overview should include two experiments');

    $response = make_request($baseUrl, 'GET', '/api/manage/dashboard.php');
    assert_equals($response['status'], 401, 'dashboard should require admin authentication');

    $response = make_request($baseUrl, 'POST', '/api/manage/login.php', ['accessCode' => 'wrong1']);
    assert_equals($response['status'], 401, 'incorrect admin code should be rejected');
    assert_equals($response['body']['error_code'] ?? null, 'AUTHENTICATION_FAILED', 'admin login failure should be generic');
    login_admin($baseUrl, 'AdminAccess123');

    $response = make_request(
        $baseUrl,
        'POST',
        '/api/manage/actions.php',
        ['action' => 'unknown'],
        false
    );
    assert_equals($response['status'], 403, 'management writes should require a CSRF token');
    assert_equals($response['body']['error_code'] ?? null, 'CSRF_TOKEN_INVALID', 'missing admin CSRF token should be explicit');

    $response = make_request(
        $baseUrl,
        'POST',
        '/api/claim.php',
        ['experimentId' => 1],
        false
    );
    assert_equals($response['status'], 403, 'student writes should require a CSRF token');
    assert_equals($response['body']['error_code'] ?? null, 'CSRF_TOKEN_INVALID', 'missing student CSRF token should be explicit');

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

    $response = make_request($baseUrl, 'GET', '/api/manage/report.php');
    assert_equals($response['status'], 200, 'report should return 200');
    assert_equals(count($response['body']['columns'] ?? []), 6, 'report should include identity, course credit, and experiment columns');
    $aliceReportRow = report_row_by_code($response['body'] ?? [], 'alice');
    assert_equals($aliceReportRow['email'] ?? null, 'alice@students.zhaw.ch', 'report row should retain source email');
    assert_equals($aliceReportRow['groupName'] ?? null, 'Course A', 'report row should expose the student course');
    assert_equals(report_value_for_experiment($response['body'] ?? [], $aliceReportRow, 1), 0, 'unconfirmed claim should not count as approved');

    login_student($baseUrl, 'bob@students.zhaw.ch', 'bob22');
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

    login_student($baseUrl, 'charlie@students.zhaw.ch', 'charlie3');
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
    assert_equals($response['status'], 200, 'dashboard should load before bulk grading operations');
    $bobParticipation = dashboard_participation($response['body'] ?? [], 'bob@students.zhaw.ch', 2);
    $charlieParticipation = dashboard_participation($response['body'] ?? [], 'charlie@students.zhaw.ch', 2);
    $bobParticipationId = (int) ($bobParticipation['id'] ?? 0);
    $charlieParticipationId = (int) ($charlieParticipation['id'] ?? 0);
    assert_true($bobParticipationId > 0, 'bob participation id should exist');
    assert_true($charlieParticipationId > 0, 'charlie participation id should exist');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'bulk_grading_operation',
        'experimentId' => 2,
        'operation' => 'confirm',
        'participationIds' => [$bobParticipationId, $charlieParticipationId],
    ]);
    assert_equals($response['status'], 200, 'bulk grading should confirm selected participations');
    assert_equals($response['body']['affectedCount'] ?? null, 2, 'bulk confirmation should affect two participations');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'bulk_grading_operation',
        'experimentId' => 2,
        'operation' => 'unconfirm',
        'participationIds' => [$charlieParticipationId],
    ]);
    assert_equals($response['status'], 200, 'bulk grading should remove selected confirmations');
    assert_equals($response['body']['affectedCount'] ?? null, 1, 'bulk unconfirmation should affect one participation');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'bulk_grading_operation',
        'experimentId' => 1,
        'operation' => 'confirm',
        'participationIds' => [$bobParticipationId],
    ]);
    assert_equals($response['status'], 422, 'bulk grading should reject participations from another experiment');
    assert_equals($response['body']['error_code'] ?? null, 'PARTICIPATION_SCOPE_MISMATCH', 'bulk grading scope guard should be explicit');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'bulk_grading_operation',
        'experimentId' => 2,
        'operation' => 'reset',
        'participationIds' => [$charlieParticipationId],
    ]);
    assert_equals($response['status'], 200, 'bulk grading should reset selected participations');
    assert_equals($response['body']['affectedCount'] ?? null, 1, 'bulk reset should affect one participation');
    assert_equals($response['body']['releasedAccessCount'] ?? null, 1, 'bulk reset should release one access row');

    $response = make_request($baseUrl, 'GET', '/api/manage/dashboard.php');
    assert_equals($response['status'], 200, 'management dashboard should return 200');
    assert_equals($response['body']['allowedStudentCount'] ?? null, 3, 'dashboard should count allowed students');
    assert_equals(count($response['body']['allowedStudents'] ?? []), 3, 'dashboard should include allowed student list');
    assert_equals(count($response['body']['studentGroups'] ?? []), 2, 'dashboard should include course groups');
    $aliceStudent = dashboard_student_by_email($response['body'] ?? [], 'alice@students.zhaw.ch');
    assert_equals($aliceStudent['group']['name'] ?? null, 'Course A', 'dashboard should expose student course membership');
    assert_equals($aliceStudent['loginCodeSet'] ?? null, true, 'dashboard should expose code completeness');
    assert_true(!array_key_exists('login_code_hash', $aliceStudent), 'dashboard must never expose student password hashes');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'delete_allowed_student',
        'email' => 'alice@students.zhaw.ch',
    ]);
    assert_equals($response['status'], 409, 'allowed student with participation should not be removed');
    assert_equals($response['body']['error_code'] ?? null, 'ALLOWED_STUDENT_HAS_PARTICIPATIONS', 'allowlist removal guard should be explicit');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'add_allowed_student',
        'email' => 'eve@students.zhaw.ch',
        'groupId' => 1,
    ]);
    assert_equals($response['status'], 201, 'management should add removable allowed student');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'delete_allowed_student',
        'email' => 'eve@students.zhaw.ch',
    ]);
    assert_equals($response['status'], 200, 'management should remove allowed student without participations');
    assert_equals($response['body']['deleted'] ?? null, true, 'allowlist removal should report deletion');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_experiment',
        'name' => 'Incomplete Open Experiment',
        'maxParticipants' => 1,
        'rewardCredits' => 1,
        'audienceMode' => 'selected_groups',
        'groupIds' => [1],
        'eligibilityMode' => 'selected',
        'conditionMode' => 'none',
        'requiresTimeSlot' => false,
        'isOpen' => true,
        'sortOrder' => 21,
    ]);
    assert_equals($response['status'], 409, 'readiness errors should block opening an experiment');
    assert_equals($response['body']['error_code'] ?? null, 'EXPERIMENT_NOT_READY', 'readiness rejection should be explicit');
    assert_true(count($response['body']['details']['issues'] ?? []) > 0, 'readiness rejection should include actionable issues');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_experiment',
        'name' => 'Course B Capacity Experiment',
        'description' => 'Course-targeted availability and capacity flow',
        'adminNotes' => 'This note must remain administrator-only.',
        'opensAt' => '2020-01-01 00:00:00',
        'closesAt' => '2099-12-31 23:59:59',
        'maxParticipants' => 1,
        'rewardCredits' => 3,
        'audienceMode' => 'selected_groups',
        'groupIds' => [2],
        'eligibilityMode' => 'all_allowed',
        'conditionMode' => 'none',
        'requiresTimeSlot' => false,
        'isOpen' => true,
        'sortOrder' => 22,
    ]);
    assert_equals($response['status'], 201, 'a complete course-targeted experiment should open');
    assert_equals($response['body']['readiness']['ready'] ?? null, true, 'course-targeted experiment should be ready');
    $courseExperimentId = (int) ($response['body']['experimentId'] ?? 0);

    login_student($baseUrl, 'alice@students.zhaw.ch', 'alice1');
    $response = make_request($baseUrl, 'GET', '/api/student_overview.php');
    assert_true(!overview_has_experiment($response['body'] ?? [], $courseExperimentId), 'student outside selected course must not see experiment');

    login_student($baseUrl, 'charlie@students.zhaw.ch', 'charlie3');
    $response = make_request($baseUrl, 'GET', '/api/student_overview.php');
    assert_true(overview_has_experiment($response['body'] ?? [], $courseExperimentId), 'student in selected course should see experiment');
    $courseExperiment = experiment_by_id($response['body'] ?? [], $courseExperimentId);
    assert_equals($courseExperiment['rewardCredits'] ?? null, 3, 'student should see experiment reward');
    assert_equals($courseExperiment['maxParticipants'] ?? null, 1, 'student should see participant maximum');
    assert_true(!array_key_exists('adminNotes', $courseExperiment), 'student payload must not expose administrator notes');

    $response = make_request($baseUrl, 'POST', '/api/claim.php', ['experimentId' => $courseExperimentId]);
    assert_equals($response['status'], 200, 'student in selected course should claim targeted experiment');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_experiment',
        'id' => $courseExperimentId,
        'name' => 'Course B Capacity Experiment',
        'description' => 'Course-targeted availability and capacity flow',
        'adminNotes' => 'This note must remain administrator-only.',
        'opensAt' => '2020-01-01 00:00:00',
        'closesAt' => '2099-12-31 23:59:59',
        'maxParticipants' => 1,
        'rewardCredits' => 3,
        'audienceMode' => 'selected_groups',
        'groupIds' => [1],
        'eligibilityMode' => 'all_allowed',
        'conditionMode' => 'none',
        'requiresTimeSlot' => false,
        'isOpen' => true,
        'sortOrder' => 22,
    ]);
    assert_equals($response['status'], 409, 'course audience must retain groups with existing participations');
    assert_equals($response['body']['error_code'] ?? null, 'AUDIENCE_HAS_PARTICIPATIONS', 'course audience participation guard should be explicit');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_student_group',
        'name' => 'Course C',
        'maxCredits' => 8,
    ]);
    assert_equals($response['status'], 201, 'management should create a course group');
    $courseCId = (int) ($response['body']['id'] ?? 0);
    assert_true($courseCId > 0, 'created course group id should be present');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'import_student_roster',
        'roster' => "email;group\nfrank@students.zhaw.ch;Course C\ngrace@students.zhaw.ch;Imported Course",
    ]);
    assert_equals($response['status'], 201, 'management should import a grouped roster');
    assert_equals($response['body']['created'] ?? null, 2, 'roster import should create two students');
    assert_equals($response['body']['createdGroups'] ?? null, 1, 'roster import should create an unknown course');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'import_student_roster',
        'roster' => "group,email\nCourse C,frank@students.zhaw.ch\nCourse C,grace@students.zhaw.ch",
    ]);
    assert_equals($response['status'], 201, 'repeated roster import should upsert students');
    assert_equals($response['body']['updated'] ?? null, 1, 'repeated roster import should update changed course membership');
    assert_equals($response['body']['unchanged'] ?? null, 1, 'repeated roster import should report unchanged membership');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'delete_student_group',
        'groupId' => $courseCId,
    ]);
    assert_equals($response['status'], 409, 'management should not delete a course group that still has students');
    assert_equals($response['body']['error_code'] ?? null, 'STUDENT_GROUP_IN_USE', 'course usage guard should be explicit');

    foreach (['frank@students.zhaw.ch', 'grace@students.zhaw.ch'] as $temporaryEmail) {
        $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
            'action' => 'delete_allowed_student',
            'email' => $temporaryEmail,
        ]);
        assert_equals($response['status'], 200, 'temporary roster student should be removable');
    }
    $response = make_request($baseUrl, 'GET', '/api/manage/dashboard.php');
    $importedCourseId = (int) (dashboard_group_by_name($response['body'] ?? [], 'Imported Course')['id'] ?? 0);
    foreach ([$courseCId, $importedCourseId] as $temporaryGroupId) {
        $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
            'action' => 'delete_student_group',
            'groupId' => $temporaryGroupId,
        ]);
        assert_equals($response['status'], 200, 'unused temporary course group should be removable');
    }

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'add_allowed_student',
        'email' => 'dana@students.zhaw.ch',
        'groupId' => 1,
    ]);
    assert_equals($response['status'], 201, 'management should add allowed student');
    assert_equals($response['body']['created'] ?? null, true, 'allowed student should be created');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'add_allowed_student',
        'email' => 'erik@students.zhaw.ch',
        'groupId' => 2,
    ]);
    assert_equals($response['status'], 201, 'management should add second allowed student');

    $response = make_request($baseUrl, 'POST', '/api/manage/generate_student_codes.php', []);
    assert_equals($response['status'], 200, 'management should generate missing access codes as CSV');
    assert_true($response['body'] === null, 'student access-code response should be CSV rather than JSON');
    assert_true(str_contains($response['raw'], 'email;access_code'), 'student code CSV should contain its header');
    $generatedCodeMatch = [];
    assert_true(
        preg_match('/dana@students\.zhaw\.ch;((?=[a-z0-9]{5}(?:\r?\n|$))(?=[a-z0-9]*[a-z])(?=[a-z0-9]*[0-9])[a-z0-9]{5})/', $response['raw'], $generatedCodeMatch) === 1,
        'generated Dana code should be five lowercase alphanumeric characters with a letter and digit'
    );
    $generatedErikMatch = [];
    assert_true(
        preg_match('/erik@students\.zhaw\.ch;([a-z0-9]{5})/', $response['raw'], $generatedErikMatch) === 1,
        'student code CSV should include every student whose code was missing'
    );
    $verificationPdo = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $hashStatement = $verificationPdo->prepare('SELECT login_code_hash FROM allowed_students WHERE student_email = ?');
    $hashStatement->execute(['dana@students.zhaw.ch']);
    $storedCodeHash = (string) ($hashStatement->fetchColumn() ?: '');
    assert_true($storedCodeHash !== ($generatedCodeMatch[1] ?? ''), 'plaintext generated code must not be stored');
    assert_true(password_verify((string) ($generatedCodeMatch[1] ?? ''), $storedCodeHash), 'stored hash should verify the one-time generated code');
    $hashStatement->closeCursor();
    unset($hashStatement, $verificationPdo);

    $response = make_request($baseUrl, 'POST', '/api/manage/generate_student_codes.php', []);
    assert_equals($response['status'], 409, 'code generation should not reveal or replace existing codes');
    assert_equals($response['body']['error_code'] ?? null, 'NO_MISSING_ACCESS_CODES', 'no-missing-code response should be explicit');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'set_student_login_code',
        'email' => 'dana@students.zhaw.ch',
        'accessCode' => 'letters',
    ]);
    assert_equals($response['status'], 422, 'manual access code should require a digit');
    assert_equals($response['body']['error_code'] ?? null, 'INVALID_ACCESS_CODE', 'manual code validation should be explicit');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'set_student_login_code',
        'email' => 'dana@students.zhaw.ch',
        'accessCode' => 'Dana44',
    ]);
    assert_equals($response['status'], 200, 'management should manually set a student access code');

    login_student($baseUrl, 'erik@students.zhaw.ch', (string) ($generatedErikMatch[1] ?? ''));
    $response = make_request($baseUrl, 'POST', '/api/claim.php', ['experimentId' => $courseExperimentId]);
    assert_equals($response['status'], 409, 'participant maximum should reject additional claims');
    assert_equals($response['body']['error_code'] ?? null, 'EXPERIMENT_FULL', 'full experiment response should be explicit');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_slot',
        'experimentId' => 2,
        'label' => 'Kein passender Termin',
        'capacity' => 10,
        'isUndated' => true,
        'isActive' => true,
        'sortOrder' => 99,
    ]);
    assert_equals($response['status'], 201, 'management should create an explicit undated slot');
    $undatedSlotId = (int) ($response['body']['slotId'] ?? 0);

    login_student($baseUrl, 'charlie@students.zhaw.ch', 'charlie3');
    $response = make_request($baseUrl, 'POST', '/api/claim.php', ['experimentId' => 2]);
    assert_equals($response['status'], 200, 'student should reclaim slot experiment after reset');
    $response = make_request($baseUrl, 'POST', '/api/choose_slot.php', [
        'experimentId' => 2,
        'slotId' => $undatedSlotId,
    ]);
    assert_equals($response['status'], 200, 'student should choose explicit undated slot');
    $slotExperiment = experiment_by_id($response['body']['overview'] ?? [], 2);
    assert_equals($slotExperiment['slotChoice']['isUndated'] ?? null, true, 'student payload should identify undated slot explicitly');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_experiment',
        'name' => 'Future Availability Experiment',
        'opensAt' => '2099-01-01 00:00:00',
        'closesAt' => '2099-12-31 23:59:59',
        'maxParticipants' => 1,
        'rewardCredits' => 1,
        'audienceMode' => 'selected_groups',
        'groupIds' => [1],
        'eligibilityMode' => 'all_allowed',
        'conditionMode' => 'none',
        'requiresTimeSlot' => false,
        'isOpen' => true,
        'sortOrder' => 24,
    ]);
    assert_equals($response['status'], 201, 'future scheduled experiment should pass configuration readiness');
    $futureExperimentId = (int) ($response['body']['experimentId'] ?? 0);
    login_student($baseUrl, 'dana@students.zhaw.ch', 'Dana44');
    $response = make_request($baseUrl, 'GET', '/api/student_overview.php');
    $futureExperiment = experiment_by_id($response['body'] ?? [], $futureExperimentId);
    assert_equals($futureExperiment['configuredOpen'] ?? null, true, 'future experiment should retain manual release state');
    assert_equals($futureExperiment['isOpen'] ?? null, false, 'future experiment should not yet be available');
    $response = make_request($baseUrl, 'POST', '/api/claim.php', ['experimentId' => $futureExperimentId]);
    assert_equals($response['status'], 409, 'future availability should be enforced on claim');
    assert_equals($response['body']['error_code'] ?? null, 'EXPERIMENT_CLOSED', 'future availability response should be explicit');
    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'delete_experiment',
        'experimentId' => $futureExperimentId,
    ]);
    assert_equals($response['status'], 200, 'unused future experiment should be removable after schedule test');

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
        'maxParticipants' => 1,
        'rewardCredits' => 5,
        'audienceMode' => 'all_groups',
        'groupIds' => [],
        'adminNotes' => 'Internal smoke-test note',
        'isOpen' => false,
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

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_experiment',
        'id' => $managedExperimentId,
        'name' => 'Managed Experiment',
        'description' => 'Created through management API',
        'adminNotes' => 'Internal smoke-test note',
        'eligibilityMode' => 'selected',
        'conditionMode' => 'assigned',
        'requiresTimeSlot' => false,
        'maxParticipants' => 1,
        'rewardCredits' => 5,
        'audienceMode' => 'all_groups',
        'groupIds' => [],
        'isOpen' => true,
        'sortOrder' => 30,
    ]);
    assert_equals($response['status'], 200, 'ready experiment should open');
    assert_equals($response['body']['readiness']['ready'] ?? null, true, 'ready experiment should pass validation');

    login_student($baseUrl, 'dana@students.zhaw.ch', 'Dana44');
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
    $managedDashboardExperiment = experiment_by_id($response['body'] ?? [], $managedExperimentId);
    assert_equals($managedDashboardExperiment['adminNotes'] ?? null, 'Internal smoke-test note', 'dashboard should expose administrator-only notes');
    assert_equals($managedDashboardExperiment['readiness']['ready'] ?? null, true, 'dashboard should expose ready-to-open state');
    assert_true(count($managedDashboardExperiment['readiness']['indicators'] ?? []) >= 6, 'dashboard should expose operational completeness indicators');
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
    assert_equals($response['body']['creditedReward'] ?? null, 4, 'final reward should be partially counted at the course maximum');

    $response = make_request($baseUrl, 'GET', '/api/manage/report.php');
    assert_equals($response['status'], 200, 'report should load after confirmation');
    $danaReportRow = report_row_by_code($response['body'] ?? [], 'dana');
    $erikReportRow = report_row_by_code($response['body'] ?? [], 'erik');
    assert_equals(report_value_for_experiment($response['body'] ?? [], $danaReportRow, $managedExperimentId), 1, 'confirmed participation should count as approved');
    assert_equals(report_value_for_experiment($response['body'] ?? [], $erikReportRow, $managedExperimentId), 0, 'missing participation should stay zero');
    assert_equals($danaReportRow['totalCredits'] ?? null, 4, 'report should total snapshotted course credits');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_appointment',
        'participationId' => $managedParticipationId,
        'appointmentText' => '09:30',
    ]);
    assert_equals($response['status'], 200, 'management should save appointment text');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_experiment',
        'name' => 'Additional Participation After Maximum',
        'maxParticipants' => 1,
        'rewardCredits' => 1,
        'audienceMode' => 'all_groups',
        'groupIds' => [],
        'eligibilityMode' => 'selected',
        'conditionMode' => 'none',
        'requiresTimeSlot' => false,
        'isOpen' => false,
        'sortOrder' => 35,
    ]);
    assert_equals($response['status'], 201, 'management should create an additional closed experiment');
    $additionalExperimentId = (int) ($response['body']['experimentId'] ?? 0);
    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'assign_student',
        'experimentId' => $additionalExperimentId,
        'email' => 'dana@students.zhaw.ch',
        'conditionId' => null,
    ]);
    assert_equals($response['status'], 200, 'management should select student who already reached course maximum');
    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'save_experiment',
        'id' => $additionalExperimentId,
        'name' => 'Additional Participation After Maximum',
        'maxParticipants' => 1,
        'rewardCredits' => 1,
        'audienceMode' => 'all_groups',
        'groupIds' => [],
        'eligibilityMode' => 'selected',
        'conditionMode' => 'none',
        'requiresTimeSlot' => false,
        'isOpen' => true,
        'sortOrder' => 35,
    ]);
    assert_equals($response['status'], 200, 'selected additional experiment should open when ready');
    $response = make_request($baseUrl, 'POST', '/api/claim.php', ['experimentId' => $additionalExperimentId]);
    assert_equals($response['status'], 200, 'reaching course maximum must not prevent further participation');
    $response = make_request($baseUrl, 'GET', '/api/manage/dashboard.php');
    $additionalParticipation = dashboard_participation($response['body'] ?? [], 'dana@students.zhaw.ch', $additionalExperimentId);
    $auditActions = array_column($response['body']['auditEvents'] ?? [], 'action');
    assert_true(in_array('participation_claimed', $auditActions, true), 'audit log should include successful student claims');
    assert_true(in_array('save_experiment', $auditActions, true), 'audit log should include successful management changes');
    assert_true(in_array('generate_student_access_codes', $auditActions, true), 'audit log should include access-code generation without plaintext values');
    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'toggle_confirmation',
        'participationId' => (int) $additionalParticipation['id'],
    ]);
    assert_equals($response['status'], 200, 'additional participation should still be confirmable');
    assert_equals($response['body']['creditedReward'] ?? null, 0, 'reward after course maximum should count as zero');

    $response = make_request($baseUrl, 'GET', '/api/student_overview.php?email=dana%40students.zhaw.ch');
    assert_equals($response['status'], 200, 'student should retrieve managed assignment');
    $managedExperiment = experiment_by_id($response['body'] ?? [], $managedExperimentId);
    assert_equals($managedExperiment['confirmed'] ?? null, true, 'student overview should show confirmation');
    assert_equals($managedExperiment['canViewAccess'] ?? null, false, 'confirmed participations should not expose access button');
    assert_equals(count($managedExperiment['accessItems'] ?? []), 0, 'confirmed participations should not expose access data');
    assert_equals($managedExperiment['appointmentText'] ?? null, '09:30', 'student overview should show appointment text');
    assert_equals($managedExperiment['creditedReward'] ?? null, 4, 'student overview should show partially counted reward');
    assert_equals($response['body']['credits']['earned'] ?? null, 4, 'student overview should show capped course total');
    $additionalExperiment = experiment_by_id($response['body'] ?? [], $additionalExperimentId);
    assert_equals($additionalExperiment['creditedReward'] ?? null, 0, 'student overview should show zero reward after maximum');

    $response = make_request($baseUrl, 'POST', '/api/manage/actions.php', [
        'action' => 'set_student_login_code',
        'email' => 'dana@students.zhaw.ch',
        'accessCode' => 'Dana55',
    ]);
    assert_equals($response['status'], 200, 'management should rotate a student access code');
    $response = make_request($baseUrl, 'GET', '/api/student_overview.php');
    assert_equals($response['status'], 401, 'changing a student access code should invalidate existing sessions');

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
