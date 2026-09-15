<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/_auth.php';

function app_config(): array
{
    return $GLOBALS['APP_CONFIG'];
}

function json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(int $statusCode, string $errorCode, string $message, array $details = []): void
{
    json_response($statusCode, [
        'error_code' => $errorCode,
        'message' => $message,
        'details' => $details,
    ]);
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    try {
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        fail(400, 'INVALID_JSON', 'Die Anfrage konnte nicht gelesen werden.');
    }

    return is_array($data) ? $data : [];
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = app_config()['db'];
    if (($config['missing'] ?? []) !== []) {
        fail(500, 'DATABASE_NOT_CONFIGURED', 'Die Datenbankkonfiguration ist unvollständig.', [
            'missing' => $config['missing'],
        ]);
    }

    try {
        $pdo = new PDO(
            $config['dsn'],
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $exception) {
        fail(500, 'DATABASE_UNAVAILABLE', 'Die Datenbankverbindung konnte nicht aufgebaut werden.');
    }

    return $pdo;
}

function require_method(string $method): void
{
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== strtoupper($method)) {
        fail(405, 'METHOD_NOT_ALLOWED', 'Diese HTTP-Methode ist hier nicht erlaubt.');
    }
}

function normalize_student_email(string $email): string
{
    return strtolower(trim($email));
}

function student_code_from_email(string $email): string
{
    $normalized = normalize_student_email($email);
    $atPosition = strpos($normalized, '@');

    return $atPosition === false ? $normalized : substr($normalized, 0, $atPosition);
}

function is_valid_student_email(string $email): bool
{
    return preg_match('/^[^@\s]+@students\.zhaw\.ch$/i', $email) === 1;
}

function is_valid_eligibility_mode(string $mode): bool
{
    return in_array($mode, ['all_allowed', 'selected'], true);
}

function is_valid_condition_mode(string $mode): bool
{
    return in_array($mode, ['none', 'student_choice', 'assigned'], true);
}

function is_valid_value_source(string $source): bool
{
    return in_array($source, ['shared', 'pool', 'staff_entry'], true);
}

function is_valid_value_type(string $type): bool
{
    return in_array($type, ['text', 'url', 'pid', 'appointment'], true);
}

function bool_value(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value)) {
        return $value === 1;
    }

    if (is_string($value)) {
        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    return false;
}

function nullable_int(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    if (is_numeric($value)) {
        $intValue = (int) $value;
        return $intValue > 0 ? $intValue : null;
    }

    return null;
}

function required_int(mixed $value, string $errorCode, string $message): int
{
    $intValue = nullable_int($value);
    if ($intValue === null) {
        fail(422, $errorCode, $message);
    }

    return $intValue;
}

function clean_text(mixed $value): string
{
    return trim((string) $value);
}

function field_key_from_label(string $label): string
{
    $key = strtolower(trim($label));
    $key = preg_replace('/[^a-z0-9]+/i', '_', $key);
    $key = trim((string) $key, '_');

    return $key !== '' ? substr($key, 0, 64) : 'field';
}

function is_sqlite(PDO $pdo): bool
{
    return (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
}

function random_order_sql(PDO $pdo): string
{
    return is_sqlite($pdo) ? 'RANDOM()' : 'RAND()';
}

function for_update_sql(PDO $pdo): string
{
    return is_sqlite($pdo) ? '' : ' FOR UPDATE';
}

function table_has_column(PDO $pdo, string $table, string $column): bool
{
    if (is_sqlite($pdo)) {
        $statement = $pdo->query('PRAGMA table_info(' . $table . ')');
        foreach ($statement->fetchAll() as $row) {
            if (($row['name'] ?? '') === $column) {
                return true;
            }
        }
        return false;
    }

    $statement = $pdo->prepare(
        'SELECT COUNT(*) AS column_count
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name'
    );
    $statement->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);
    $row = $statement->fetch();

    return (int) ($row['column_count'] ?? 0) > 0;
}

function is_allowed_student_email(PDO $pdo, string $email): bool
{
    $statement = $pdo->prepare(
        'SELECT id
         FROM allowed_students
         WHERE student_email = :student_email
         LIMIT 1'
    );
    $statement->execute([
        'student_email' => $email,
    ]);

    return $statement->fetch() !== false;
}

function require_allowed_student(PDO $pdo, string $email): void
{
    if (!is_valid_student_email($email)) {
        fail(422, 'INVALID_EMAIL', 'Bitte verwenden Sie Ihre ZHAW-Studierenden-E-Mail-Adresse.');
    }

    if (!is_allowed_student_email($pdo, $email)) {
        fail(422, 'EMAIL_NOT_RECOGNIZED', 'Wir haben die E-Mail-Adresse nicht erkannt.');
    }
}

function fetch_experiment(PDO $pdo, int $experimentId): ?array
{
    $statement = $pdo->prepare(
        'SELECT *
         FROM experiments
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $experimentId]);
    $row = $statement->fetch();

    return $row !== false ? $row : null;
}

