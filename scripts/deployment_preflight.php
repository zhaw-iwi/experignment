<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$arguments = array_slice($argv, 1);
if (in_array('--help', $arguments, true)) {
    fwrite(STDOUT, "Usage: php scripts/deployment_preflight.php [--expect-empty]\n");
    fwrite(STDOUT, "Checks production configuration, the V3 MySQL/MariaDB schema, runtime database permissions, and optionally an empty install.\n");
    exit(0);
}

$unknownArguments = array_values(array_diff($arguments, ['--expect-empty']));
if ($unknownArguments !== []) {
    fwrite(STDERR, '[ERROR] Unknown argument: ' . $unknownArguments[0] . PHP_EOL);
    exit(2);
}

$expectEmpty = in_array('--expect-empty', $arguments, true);
$passes = [];
$warnings = [];
$errors = [];

$pass = static function (string $message) use (&$passes): void {
    $passes[] = $message;
};
$warn = static function (string $message) use (&$warnings): void {
    $warnings[] = $message;
};
$error = static function (string $message) use (&$errors): void {
    $errors[] = $message;
};

try {
    require __DIR__ . '/../config/config.php';
} catch (Throwable $exception) {
    fwrite(STDERR, '[ERROR] Configuration could not be loaded: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

$config = $GLOBALS['APP_CONFIG'];
$databaseConfig = $config['db'];
if (($databaseConfig['missing'] ?? []) !== []) {
    $error('Missing database configuration: ' . implode(', ', $databaseConfig['missing']) . '.');
}

$adminHash = $config['auth']['admin_access_code_hash'] ?? null;
if (!is_string($adminHash) || $adminHash === '' || (password_get_info($adminHash)['algoName'] ?? 'unknown') === 'unknown') {
    $error('ADMIN_ACCESS_CODE_HASH is missing or is not a password hash.');
} else {
    $pass('Administrator access-code hash is configured.');
}

if (($config['session']['secure'] ?? false) !== true) {
    $error('APP_SESSION_SECURE must be true for production.');
} else {
    $pass('Secure session cookies are enabled.');
}

if (($config['auth']['admin_idle_seconds'] ?? 0) <= 0 || ($config['auth']['student_idle_seconds'] ?? 0) <= 0) {
    $error('Session idle timeouts must be positive.');
} else {
    $pass('Session idle timeouts are positive.');
}

$pass('Application timezone is ' . $config['timezone'] . '.');

$pdo = null;
if (($databaseConfig['missing'] ?? []) === []) {
    try {
        $pdo = new PDO(
            $databaseConfig['dsn'],
            $databaseConfig['username'],
            $databaseConfig['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        $pass('Database connection succeeded.');
    } catch (Throwable $exception) {
        $error('Database connection failed.');
    }
}

$requiredColumns = [
    'schema_versions' => ['version_number'],
    'student_groups' => ['name', 'max_credits'],
    'allowed_students' => ['student_email', 'group_id', 'login_code_hash', 'login_code_version'],
    'authentication_throttles' => ['subject_type', 'subject_hash', 'locked_until'],
    'experiments' => ['admin_notes', 'opens_at', 'closes_at', 'max_participants', 'reward_credits'],
    'experiment_conditions' => ['experiment_id', 'public_name'],
    'experiment_group_eligibilities' => ['experiment_id', 'group_id'],
    'experiment_eligibilities' => ['experiment_id', 'student_email', 'condition_id'],
    'access_fields' => ['experiment_id', 'value_source', 'shared_value'],
    'eligibility_field_values' => ['eligibility_id', 'field_id', 'field_value'],
    'access_pool_rows' => ['experiment_id', 'assigned_participation_id'],
    'access_pool_values' => ['pool_row_id', 'field_id', 'field_value'],
    'participations' => ['experiment_id', 'student_email', 'confirmed_at', 'reward_credits_snapshot'],
    'participation_field_values' => ['participation_id', 'field_id', 'field_value'],
    'time_slots' => ['experiment_id', 'starts_at', 'ends_at', 'capacity', 'is_undated'],
    'slot_choices' => ['participation_id', 'time_slot_id'],
    'appointments' => ['participation_id', 'appointment_text'],
    'randomization_runs' => ['experiment_id', 'seed'],
    'randomization_run_allocations' => ['run_id', 'condition_id'],
    'audit_events' => ['actor_type', 'action', 'details_json'],
];

if ($pdo instanceof PDO) {
    $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver !== 'mysql') {
        $error('Production database driver must be mysql; detected ' . $driver . '.');
    } else {
        $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        $pass('Database engine reports ' . $version . '.');

        $currentUser = (string) $pdo->query('SELECT CURRENT_USER()')->fetchColumn();
        if (str_starts_with(strtolower($currentUser), 'root@')) {
            $warn('The database connection uses root; production should use a dedicated application account.');
        } else {
            $pass('Database connection uses a non-root account.');
        }

        $databaseCharset = strtolower((string) $pdo->query('SELECT @@character_set_database')->fetchColumn());
        if ($databaseCharset !== 'utf8mb4') {
            $warn('Database default character set is ' . $databaseCharset . '; utf8mb4 is recommended.');
        } else {
            $pass('Database default character set is utf8mb4.');
        }

        $tableStatement = $pdo->query(
            'SELECT TABLE_NAME, ENGINE
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE()'
        );
        $tableEngines = [];
        foreach ($tableStatement->fetchAll() as $row) {
            $tableEngines[strtolower((string) $row['TABLE_NAME'])] = strtoupper((string) ($row['ENGINE'] ?? ''));
        }

        $columnStatement = $pdo->query(
            'SELECT TABLE_NAME, COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()'
        );
        $columnsByTable = [];
        foreach ($columnStatement->fetchAll() as $row) {
            $tableName = strtolower((string) $row['TABLE_NAME']);
            $columnsByTable[$tableName][] = strtolower((string) $row['COLUMN_NAME']);
        }

        $schemaErrorCount = count($errors);
        foreach ($requiredColumns as $table => $columns) {
            if (!isset($tableEngines[$table])) {
                $error('Required table is missing: ' . $table . '.');
                continue;
            }
            if ($tableEngines[$table] !== 'INNODB') {
                $error('Table ' . $table . ' must use InnoDB.');
            }
            foreach ($columns as $column) {
                if (!in_array($column, $columnsByTable[$table] ?? [], true)) {
                    $error('Required column is missing: ' . $table . '.' . $column . '.');
                }
            }
        }
        if (count($errors) === $schemaErrorCount) {
            $pass('All 20 required V3 tables and key columns are present with InnoDB storage.');
        }

        if (isset($tableEngines['schema_versions'])) {
            $schemaVersion = (int) $pdo->query('SELECT COALESCE(MAX(version_number), 0) FROM schema_versions')->fetchColumn();
            if ($schemaVersion !== 3) {
                $error('Expected schema version 3; found ' . $schemaVersion . '.');
            } else {
                $pass('Schema version is 3.');
            }
        }

        if ($expectEmpty) {
            $nonEmpty = [];
            foreach (array_keys($requiredColumns) as $table) {
                if ($table === 'schema_versions' || !isset($tableEngines[$table])) {
                    continue;
                }
                $rowCount = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
                if ($rowCount !== 0) {
                    $nonEmpty[] = $table . '=' . $rowCount;
                }
            }
            if ($nonEmpty === []) {
                $pass('All semester and runtime tables are empty.');
            } else {
                $error('Expected an empty installation, but found rows in: ' . implode(', ', $nonEmpty) . '.');
            }
        }

        if (isset($tableEngines['authentication_throttles'])) {
            try {
                $probeHash = hash('sha256', random_bytes(32));
                $pdo->beginTransaction();
                $insert = $pdo->prepare(
                    "INSERT INTO authentication_throttles
                        (subject_type, subject_hash, failed_attempts, window_started_at)
                     VALUES ('admin', :subject_hash, 1, CURRENT_TIMESTAMP)"
                );
                $insert->execute(['subject_hash' => $probeHash]);
                $update = $pdo->prepare(
                    'UPDATE authentication_throttles
                     SET failed_attempts = 2
                     WHERE subject_type = \'admin\' AND subject_hash = :subject_hash'
                );
                $update->execute(['subject_hash' => $probeHash]);
                $delete = $pdo->prepare(
                    'DELETE FROM authentication_throttles
                     WHERE subject_type = \'admin\' AND subject_hash = :subject_hash'
                );
                $delete->execute(['subject_hash' => $probeHash]);
                $pdo->rollBack();
                $pass('Runtime SELECT/INSERT/UPDATE/DELETE permissions succeeded in a rolled-back transaction.');
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error('Runtime database permission probe failed.');
            }
        }
    }
}

foreach ($passes as $message) {
    fwrite(STDOUT, '[OK] ' . $message . PHP_EOL);
}
foreach ($warnings as $message) {
    fwrite(STDOUT, '[WARN] ' . $message . PHP_EOL);
}
foreach ($errors as $message) {
    fwrite(STDERR, '[ERROR] ' . $message . PHP_EOL);
}

fwrite(STDOUT, sprintf(
    'Preflight result: %d passed, %d warning(s), %d error(s).' . PHP_EOL,
    count($passes),
    count($warnings),
    count($errors)
));

exit($errors === [] ? 0 : 1);
