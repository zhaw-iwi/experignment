<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$dbPath = $argv[1] ?? '';
if ($dbPath === '') {
    fwrite(STDERR, "Usage: php create_fixture.php <sqlite-path>\n");
    exit(1);
}

if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, "pdo_sqlite is required for browser tests.\n");
    exit(1);
}

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
    UNIQUE(experiment_id, student_email)
)');
$pdo->exec('CREATE TABLE student_chest_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_email TEXT NOT NULL,
    source_participation_id INTEGER NULL,
    event_type TEXT NOT NULL DEFAULT "participation_credited",
    trigger_scope TEXT NOT NULL,
    variant TEXT NOT NULL DEFAULT "gold",
    experiment_name_snapshot TEXT NOT NULL,
    earned_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    opened_at TEXT NULL,
    revoked_at TEXT NULL,
    UNIQUE(event_type, trigger_scope)
)');
$pdo->exec('CREATE TABLE appointments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    participation_id INTEGER NOT NULL UNIQUE,
    appointment_text TEXT NOT NULL,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
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

$pdo->exec("INSERT INTO student_groups (id, name, max_credits) VALUES
    (1, 'Course A', 8),
    (2, 'Course B', 10),
    (3, 'Course C', NULL),
    (4, 'Course D', 0)");

$students = [
    ['below@students.zhaw.ch', 1, 'below1'],
    ['above@students.zhaw.ch', 1, 'above1'],
    ['courseb@students.zhaw.ch', 2, 'courseb1'],
    ['missing@students.zhaw.ch', 3, 'missing1'],
    ['zero@students.zhaw.ch', 4, 'zero1'],
    ['chestfull@students.zhaw.ch', 1, 'full1'],
    ['chestmulti@students.zhaw.ch', 1, 'multi1'],
    ['chestreduced@students.zhaw.ch', 1, 'reduce1'],
    ['chestcleanup@students.zhaw.ch', 1, 'clean1'],
    ['chestslow@students.zhaw.ch', 1, 'slow1'],
    ['chestretry@students.zhaw.ch', 1, 'retry1'],
    ['chestrestore@students.zhaw.ch', 1, 'restore1'],
    ['chesttabs@students.zhaw.ch', 1, 'tabs1'],
];
$insertStudent = $pdo->prepare(
    'INSERT INTO allowed_students
        (student_email, group_id, login_code_hash, login_code_version, login_code_set_at)
     VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP)'
);
foreach ($students as [$email, $groupId, $accessCode]) {
    $insertStudent->execute([$email, $groupId, password_hash($accessCode, PASSWORD_DEFAULT)]);
}

$pdo->exec("INSERT INTO experiments
    (id, public_name, description, is_open, max_participants, reward_credits, eligibility_mode, condition_mode, requires_time_slot, sort_order)
    VALUES
    (1, 'One Point Study', 'One course-independent point', 1, NULL, 1, 'all_allowed', 'none', 0, 10),
    (2, 'Fractional Study', 'A 1.4-point reward', 1, NULL, 1.4, 'all_allowed', 'none', 0, 20),
    (3, 'Four Point Study', 'A four-point reward', 1, NULL, 4, 'all_allowed', 'none', 0, 30),
    (4, 'Eight Point Bonus', 'Can take a total beyond its target', 1, NULL, 8, 'all_allowed', 'none', 0, 40),
    (5, 'Langzeitstudie zur verständlichen Entscheidungsfindung unter aussergewöhnlich komplexen Bedingungen', 'Long localized browser fixture', 1, NULL, 0.25, 'all_allowed', 'none', 0, 50)");

$confirmations = [
    [2, 'below@students.zhaw.ch'],
    [3, 'below@students.zhaw.ch'],
    [1, 'above@students.zhaw.ch'],
    [4, 'above@students.zhaw.ch'],
    [3, 'courseb@students.zhaw.ch'],
    [2, 'missing@students.zhaw.ch'],
    [1, 'zero@students.zhaw.ch'],
];
$insertParticipation = $pdo->prepare(
    'INSERT INTO participations (experiment_id, student_email, assigned_at, confirmed_at)
     VALUES (?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
);
foreach ($confirmations as [$experimentId, $email]) {
    $insertParticipation->execute([$experimentId, $email]);
}

$pdo->exec("INSERT INTO participations
    (id, experiment_id, student_email, assigned_at, confirmed_at)
    VALUES
    (101, 1, 'chestfull@students.zhaw.ch', '2026-09-15 10:00:00', '2026-09-15 10:00:00'),
    (102, 1, 'chestmulti@students.zhaw.ch', '2026-09-15 09:00:00', '2026-09-15 09:00:00'),
    (103, 2, 'chestmulti@students.zhaw.ch', '2026-09-15 10:00:00', '2026-09-15 10:00:00'),
    (104, 3, 'chestreduced@students.zhaw.ch', '2026-09-15 10:00:00', '2026-09-15 10:00:00'),
    (105, 1, 'chestcleanup@students.zhaw.ch', '2026-09-15 09:00:00', '2026-09-15 09:00:00'),
    (106, 3, 'chestcleanup@students.zhaw.ch', '2026-09-15 10:00:00', '2026-09-15 10:00:00'),
    (107, 2, 'chestslow@students.zhaw.ch', '2026-09-15 10:00:00', '2026-09-15 10:00:00'),
    (108, 5, 'chestretry@students.zhaw.ch', '2026-09-15 10:00:00', '2026-09-15 10:00:00'),
    (109, 3, 'chestrestore@students.zhaw.ch', '2026-09-15 10:00:00', '2026-09-15 10:00:00'),
    (110, 4, 'chesttabs@students.zhaw.ch', '2026-09-15 10:00:00', '2026-09-15 10:00:00')");

$pdo->exec("INSERT INTO student_chest_events
    (id, student_email, source_participation_id, event_type, trigger_scope, variant, experiment_name_snapshot, earned_at)
    VALUES
    (201, 'chestfull@students.zhaw.ch', 101, 'participation_credited', 'participation:101', 'gold', 'One Point Study', '2026-09-15 10:00:00'),
    (202, 'chestmulti@students.zhaw.ch', 102, 'participation_credited', 'participation:102', 'gold', 'One Point Study', '2026-09-15 09:00:00'),
    (203, 'chestmulti@students.zhaw.ch', 103, 'participation_credited', 'participation:103', 'gold', 'Fractional Study', '2026-09-15 10:00:00'),
    (204, 'chestreduced@students.zhaw.ch', 104, 'participation_credited', 'participation:104', 'gold', 'Four Point Study', '2026-09-15 10:00:00'),
    (205, 'chestcleanup@students.zhaw.ch', 105, 'participation_credited', 'participation:105', 'gold', 'One Point Study', '2026-09-15 09:00:00'),
    (206, 'chestcleanup@students.zhaw.ch', 106, 'participation_credited', 'participation:106', 'gold', 'Four Point Study', '2026-09-15 10:00:00'),
    (207, 'chestslow@students.zhaw.ch', 107, 'participation_credited', 'participation:107', 'gold', 'Fractional Study', '2026-09-15 10:00:00'),
    (208, 'chestretry@students.zhaw.ch', 108, 'participation_credited', 'participation:108', 'gold', 'Langzeitstudie zur verständlichen Entscheidungsfindung unter aussergewöhnlich komplexen Bedingungen', '2026-09-15 10:00:00'),
    (209, 'chestrestore@students.zhaw.ch', 109, 'participation_credited', 'participation:109', 'gold', 'Four Point Study', '2026-09-15 10:00:00'),
    (210, 'chesttabs@students.zhaw.ch', 110, 'participation_credited', 'participation:110', 'gold', 'Eight Point Bonus', '2026-09-15 10:00:00')");

fwrite(STDOUT, "browser fixture ready\n");