function fetch_conditions(PDO $pdo, int $experimentId): array
{
    $statement = $pdo->prepare(
        'SELECT id, experiment_id, public_name, sort_order
         FROM experiment_conditions
         WHERE experiment_id = :experiment_id
         ORDER BY sort_order ASC, id ASC'
    );
    $statement->execute(['experiment_id' => $experimentId]);

    return $statement->fetchAll();
}

function fetch_condition(PDO $pdo, int $conditionId): ?array
{
    $statement = $pdo->prepare(
        'SELECT id, experiment_id, public_name, sort_order
         FROM experiment_conditions
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $conditionId]);
    $row = $statement->fetch();

    return $row !== false ? $row : null;
}

function condition_belongs_to_experiment(PDO $pdo, int $conditionId, int $experimentId): bool
{
    $condition = fetch_condition($pdo, $conditionId);
    return $condition !== null && (int) $condition['experiment_id'] === $experimentId;
}

function fetch_eligibility(PDO $pdo, int $experimentId, string $email): ?array
{
    $statement = $pdo->prepare(
        'SELECT *
         FROM experiment_eligibilities
         WHERE experiment_id = :experiment_id
           AND student_email = :student_email
         LIMIT 1'
    );
    $statement->execute([
        'experiment_id' => $experimentId,
        'student_email' => $email,
    ]);
    $row = $statement->fetch();

    return $row !== false ? $row : null;
}

function fetch_participation(PDO $pdo, int $experimentId, string $email): ?array
{
    $statement = $pdo->prepare(
        'SELECT *
         FROM participations
         WHERE experiment_id = :experiment_id
           AND student_email = :student_email
         LIMIT 1'
    );
    $statement->execute([
        'experiment_id' => $experimentId,
        'student_email' => $email,
    ]);
    $row = $statement->fetch();

    return $row !== false ? $row : null;
}

function write_audit_event(
    PDO $pdo,
    string $actorType,
    ?string $actorIdentifier,
    string $action,
    ?string $entityType = null,
    ?string $entityIdentifier = null,
    array $details = []
): void {
    $statement = $pdo->prepare(
        'INSERT INTO audit_events
            (actor_type, actor_identifier, action, entity_type, entity_identifier, details_json, ip_address)
         VALUES
            (:actor_type, :actor_identifier, :action, :entity_type, :entity_identifier, :details_json, :ip_address)'
    );
    $statement->execute([
        'actor_type' => $actorType,
        'actor_identifier' => $actorIdentifier,
        'action' => substr($action, 0, 100),
        'entity_type' => $entityType === null ? null : substr($entityType, 0, 100),
        'entity_identifier' => $entityIdentifier === null ? null : substr($entityIdentifier, 0, 255),
        'details_json' => $details === [] ? null : json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
    ]);
}

function schedule_successful_audit_event(
    PDO $pdo,
    string $actorType,
    ?string $actorIdentifier,
    string $action,
    ?string $entityType = null,
    ?string $entityIdentifier = null,
    array $details = []
): void {
    register_shutdown_function(static function () use (
        $pdo,
        $actorType,
        $actorIdentifier,
        $action,
        $entityType,
        $entityIdentifier,
        $details
    ): void {
        $status = http_response_code();
        if (!is_int($status) || $status < 200 || $status >= 400) {
            return;
        }
        try {
            write_audit_event($pdo, $actorType, $actorIdentifier, $action, $entityType, $entityIdentifier, $details);
        } catch (Throwable $exception) {
            error_log('Audit event could not be written: ' . $action);
        }
    });
}

function fetch_allowed_student(PDO $pdo, string $email): ?array
{
    $statement = $pdo->prepare(
        'SELECT a.*, g.name AS group_name, g.max_credits AS group_max_credits
         FROM allowed_students a
         INNER JOIN student_groups g ON g.id = a.group_id
         WHERE a.student_email = :student_email
         LIMIT 1'
    );
    $statement->execute(['student_email' => normalize_student_email($email)]);
    $student = $statement->fetch();

    return $student !== false ? $student : null;
}

function explicit_experiment_group_ids(PDO $pdo, int $experimentId): array
{
    $statement = $pdo->prepare(
        'SELECT group_id
         FROM experiment_group_eligibilities
         WHERE experiment_id = :experiment_id
         ORDER BY group_id ASC'
    );
    $statement->execute(['experiment_id' => $experimentId]);

    return array_map(static fn (array $row): int => (int) $row['group_id'], $statement->fetchAll());
}

function student_group_is_eligible(PDO $pdo, int $experimentId, int $groupId): bool
{
    $explicitGroupIds = explicit_experiment_group_ids($pdo, $experimentId);

    return $explicitGroupIds === [] || in_array($groupId, $explicitGroupIds, true);
}

function student_is_eligible(array $experiment, ?array $eligibility, bool $groupEligible = true): bool
{
    return $groupEligible && ($experiment['eligibility_mode'] === 'all_allowed' || $eligibility !== null);
}

