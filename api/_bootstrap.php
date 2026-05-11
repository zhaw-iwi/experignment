<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function app_config(): array
{
    return $GLOBALS['APP_CONFIG'];
}

function json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
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

function student_is_eligible(array $experiment, ?array $eligibility): bool
{
    return $experiment['eligibility_mode'] === 'all_allowed' || $eligibility !== null;
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
        'SELECT sc.id, sc.chosen_at, ts.id AS slot_id, ts.label, ts.starts_at, ts.ends_at, ts.capacity
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
    ];
}

function fetch_time_slots(PDO $pdo, int $experimentId): array
{
    $statement = $pdo->prepare(
        'SELECT ts.id, ts.label, ts.starts_at, ts.ends_at, ts.capacity, ts.is_active, ts.sort_order,
                COUNT(sc.id) AS chosen_count
         FROM time_slots ts
         LEFT JOIN slot_choices sc ON sc.time_slot_id = ts.id
         WHERE ts.experiment_id = :experiment_id
         GROUP BY ts.id, ts.label, ts.starts_at, ts.ends_at, ts.capacity, ts.is_active, ts.sort_order
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
    $eligibility = fetch_eligibility($pdo, $experimentId, $email);
    $participation = fetch_participation($pdo, $experimentId, $email);
    $eligible = student_is_eligible($experiment, $eligibility);

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

    $isOpen = bool_value($experiment['is_open']);
    $assigned = $participation !== null;
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
            $canChooseSlot = $isOpen && $slotChoice === null;
        }
    }

    return [
        'id' => $experimentId,
        'name' => $experiment['public_name'],
        'description' => $experiment['description'],
        'isOpen' => $isOpen,
        'eligibilityMode' => $experiment['eligibility_mode'],
        'conditionMode' => $experiment['condition_mode'],
        'requiresTimeSlot' => bool_value($experiment['requires_time_slot']),
        'eligible' => $eligible,
        'assigned' => $assigned,
        'assignedAt' => $participation['assigned_at'] ?? null,
        'confirmed' => $participation !== null && ($participation['confirmed_at'] ?? null) !== null,
        'confirmedAt' => $participation['confirmed_at'] ?? null,
        'condition' => condition_payload($activeCondition),
        'availableConditions' => $availableConditions,
        'canClaim' => $isOpen && $eligible && !$assigned,
        'canViewAccess' => $isOpen && $assigned,
        'canChooseCondition' => $canChooseCondition,
        'accessItems' => $isOpen && $participation !== null ? access_payload($pdo, $participation) : [],
        'slotChoice' => $slotChoice,
        'timeSlots' => $timeSlots,
        'canChooseSlot' => $canChooseSlot,
        'appointmentText' => $participation !== null ? fetch_appointment_text($pdo, (int) $participation['id']) : null,
    ];
}

function student_overview(PDO $pdo, string $email): array
{
    require_allowed_student($pdo, $email);

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
        'experiments' => $experiments,
    ];
}