function experiment_is_available_now(array $experiment, ?int $now = null): bool
{
    if (!bool_value($experiment['is_open'] ?? false)) {
        return false;
    }
    $now ??= time();
    $opensAt = is_string($experiment['opens_at'] ?? null) && $experiment['opens_at'] !== ''
        ? strtotime($experiment['opens_at'])
        : false;
    $closesAt = is_string($experiment['closes_at'] ?? null) && $experiment['closes_at'] !== ''
        ? strtotime($experiment['closes_at'])
        : false;

    return ($opensAt === false || $now >= $opensAt)
        && ($closesAt === false || $now < $closesAt);
}

function experiment_is_full(PDO $pdo, array $experiment): bool
{
    $maximum = nullable_int($experiment['max_participants'] ?? null);
    if ($maximum === null) {
        return false;
    }
    $statement = $pdo->prepare('SELECT COUNT(*) FROM participations WHERE experiment_id = :experiment_id');
    $statement->execute(['experiment_id' => (int) $experiment['id']]);

    return (int) $statement->fetchColumn() >= $maximum;
}

function experiment_audience_students(PDO $pdo, array $experiment): array
{
    $experimentId = (int) $experiment['id'];
    $explicitGroupIds = explicit_experiment_group_ids($pdo, $experimentId);
    $params = [];
    $groupSql = '';
    if ($explicitGroupIds !== []) {
        $placeholders = [];
        foreach ($explicitGroupIds as $index => $groupId) {
            $key = 'group_id_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $groupId;
        }
        $groupSql = ' AND a.group_id IN (' . implode(', ', $placeholders) . ')';
    }
    $eligibilityJoin = $experiment['eligibility_mode'] === 'selected'
        ? ' INNER JOIN experiment_eligibilities ee
              ON ee.student_email = a.student_email
             AND ee.experiment_id = :experiment_id'
        : ' LEFT JOIN experiment_eligibilities ee
              ON ee.student_email = a.student_email
             AND ee.experiment_id = :experiment_id';
    $params['experiment_id'] = $experimentId;
    $statement = $pdo->prepare(
        'SELECT a.student_email, a.group_id, a.login_code_hash,
                g.name AS group_name, g.max_credits AS group_max_credits,
                ee.id AS eligibility_id, ee.condition_id
         FROM allowed_students a
         INNER JOIN student_groups g ON g.id = a.group_id' . $eligibilityJoin . '
         WHERE 1 = 1' . $groupSql . '
         ORDER BY a.student_email ASC'
    );
    $statement->execute($params);

    return $statement->fetchAll();
}

function credit_percentage(float $earned, ?float $target): ?float
{
    if ($target === null || $target <= 0) {
        return null;
    }

    return round(($earned / $target) * 100, 2);
}

function student_credit_summary(PDO $pdo, string $email): array
{
    $student = fetch_allowed_student($pdo, $email);
    if ($student === null) {
        return ['earned' => 0.0, 'maximum' => null, 'remaining' => null, 'percentage' => null];
    }
    $statement = $pdo->prepare(
        'SELECT COALESCE(SUM(e.reward_credits), 0)
         FROM participations p
         INNER JOIN experiments e ON e.id = p.experiment_id
         WHERE p.student_email = :student_email
           AND p.confirmed_at IS NOT NULL'
    );
    $statement->execute(['student_email' => normalize_student_email($email)]);
    $earned = round((float) $statement->fetchColumn(), 2);
    $maximum = $student['group_max_credits'] === null ? null : (float) $student['group_max_credits'];

    return [
        'earned' => $earned,
        'maximum' => $maximum,
        'remaining' => $maximum === null ? null : max(0.0, round($maximum - $earned, 2)),
        'percentage' => credit_percentage($earned, $maximum),
    ];
}

function experiment_readiness(PDO $pdo, array $experiment): array
{
    $experimentId = (int) $experiment['id'];
    $issues = [];
    $indicators = [];
    $addIndicator = static function (string $key, string $label, string $status, string $message) use (&$issues, &$indicators): void {
        $indicators[] = ['key' => $key, 'label' => $label, 'status' => $status, 'message' => $message];
        if ($status === 'error' || $status === 'warning') {
            $issues[] = ['severity' => $status, 'key' => $key, 'message' => $message];
        }
    };

    $explicitGroupIds = explicit_experiment_group_ids($pdo, $experimentId);
    $groupParams = [];
    $groupSql = '';
    if ($explicitGroupIds !== []) {
        $groupPlaceholders = [];
        foreach ($explicitGroupIds as $index => $groupId) {
            $key = 'readiness_group_' . $index;
            $groupPlaceholders[] = ':' . $key;
            $groupParams[$key] = $groupId;
        }
        $groupSql = ' WHERE id IN (' . implode(', ', $groupPlaceholders) . ')';
    }
    $groupStatement = $pdo->prepare('SELECT id, name, max_credits FROM student_groups' . $groupSql . ' ORDER BY name ASC');
    $groupStatement->execute($groupParams);
    $groups = $groupStatement->fetchAll();
    $students = experiment_audience_students($pdo, $experiment);
    if ($groups === [] || $students === []) {
        $addIndicator('audience', 'Zielgruppe', 'error', 'Die Zielgruppe enthält keine Studierenden.');
    } else {
        $addIndicator('audience', 'Zielgruppe', 'complete', count($students) . ' Studierende in ' . count($groups) . ' Kursen.');
    }

    $groupsWithoutMaximum = array_filter($groups, static fn (array $group): bool => $group['max_credits'] === null);
    if ($groupsWithoutMaximum !== []) {
        $addIndicator('course_maximum', 'Punkteziele', 'error', count($groupsWithoutMaximum) . ' Zielkurse haben noch kein Punkteziel.');
    } else {
        $addIndicator('course_maximum', 'Punkteziele', 'complete', 'Alle Zielkurse haben ein Punkteziel.');
    }

    $studentsWithoutCode = array_filter(
        $students,
        static fn (array $student): bool => !is_string($student['login_code_hash'] ?? null) || $student['login_code_hash'] === ''
    );
    if ($studentsWithoutCode !== []) {
        $addIndicator('access_codes', 'Zugangscodes', 'error', count($studentsWithoutCode) . ' Studierende der Zielgruppe haben noch keinen Zugangscode.');
    } else {
        $addIndicator('access_codes', 'Zugangscodes', 'complete', 'Alle Studierenden der Zielgruppe haben einen Zugangscode.');
    }

    $conditions = fetch_conditions($pdo, $experimentId);
    if ($experiment['condition_mode'] === 'none') {
        $addIndicator('conditions', 'Bedingungen', 'not_required', 'Für dieses Experiment werden keine Bedingungen benötigt.');
    } elseif ($conditions === []) {
        $addIndicator('conditions', 'Bedingungen', 'error', 'Der gewählte Bedingungsmodus benötigt mindestens eine Bedingung.');
    } elseif ($experiment['condition_mode'] === 'assigned') {
        $unassigned = array_filter($students, static fn (array $student): bool => nullable_int($student['condition_id'] ?? null) === null);
        $addIndicator(
            'conditions',
            'Bedingungen',
            $unassigned === [] ? 'complete' : 'error',
            $unassigned === [] ? 'Alle Studierenden haben eine Bedingung.' : count($unassigned) . ' Studierende haben noch keine Bedingung.'
        );
    } else {
        $addIndicator('conditions', 'Bedingungen', 'complete', count($conditions) . ' Bedingungen stehen zur Auswahl.');
    }

    $allFieldsStatement = $pdo->prepare('SELECT * FROM access_fields WHERE experiment_id = :experiment_id');
    $allFieldsStatement->execute(['experiment_id' => $experimentId]);
    $fields = $allFieldsStatement->fetchAll();
    $emptySharedFields = array_filter(
        $fields,
        static fn (array $field): bool => $field['value_source'] === 'shared' && clean_text($field['shared_value'] ?? '') === ''
    );
    $poolFields = array_filter($fields, static fn (array $field): bool => $field['value_source'] === 'pool');
    $staffFields = array_filter(
        $fields,
        static fn (array $field): bool => $field['value_source'] === 'staff_entry' && $field['value_type'] !== 'appointment'
    );
    $missingStaffValues = 0;
    if ($staffFields !== []) {
        $staffValueStatement = $pdo->prepare(
            'SELECT field_id FROM eligibility_field_values WHERE eligibility_id = :eligibility_id'
        );
        foreach ($students as $student) {
            $eligibilityId = nullable_int($student['eligibility_id'] ?? null);
            $studentConditionId = nullable_int($student['condition_id'] ?? null);
            $valueFieldIds = [];
            if ($eligibilityId !== null) {
                $staffValueStatement->execute(['eligibility_id' => $eligibilityId]);
                $valueFieldIds = array_map(
                    static fn (array $value): int => (int) $value['field_id'],
                    $staffValueStatement->fetchAll()
                );
            }
            foreach ($staffFields as $field) {
                $fieldConditionId = nullable_int($field['condition_id'] ?? null);
                if ($fieldConditionId !== null && $fieldConditionId !== $studentConditionId) {
                    continue;
                }
                if (!in_array((int) $field['id'], $valueFieldIds, true)) {
                    $missingStaffValues++;
                }
            }
        }
    }
    if ($emptySharedFields !== []) {
        $addIndicator('access_data', 'Zugangsdaten', 'error', count($emptySharedFields) . ' gemeinsame Zugangsfelder haben noch keinen Wert.');
    } elseif ($missingStaffValues > 0) {
        $addIndicator('access_data', 'Zugangsdaten', 'error', $missingStaffValues . ' individuelle Verwaltungswerte sind noch nicht vorbereitet.');
    } elseif ($poolFields !== []) {
        $poolStatement = $pdo->prepare('SELECT id, condition_id FROM access_pool_rows WHERE experiment_id = :experiment_id');
        $poolStatement->execute(['experiment_id' => $experimentId]);
        $poolRows = $poolStatement->fetchAll();
        $poolValueStatement = $pdo->prepare('SELECT field_id FROM access_pool_values WHERE pool_row_id = :pool_row_id');
        $missingPoolValues = 0;
        foreach ($poolRows as $poolRow) {
            $poolValueStatement->execute(['pool_row_id' => (int) $poolRow['id']]);
            $valueFieldIds = array_map(
                static fn (array $value): int => (int) $value['field_id'],
                $poolValueStatement->fetchAll()
            );
            $rowConditionId = nullable_int($poolRow['condition_id'] ?? null);
            foreach ($poolFields as $field) {
                $fieldConditionId = nullable_int($field['condition_id'] ?? null);
                if ($fieldConditionId !== null && $fieldConditionId !== $rowConditionId) {
                    continue;
                }
                if (!in_array((int) $field['id'], $valueFieldIds, true)) {
                    $missingPoolValues++;
                }
            }
        }
        $configuredMaximum = nullable_int($experiment['max_participants'] ?? null);
        $targetCapacity = $configuredMaximum === null ? count($students) : min($configuredMaximum, count($students));
        if ($missingPoolValues > 0) {
            $addIndicator('access_data', 'Zugangsdaten', 'error', $missingPoolValues . ' Werte fehlen in den Zugangsdaten-Paketen.');
        } elseif (count($poolRows) < $targetCapacity) {
            $addIndicator('access_data', 'Zugangsdaten', 'error', 'Der Zugangsdaten-Pool enthält ' . count($poolRows) . ' von benötigten ' . $targetCapacity . ' Datensätzen.');
        } else {
            $addIndicator('access_data', 'Zugangsdaten', 'complete', count($poolRows) . ' vollständige Zugangsdaten-Pakete sind vorbereitet.');
        }
    } elseif ($fields === []) {
        $addIndicator('access_data', 'Zugangsdaten', 'warning', 'Es sind keine studentensichtbaren Zugangsdaten konfiguriert.');
    } else {
        $addIndicator('access_data', 'Zugangsdaten', 'complete', count($fields) . ' Zugangsfelder sind konfiguriert.');
    }

    if (bool_value($experiment['requires_time_slot'] ?? false)) {
        $slotStatement = $pdo->prepare(
            'SELECT COUNT(*) AS slot_count, COALESCE(SUM(capacity), 0) AS total_capacity
             FROM time_slots
             WHERE experiment_id = :experiment_id AND is_active = 1'
        );
        $slotStatement->execute(['experiment_id' => $experimentId]);
        $slotSummary = $slotStatement->fetch();
        $slotCount = (int) ($slotSummary['slot_count'] ?? 0);
        $slotCapacity = (int) ($slotSummary['total_capacity'] ?? 0);
        $configuredMaximum = nullable_int($experiment['max_participants'] ?? null);
        $targetCapacity = $configuredMaximum === null ? count($students) : min($configuredMaximum, count($students));
        if ($slotCount === 0 || $slotCapacity < $targetCapacity) {
            $addIndicator('time_slots', 'Zeitslots', 'error', $slotCount === 0
                ? 'Es ist noch kein aktiver Zeitslot vorhanden.'
                : 'Die aktiven Zeitslots bieten ' . $slotCapacity . ' von benötigten ' . $targetCapacity . ' Plätzen.');
        } else {
            $addIndicator('time_slots', 'Zeitslots', 'complete', $slotCount . ' aktive Slots mit ' . $slotCapacity . ' Plätzen.');
        }
    } else {
        $addIndicator('time_slots', 'Zeitslots', 'not_required', 'Für dieses Experiment werden keine Zeitslots benötigt.');
    }

    $maximum = nullable_int($experiment['max_participants'] ?? null);
    $addIndicator(
        'capacity',
        'Teilnahmelimit',
        $maximum === null ? 'warning' : 'complete',
        $maximum === null ? 'Es ist kein maximales Teilnahmelimit gesetzt.' : 'Das Teilnahmelimit beträgt ' . $maximum . '.'
    );
    $hasSchedule = clean_text($experiment['opens_at'] ?? '') !== '' || clean_text($experiment['closes_at'] ?? '') !== '';
    $addIndicator(
        'schedule',
        'Verfügbarkeit',
        $hasSchedule ? 'complete' : 'warning',
        $hasSchedule ? 'Das Verfügbarkeitsfenster ist konfiguriert.' : 'Das Experiment wird ausschließlich manuell geöffnet und geschlossen.'
    );

    return [
        'ready' => !array_filter($issues, static fn (array $issue): bool => $issue['severity'] === 'error'),
        'issues' => array_values($issues),
        'indicators' => $indicators,
        'audienceStudentCount' => count($students),
    ];
}

function resolve_participation_condition(PDO $pdo, array $experiment, ?array $eligibility, ?int $requestedConditionId): ?int
{
    $experimentId = (int) $experiment['id'];
    $assignedConditionId = $eligibility !== null ? nullable_int($eligibility['condition_id'] ?? null) : null;

    if ($assignedConditionId !== null) {
        if ($requestedConditionId !== null && $requestedConditionId !== $assignedConditionId) {
            fail(409, 'CONDITION_MISMATCH', 'Für dieses Experiment wurde Ihnen eine andere Bedingung zugewiesen.');
        }
        return $assignedConditionId;
    }

    if ($requestedConditionId !== null) {
        if (!condition_belongs_to_experiment($pdo, $requestedConditionId, $experimentId)) {
            fail(422, 'INVALID_CONDITION', 'Die gewählte Bedingung gehört nicht zu diesem Experiment.');
        }

        if ($experiment['condition_mode'] !== 'student_choice') {
            fail(422, 'CONDITION_NOT_SELECTABLE', 'Die Bedingung kann für dieses Experiment nicht selbst gewählt werden.');
        }

        return $requestedConditionId;
    }

    $conditions = fetch_conditions($pdo, $experimentId);
    if ($conditions === []) {
        return null;
    }

    if ($experiment['condition_mode'] === 'student_choice') {
        fail(422, 'CONDITION_REQUIRED', 'Bitte wählen Sie eine Bedingung für dieses Experiment.');
    }

    fail(409, 'CONDITION_NOT_ASSIGNED', 'Für dieses Experiment wurde Ihnen noch keine Bedingung zugewiesen.');
}

function fetch_access_fields(PDO $pdo, int $experimentId, ?int $conditionId, bool $visibleOnly = true): array
{
    $visibleSql = $visibleOnly ? ' AND is_visible = 1' : '';
    if ($conditionId === null) {
        $statement = $pdo->prepare(
            'SELECT *
             FROM access_fields
             WHERE experiment_id = :experiment_id
               AND condition_id IS NULL' . $visibleSql . '
             ORDER BY sort_order ASC, id ASC'
        );
        $statement->execute(['experiment_id' => $experimentId]);
    } else {
        $statement = $pdo->prepare(
            'SELECT *
             FROM access_fields
             WHERE experiment_id = :experiment_id
               AND (condition_id IS NULL OR condition_id = :condition_id)' . $visibleSql . '
             ORDER BY sort_order ASC, id ASC'
        );
        $statement->execute([
            'experiment_id' => $experimentId,
            'condition_id' => $conditionId,
        ]);
    }

    return $statement->fetchAll();
}

function access_fields_require_pool(PDO $pdo, int $experimentId, ?int $conditionId): bool
{
    foreach (fetch_access_fields($pdo, $experimentId, $conditionId, false) as $field) {
        if ($field['value_source'] === 'pool') {
            return true;
        }
    }

    return false;
}

function select_available_pool_row(PDO $pdo, int $experimentId, ?int $conditionId): ?array
{
    $orderBy = random_order_sql($pdo);
    $forUpdate = for_update_sql($pdo);

    if ($conditionId === null) {
        $statement = $pdo->prepare(
            sprintf(
                'SELECT *
                 FROM access_pool_rows
                 WHERE experiment_id = :experiment_id
                   AND condition_id IS NULL
                   AND is_assigned = 0
                 ORDER BY %s
                 LIMIT 1%s',
                $orderBy,
                $forUpdate
            )
        );
        $statement->execute(['experiment_id' => $experimentId]);
    } else {
        $statement = $pdo->prepare(
            sprintf(
                'SELECT *
                 FROM access_pool_rows
                 WHERE experiment_id = :experiment_id
                   AND condition_id = :condition_id
                   AND is_assigned = 0
                 ORDER BY %s
                 LIMIT 1%s',
                $orderBy,
                $forUpdate
            )
        );
        $statement->execute([
            'experiment_id' => $experimentId,
            'condition_id' => $conditionId,
        ]);
    }

    $row = $statement->fetch();
    return $row !== false ? $row : null;
}

function fetch_pool_values(PDO $pdo, ?int $poolRowId): array
{
    if ($poolRowId === null) {
        return [];
    }

    $statement = $pdo->prepare(
        'SELECT field_id, field_value
         FROM access_pool_values
         WHERE pool_row_id = :pool_row_id'
    );
    $statement->execute(['pool_row_id' => $poolRowId]);

    $values = [];
    foreach ($statement->fetchAll() as $row) {
        $values[(int) $row['field_id']] = $row['field_value'];
    }

    return $values;
}

function fetch_participation_field_values(PDO $pdo, int $participationId): array
{
    $statement = $pdo->prepare(
        'SELECT field_id, field_value
         FROM participation_field_values
         WHERE participation_id = :participation_id'
    );
    $statement->execute(['participation_id' => $participationId]);

    $values = [];
    foreach ($statement->fetchAll() as $row) {
        $values[(int) $row['field_id']] = $row['field_value'];
    }

    return $values;
}

function copy_eligibility_field_values_to_participation(PDO $pdo, int $participationId, int $eligibilityId, int $experimentId, ?int $conditionId): void
{
    $fieldIds = [];
    foreach (fetch_access_fields($pdo, $experimentId, $conditionId, false) as $field) {
        if ($field['value_source'] === 'staff_entry' && $field['value_type'] !== 'appointment') {
            $fieldIds[] = (int) $field['id'];
        }
    }

    if ($fieldIds === []) {
        return;
    }

    $placeholders = implode(', ', array_fill(0, count($fieldIds), '?'));
    $lookup = $pdo->prepare(
        'SELECT field_id, field_value
         FROM eligibility_field_values
         WHERE eligibility_id = ?
           AND field_id IN (' . $placeholders . ')'
    );
    $lookup->execute(array_merge([$eligibilityId], $fieldIds));

    $insert = $pdo->prepare(
        'INSERT INTO participation_field_values (participation_id, field_id, field_value)
         VALUES (:participation_id, :field_id, :field_value)'
    );
    foreach ($lookup->fetchAll() as $valueRow) {
        $insert->execute([
            'participation_id' => $participationId,
            'field_id' => (int) $valueRow['field_id'],
            'field_value' => $valueRow['field_value'],
        ]);
    }
}

function fetch_appointment_text(PDO $pdo, int $participationId): ?string
{
    $statement = $pdo->prepare(
        'SELECT appointment_text
         FROM appointments
         WHERE participation_id = :participation_id
         LIMIT 1'
    );
    $statement->execute(['participation_id' => $participationId]);
    $row = $statement->fetch();

    return $row !== false ? (string) $row['appointment_text'] : null;
}

function fetch_slot_choice(PDO $pdo, int $participationId): ?array
{
    $statement = $pdo->prepare(
        'SELECT sc.id, sc.chosen_at, ts.id AS slot_id, ts.label, ts.starts_at, ts.ends_at, ts.capacity, ts.is_undated
         FROM slot_choices sc
         INNER JOIN time_slots ts ON ts.id = sc.time_slot_id
         WHERE sc.participation_id = :participation_id
         LIMIT 1'
    );
    $statement->execute(['participation_id' => $participationId]);
    $row = $statement->fetch();
    if ($row === false) {
        return null;
    }

    return [
        'id' => (int) $row['slot_id'],
        'label' => $row['label'],
        'startsAt' => $row['starts_at'],
        'endsAt' => $row['ends_at'],
        'chosenAt' => $row['chosen_at'],
        'capacity' => (int) $row['capacity'],
        'isUndated' => bool_value($row['is_undated']),
    ];
}

function fetch_time_slots(PDO $pdo, int $experimentId): array
{
    $statement = $pdo->prepare(
        'SELECT ts.id, ts.label, ts.starts_at, ts.ends_at, ts.capacity, ts.is_active, ts.is_undated, ts.sort_order,
                COUNT(sc.id) AS chosen_count
         FROM time_slots ts
         LEFT JOIN slot_choices sc ON sc.time_slot_id = ts.id
         WHERE ts.experiment_id = :experiment_id
         GROUP BY ts.id, ts.label, ts.starts_at, ts.ends_at, ts.capacity, ts.is_active, ts.is_undated, ts.sort_order
         ORDER BY ts.sort_order ASC, ts.id ASC'
    );
    $statement->execute(['experiment_id' => $experimentId]);

    $slots = [];
    foreach ($statement->fetchAll() as $row) {
        $capacity = (int) $row['capacity'];
        $chosenCount = (int) $row['chosen_count'];
        $slots[] = [
            'id' => (int) $row['id'],
            'label' => $row['label'],
            'startsAt' => $row['starts_at'],
            'endsAt' => $row['ends_at'],
            'capacity' => $capacity,
            'chosenCount' => $chosenCount,
            'remainingCapacity' => max(0, $capacity - $chosenCount),
            'isActive' => bool_value($row['is_active']),
            'isUndated' => bool_value($row['is_undated']),
            'sortOrder' => (int) $row['sort_order'],
        ];
    }

    return $slots;
}

function condition_payload(?array $condition): ?array
{
    if ($condition === null) {
        return null;
    }

    return [
        'id' => (int) $condition['id'],
        'name' => $condition['public_name'],
    ];
}

function access_payload(PDO $pdo, array $participation): array
{
    $experimentId = (int) $participation['experiment_id'];
    $conditionId = nullable_int($participation['condition_id'] ?? null);
    $fields = fetch_access_fields($pdo, $experimentId, $conditionId, true);
    $poolValues = fetch_pool_values($pdo, nullable_int($participation['access_pool_row_id'] ?? null));
    $manualValues = fetch_participation_field_values($pdo, (int) $participation['id']);
    $appointmentText = fetch_appointment_text($pdo, (int) $participation['id']);

    $items = [];
    foreach ($fields as $field) {
        $fieldId = (int) $field['id'];
        $value = null;
        if ($field['value_source'] === 'shared') {
            $value = $field['shared_value'];
        } elseif ($field['value_source'] === 'pool') {
            $value = $poolValues[$fieldId] ?? null;
        } elseif ($field['value_type'] === 'appointment') {
            $value = $appointmentText;
        } else {
            $value = $manualValues[$fieldId] ?? null;
        }

        if ($value === null || $value === '') {
            continue;
        }

        $items[] = [
            'id' => $fieldId,
            'key' => $field['field_key'],
            'label' => $field['label'],
            'valueType' => $field['value_type'],
            'value' => $value,
        ];
    }

    return $items;
}

function experiment_student_payload(PDO $pdo, array $experiment, string $email): ?array
{
    $experimentId = (int) $experiment['id'];
    $student = fetch_allowed_student($pdo, $email);
    if ($student === null) {
        return null;
    }
    $eligibility = fetch_eligibility($pdo, $experimentId, $email);
    $participation = fetch_participation($pdo, $experimentId, $email);
    $groupEligible = student_group_is_eligible($pdo, $experimentId, (int) $student['group_id']);
    $eligible = student_is_eligible($experiment, $eligibility, $groupEligible);

    if (!$eligible && $participation === null) {
        return null;
    }

    $conditions = fetch_conditions($pdo, $experimentId);
    $conditionRowsById = [];
    foreach ($conditions as $condition) {
        $conditionRowsById[(int) $condition['id']] = $condition;
    }

    $assignedEligibilityConditionId = $eligibility !== null
        ? nullable_int($eligibility['condition_id'] ?? null)
        : null;
    $participationConditionId = $participation !== null
        ? nullable_int($participation['condition_id'] ?? null)
        : null;
    $activeConditionId = $participationConditionId ?? $assignedEligibilityConditionId;
    $activeCondition = $activeConditionId !== null && isset($conditionRowsById[$activeConditionId])
        ? $conditionRowsById[$activeConditionId]
        : null;

    $isOpen = experiment_is_available_now($experiment);
    $isFull = experiment_is_full($pdo, $experiment);
    $assigned = $participation !== null;
    $confirmed = $participation !== null && ($participation['confirmed_at'] ?? null) !== null;
    $canChooseCondition = !$assigned
        && $isOpen
        && $experiment['condition_mode'] === 'student_choice'
        && $assignedEligibilityConditionId === null
        && $conditions !== [];

    $availableConditions = [];
    if ($canChooseCondition) {
        foreach ($conditions as $condition) {
            $availableConditions[] = condition_payload($condition);
        }
    }

    $slotChoice = null;
    $timeSlots = [];
    $canChooseSlot = false;
    if (bool_value($experiment['requires_time_slot'])) {
        $timeSlots = fetch_time_slots($pdo, $experimentId);
        if ($participation !== null) {
            $slotChoice = fetch_slot_choice($pdo, (int) $participation['id']);
            $canChooseSlot = $isOpen && !$confirmed && $slotChoice === null;
        }
    }

    return [
        'id' => $experimentId,
        'name' => $experiment['public_name'],
        'description' => $experiment['description'],
        'isOpen' => $isOpen,
        'configuredOpen' => bool_value($experiment['is_open']),
        'opensAt' => $experiment['opens_at'] ?? null,
        'closesAt' => $experiment['closes_at'] ?? null,
        'maxParticipants' => nullable_int($experiment['max_participants'] ?? null),
        'isFull' => $isFull,
        'rewardCredits' => round((float) ($experiment['reward_credits'] ?? 0), 2),
        'creditedReward' => $confirmed ? round((float) ($experiment['reward_credits'] ?? 0), 2) : null,
        'eligibilityMode' => $experiment['eligibility_mode'],
        'conditionMode' => $experiment['condition_mode'],
        'requiresTimeSlot' => bool_value($experiment['requires_time_slot']),
        'eligible' => $eligible,
        'assigned' => $assigned,
        'assignedAt' => $participation['assigned_at'] ?? null,
        'confirmed' => $confirmed,
        'confirmedAt' => $participation['confirmed_at'] ?? null,
        'condition' => condition_payload($activeCondition),
        'availableConditions' => $availableConditions,
        'canClaim' => $isOpen && $eligible && !$assigned && !$isFull,
        'canViewAccess' => $isOpen && $assigned && !$confirmed,
        'canChooseCondition' => $canChooseCondition,
        'accessItems' => $isOpen && $participation !== null && !$confirmed ? access_payload($pdo, $participation) : [],
        'slotChoice' => $slotChoice,
        'timeSlots' => $timeSlots,
        'canChooseSlot' => $canChooseSlot,
        'appointmentText' => $participation !== null ? fetch_appointment_text($pdo, (int) $participation['id']) : null,
    ];
}

function student_overview(PDO $pdo, string $email): array
{
    require_allowed_student($pdo, $email);
    $student = fetch_allowed_student($pdo, $email);

    $statement = $pdo->query(
        'SELECT *
         FROM experiments
         ORDER BY sort_order ASC, id ASC'
    );

    $experiments = [];
    foreach ($statement->fetchAll() as $experiment) {
        $payload = experiment_student_payload($pdo, $experiment, $email);
        if ($payload !== null) {
            $experiments[] = $payload;
        }
    }

    return [
        'email' => $email,
        'group' => [
            'id' => (int) ($student['group_id'] ?? 0),
            'name' => $student['group_name'] ?? '',
        ],
        'credits' => student_credit_summary($pdo, $email),
        'experiments' => $experiments,
    ];
}
