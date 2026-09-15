<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$adminAuth = require_admin_authentication();
require_csrf_token($adminAuth);

$payload = read_json_body();
$action = clean_text($payload['action'] ?? '');
$pdo = db();
$auditIdentifier = null;
foreach (['experimentId', 'participationId', 'fieldId', 'conditionId', 'slotId', 'groupId', 'email', 'id'] as $identifierKey) {
    if (isset($payload[$identifierKey]) && is_scalar($payload[$identifierKey])) {
        $auditIdentifier = (string) $payload[$identifierKey];
        break;
    }
}
schedule_successful_audit_event($pdo, 'admin', 'admin', $action, 'management_action', $auditIdentifier);

function ensure_condition_for_experiment(PDO $pdo, ?int $conditionId, int $experimentId): ?int
{
    if ($conditionId === null) {
        return null;
    }

    if (!condition_belongs_to_experiment($pdo, $conditionId, $experimentId)) {
        fail(422, 'INVALID_CONDITION', 'Die Bedingung gehört nicht zu diesem Experiment.');
    }

    return $conditionId;
}

function parse_table_lines(string $raw): array
{
    $lines = preg_split('/\R/u', trim($raw));
    if ($lines === false || $lines === [] || trim($lines[0] ?? '') === '') {
        fail(422, 'EMPTY_IMPORT', 'Bitte fügen Sie eine Tabelle mit Kopfzeile ein.');
    }

    $headerLine = (string) array_shift($lines);
    $delimiter = "\t";
    if (substr_count($headerLine, "\t") === 0) {
        $delimiter = substr_count($headerLine, ';') >= substr_count($headerLine, ',') ? ';' : ',';
    }

    $headers = array_map(
        static fn (string $header): string => strtolower(trim($header)),
        str_getcsv($headerLine, $delimiter, '"', '\\')
    );

    $rows = [];
    foreach ($lines as $line) {
        if (trim($line) === '') {
            continue;
        }
        $values = str_getcsv($line, $delimiter, '"', '\\');
        $row = [];
        foreach ($headers as $index => $header) {
            $row[$header] = trim((string) ($values[$index] ?? ''));
        }
        $rows[] = $row;
    }

    return [$headers, $rows];
}

function fetch_pool_fields_for_import(PDO $pdo, int $experimentId, ?int $conditionId): array
{
    $fields = [];
    foreach (fetch_access_fields($pdo, $experimentId, $conditionId, false) as $field) {
        if ($field['value_source'] !== 'pool') {
            continue;
        }
        $fields[] = $field;
    }

    if ($fields === []) {
        fail(422, 'NO_POOL_FIELDS', 'Für diese Auswahl sind keine Pool-Felder definiert.');
    }

    return $fields;
}

function require_valid_pool_import_scope(PDO $pdo, int $experimentId, ?int $conditionId): void
{
    $experiment = fetch_experiment($pdo, $experimentId);
    if ($experiment === null) {
        fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
    }

    if ($conditionId === null && $experiment['condition_mode'] !== 'none' && fetch_conditions($pdo, $experimentId) !== []) {
        fail(
            422,
            'CONDITION_POOL_REQUIRED',
            'Dieses Experiment verwendet Bedingungen. Bitte stellen Sie den Zugangsdaten-Pool pro Bedingung bereit.'
        );
    }
}

function deterministic_email_order(array $emails, string $seed): array
{
    usort(
        $emails,
        static fn (string $left, string $right): int => strcmp(
            hash('sha256', $seed . '|' . $left),
            hash('sha256', $seed . '|' . $right)
        )
    );

    return $emails;
}

function allocation_counts(array $allocations, int $total): array
{
    $sum = 0.0;
    foreach ($allocations as $allocation) {
        $sum += (float) $allocation['percentage'];
    }

    if ($sum <= 0.0) {
        fail(422, 'INVALID_PERCENTAGES', 'Die Prozentwerte müssen größer als 0 sein.');
    }

    $counts = [];
    $remainders = [];
    $assigned = 0;
    foreach ($allocations as $index => $allocation) {
        $exact = ((float) $allocation['percentage'] / $sum) * $total;
        $count = (int) floor($exact);
        $counts[$index] = $count;
        $remainders[$index] = $exact - $count;
        $assigned += $count;
    }

    arsort($remainders);
    $remaining = $total - $assigned;
    foreach (array_keys($remainders) as $index) {
        if ($remaining <= 0) {
            break;
        }
        $counts[$index]++;
        $remaining--;
    }

    return $counts;
}

function count_rows(PDO $pdo, string $sql, array $params): int
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $row = $statement->fetch();

    return (int) ($row['row_count'] ?? 0);
}

function delete_by_id(PDO $pdo, string $table, int $id): bool
{
    $statement = $pdo->prepare('DELETE FROM ' . $table . ' WHERE id = :id');
    $statement->execute(['id' => $id]);

    return $statement->rowCount() > 0;
}

function access_field_has_runtime_values(PDO $pdo, int $fieldId): bool
{
    $assignedPoolValues = count_rows(
        $pdo,
        'SELECT COUNT(*) AS row_count
         FROM access_pool_values apv
         INNER JOIN access_pool_rows apr ON apr.id = apv.pool_row_id
         WHERE apv.field_id = :field_id
           AND apr.is_assigned = 1',
        ['field_id' => $fieldId]
    );
    if ($assignedPoolValues > 0) {
        return true;
    }

    $preparedValues = count_rows(
        $pdo,
        'SELECT COUNT(*) AS row_count
         FROM eligibility_field_values
         WHERE field_id = :field_id',
        ['field_id' => $fieldId]
    );
    if ($preparedValues > 0) {
        return true;
    }

    $manualValues = count_rows(
        $pdo,
        'SELECT COUNT(*) AS row_count
         FROM participation_field_values
         WHERE field_id = :field_id',
        ['field_id' => $fieldId]
    );

    return $manualValues > 0;
}

function normalized_group_name(mixed $value): string
{
    $name = clean_text($value);
    if ($name === '' || strlen($name) > 255) {
        fail(422, 'INVALID_GROUP_NAME', 'Bitte geben Sie einen Kursnamen mit höchstens 255 Zeichen ein.');
    }

    return $name;
}

function require_student_group(PDO $pdo, int $groupId): array
{
    $statement = $pdo->prepare('SELECT * FROM student_groups WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $groupId]);
    $group = $statement->fetch();
    if ($group === false) {
        fail(404, 'STUDENT_GROUP_NOT_FOUND', 'Der Kurs wurde nicht gefunden.');
    }

    return $group;
}

function normalized_max_credits(mixed $value): ?float
{
    if ($value === null || trim((string) $value) === '') {
        return null;
    }
    if (!is_numeric($value)) {
        fail(422, 'INVALID_MAX_CREDITS', 'Die maximale Punktzahl muss eine nicht negative Zahl sein.');
    }
    $credits = (float) $value;
    if (!is_finite($credits) || $credits < 0 || $credits > 999999.99) {
        fail(422, 'INVALID_MAX_CREDITS', 'Die maximale Punktzahl muss eine nicht negative Zahl sein.');
    }

    return round($credits, 2);
}

function normalized_reward_credits(mixed $value): float
{
    if (!is_numeric($value)) {
        fail(422, 'INVALID_REWARD_CREDITS', 'Die Belohnung muss eine nicht negative Zahl sein.');
    }
    $credits = (float) $value;
    if (!is_finite($credits) || $credits < 0 || $credits > 999999.99) {
        fail(422, 'INVALID_REWARD_CREDITS', 'Die Belohnung muss eine nicht negative Zahl sein.');
    }

    return round($credits, 2);
}

function normalized_optional_capacity(mixed $value): ?int
{
    if ($value === null || trim((string) $value) === '') {
        return null;
    }
    if (!is_numeric($value) || (int) $value != (float) $value || (int) $value <= 0) {
        fail(422, 'INVALID_MAX_PARTICIPANTS', 'Die maximale Teilnehmerzahl muss eine positive ganze Zahl sein.');
    }

    return (int) $value;
}

function normalized_optional_datetime(mixed $value, string $errorCode): ?string
{
    $raw = clean_text($value);
    if ($raw === '') {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $raw);
    $errors = DateTimeImmutable::getLastErrors();
    if ($date === false || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
        fail(422, $errorCode, 'Bitte geben Sie ein gültiges Datum mit Uhrzeit ein.');
    }

    return $date->format('Y-m-d H:i:s');
}

function normalized_group_id_array(mixed $value): array
{
    if (!is_array($value)) {
        return [];
    }
    $ids = [];
    foreach ($value as $rawId) {
        $id = nullable_int($rawId);
        if ($id === null) {
            fail(422, 'INVALID_STUDENT_GROUP', 'Die Kursauswahl ist ungültig.');
        }
        $ids[] = $id;
    }

    return array_values(array_unique($ids));
}

function roster_delimiter(string $line): string
{
    $delimiters = ["\t", ';', ','];
    usort($delimiters, static fn (string $left, string $right): int => substr_count($line, $right) <=> substr_count($line, $left));

    return $delimiters[0];
}

function normalized_roster_header(string $value): string
{
    $value = preg_replace('/^\xEF\xBB\xBF/', '', trim($value)) ?? trim($value);
    $value = strtolower($value);

    return preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;
}

function imported_student_roster(string $raw): array
{
    $lines = preg_split('/\R/u', trim($raw));
    $lines = is_array($lines)
        ? array_values(array_filter($lines, static fn (string $line): bool => trim($line) !== ''))
        : [];
    if ($lines === []) {
        fail(422, 'EMPTY_ROSTER', 'Bitte fügen Sie eine Studierendenliste ein.');
    }

    $delimiter = roster_delimiter($lines[0]);
    $parsedRows = array_map(
        static fn (string $line): array => str_getcsv($line, $delimiter, '"', ''),
        $lines
    );
    $headers = array_map('normalized_roster_header', $parsedRows[0]);
    $emailHeaders = ['email', 'e_mail', 'student_email', 'studierenden_email', 'studierenden_e_mail'];
    $groupHeaders = ['group', 'group_name', 'course', 'course_name', 'kurs', 'gruppe'];
    $emailIndex = null;
    $groupIndex = null;
    foreach ($headers as $index => $header) {
        if (in_array($header, $emailHeaders, true)) {
            $emailIndex = $index;
        }
        if (in_array($header, $groupHeaders, true)) {
            $groupIndex = $index;
        }
    }
    $hasHeader = $emailIndex !== null && $groupIndex !== null;
    if (!$hasHeader) {
        $emailIndex = 0;
        $groupIndex = 1;
    }

    $roster = [];
    foreach ($parsedRows as $index => $fields) {
        if ($hasHeader && $index === 0) {
            continue;
        }
        if (!$hasHeader) {
            $validEmailIndices = [];
            foreach ($fields as $fieldIndex => $field) {
                if (is_valid_student_email(normalize_student_email((string) $field))) {
                    $validEmailIndices[] = $fieldIndex;
                }
            }
            if (count($validEmailIndices) === 1) {
                $emailIndex = $validEmailIndices[0];
                $groupIndex = $emailIndex === 0 ? 1 : 0;
            }
        }

        $email = normalize_student_email((string) ($fields[$emailIndex] ?? ''));
        $groupName = clean_text($fields[$groupIndex] ?? '');
        if (!is_valid_student_email($email) || $groupName === '' || strlen($groupName) > 255) {
            fail(422, 'INVALID_ROSTER_ROW', 'Die Studierendenliste enthält eine ungültige Zeile.', [
                'line' => $index + 1,
            ]);
        }
        if (isset($roster[$email]) && strcasecmp($roster[$email], $groupName) !== 0) {
            fail(422, 'CONFLICTING_ROSTER_ROW', 'Eine E-Mail-Adresse ist mehreren Kursen zugeordnet.', [
                'email' => $email,
            ]);
        }
        $roster[$email] = $groupName;
    }
    if ($roster === []) {
        fail(422, 'EMPTY_ROSTER', 'Die Studierendenliste enthält keine Datenzeilen.');
    }

    return $roster;
}

function find_or_create_student_group(PDO $pdo, string $name, int &$createdGroups): int
{
    $lookup = $pdo->prepare('SELECT id FROM student_groups WHERE LOWER(name) = LOWER(:name) LIMIT 1');
    $lookup->execute(['name' => $name]);
    $existing = $lookup->fetch();
    if ($existing !== false) {
        return (int) $existing['id'];
    }

    $insert = $pdo->prepare('INSERT INTO student_groups (name) VALUES (:name)');
    $insert->execute(['name' => $name]);
    $createdGroups++;

    return (int) $pdo->lastInsertId();
}

function normalized_email_array(mixed $rawEmails): array
{
    if (!is_array($rawEmails)) {
        return [];
    }

    $emails = [];
    foreach ($rawEmails as $rawEmail) {
        $email = normalize_student_email((string) $rawEmail);
        if (!is_valid_student_email($email)) {
            fail(422, 'INVALID_EMAIL', 'Bitte geben Sie gültige Studierenden-E-Mail-Adressen ein.');
        }
        $emails[] = $email;
    }

    return array_values(array_unique($emails));
}

function require_allowed_email_list(PDO $pdo, array $emails, ?int $experimentId = null): void
{
    foreach ($emails as $email) {
        require_allowed_student($pdo, $email);
        if ($experimentId !== null) {
            $student = fetch_allowed_student($pdo, $email);
            if ($student === null || !student_group_is_eligible($pdo, $experimentId, (int) $student['group_id'])) {
                fail(422, 'STUDENT_OUTSIDE_COURSE_AUDIENCE', 'Mindestens eine E-Mail-Adresse gehört nicht zur Kursfreigabe des Experiments.');
            }
        }
    }
}

function participation_emails_for_experiment(PDO $pdo, int $experimentId): array
{
    $statement = $pdo->prepare(
        'SELECT student_email
         FROM participations
         WHERE experiment_id = :experiment_id'
    );
    $statement->execute(['experiment_id' => $experimentId]);

    return array_map(
        static fn (array $row): string => (string) $row['student_email'],
        $statement->fetchAll()
    );
}

function participation_count_for_experiment(PDO $pdo, int $experimentId): int
{
    return count_rows(
        $pdo,
        'SELECT COUNT(*) AS row_count
         FROM participations
         WHERE experiment_id = :experiment_id',
        ['experiment_id' => $experimentId]
    );
}

function update_participation_confirmation(PDO $pdo, int $participationId, bool $confirm): array
{
    $statement = $pdo->prepare(
        'SELECT p.id, p.student_email, p.confirmed_at, e.reward_credits
         FROM participations p
         INNER JOIN experiments e ON e.id = p.experiment_id
         WHERE p.id = :id
         LIMIT 1' . for_update_sql($pdo)
    );
    $statement->execute(['id' => $participationId]);
    $participation = $statement->fetch();
    if ($participation === false) {
        return ['found' => false];
    }

    if (!$confirm) {
        $update = $pdo->prepare(
            'UPDATE participations
             SET confirmed_at = NULL,
                 reward_credits_snapshot = NULL
             WHERE id = :id'
        );
        $update->execute(['id' => $participationId]);
        return [
            'found' => true,
            'changed' => ($participation['confirmed_at'] ?? null) !== null,
            'confirmed' => false,
            'creditedReward' => null,
        ];
    }

    if (($participation['confirmed_at'] ?? null) !== null) {
        return [
            'found' => true,
            'changed' => false,
            'confirmed' => true,
            'creditedReward' => round((float) ($participation['reward_credits'] ?? 0), 2),
        ];
    }

    $creditedReward = round((float) $participation['reward_credits'], 2);

    $update = $pdo->prepare(
        'UPDATE participations
         SET confirmed_at = CURRENT_TIMESTAMP,
             reward_credits_snapshot = NULL
         WHERE id = :id'
    );
    $update->execute(['id' => $participationId]);

    return [
        'found' => true,
        'changed' => true,
        'confirmed' => true,
        'creditedReward' => $creditedReward,
    ];
}

function required_int_list(mixed $rawValues, string $errorCode, string $message): array
{
    if (!is_array($rawValues)) {
        fail(422, $errorCode, $message);
    }

    $ids = [];
    foreach ($rawValues as $rawValue) {
        $id = nullable_int($rawValue);
        if ($id === null || $id <= 0) {
            fail(422, $errorCode, $message);
        }
        $ids[] = $id;
    }

    $ids = array_values(array_unique($ids));
    if ($ids === []) {
        fail(422, $errorCode, $message);
    }

    if (count($ids) > 500) {
        fail(422, 'TOO_MANY_PARTICIPATIONS', 'Bitte wählen Sie höchstens 500 Zuweisungen auf einmal aus.');
    }

    return $ids;
}

function bind_int_list(array $ids, string $prefix, array &$params): string
{
    $placeholders = [];
    foreach ($ids as $index => $id) {
        $key = $prefix . $index;
        $placeholders[] = ':' . $key;
        $params[$key] = $id;
    }

    return implode(', ', $placeholders);
}

function delete_randomization_runs_for_experiment(PDO $pdo, int $experimentId): void
{
    $deleteAllocations = $pdo->prepare(
        'DELETE FROM randomization_run_allocations
         WHERE run_id IN (
             SELECT id
             FROM randomization_runs
             WHERE experiment_id = :experiment_id
         )'
    );
    $deleteAllocations->execute(['experiment_id' => $experimentId]);

    $deleteRuns = $pdo->prepare('DELETE FROM randomization_runs WHERE experiment_id = :experiment_id');
    $deleteRuns->execute(['experiment_id' => $experimentId]);
}

function upsert_eligibility(PDO $pdo, int $experimentId, string $email, ?int $conditionId, string $source, bool $preserveExistingCondition = false): void
{
    $existing = fetch_eligibility($pdo, $experimentId, $email);
    if ($existing === null) {
        $insert = $pdo->prepare(
            'INSERT INTO experiment_eligibilities (experiment_id, student_email, condition_id, source)
             VALUES (:experiment_id, :student_email, :condition_id, :source)'
        );
        $insert->execute([
            'experiment_id' => $experimentId,
            'student_email' => $email,
            'condition_id' => $conditionId,
            'source' => $source,
        ]);
        return;
    }

    $update = $pdo->prepare(
        'UPDATE experiment_eligibilities
         SET condition_id = :condition_id,
             source = :source
         WHERE experiment_id = :experiment_id
           AND student_email = :student_email'
    );
    $update->execute([
        'experiment_id' => $experimentId,
        'student_email' => $email,
        'condition_id' => $preserveExistingCondition ? nullable_int($existing['condition_id'] ?? null) : $conditionId,
        'source' => $preserveExistingCondition ? (string) $existing['source'] : $source,
    ]);
}

try {
    if ($action === 'save_student_group') {
        $id = nullable_int($payload['id'] ?? null);
        $name = normalized_group_name($payload['name'] ?? '');
        $maxCredits = normalized_max_credits($payload['maxCredits'] ?? null);

        $duplicateSql = 'SELECT id FROM student_groups WHERE LOWER(name) = LOWER(:name)';
        $duplicateParams = ['name' => $name];
        if ($id !== null) {
            $duplicateSql .= ' AND id <> :excluded_id';
            $duplicateParams['excluded_id'] = $id;
        }
        $duplicate = $pdo->prepare($duplicateSql . ' LIMIT 1');
        $duplicate->execute($duplicateParams);
        if ($duplicate->fetch() !== false) {
            fail(409, 'STUDENT_GROUP_NAME_EXISTS', 'Ein Kurs mit diesem Namen ist bereits vorhanden.');
        }

        if ($id === null) {
            $statement = $pdo->prepare(
                'INSERT INTO student_groups (name, max_credits)
                 VALUES (:name, :max_credits)'
            );
            $statement->execute(['name' => $name, 'max_credits' => $maxCredits]);
            $id = (int) $pdo->lastInsertId();
            json_response(201, ['id' => $id, 'name' => $name, 'maxCredits' => $maxCredits]);
        }

        require_student_group($pdo, $id);
        $statement = $pdo->prepare(
            'UPDATE student_groups
             SET name = :name,
                 max_credits = :max_credits
             WHERE id = :id'
        );
        $statement->execute(['id' => $id, 'name' => $name, 'max_credits' => $maxCredits]);
        json_response(200, ['id' => $id, 'name' => $name, 'maxCredits' => $maxCredits]);
    }

    if ($action === 'delete_student_group') {
        $groupId = required_int($payload['groupId'] ?? null, 'INVALID_STUDENT_GROUP', 'Bitte wählen Sie einen Kurs aus.');
        require_student_group($pdo, $groupId);
        $studentCount = count_rows(
            $pdo,
            'SELECT COUNT(*) AS row_count FROM allowed_students WHERE group_id = :group_id',
            ['group_id' => $groupId]
        );
        $experimentCount = count_rows(
            $pdo,
            'SELECT COUNT(*) AS row_count FROM experiment_group_eligibilities WHERE group_id = :group_id',
            ['group_id' => $groupId]
        );
        if ($studentCount > 0 || $experimentCount > 0) {
            fail(409, 'STUDENT_GROUP_IN_USE', 'Der Kurs wird noch von Studierenden oder Experimenten verwendet.');
        }

        $delete = $pdo->prepare('DELETE FROM student_groups WHERE id = :id');
        $delete->execute(['id' => $groupId]);
        json_response(200, ['groupId' => $groupId, 'deleted' => true]);
    }

    if ($action === 'add_allowed_student') {
        $email = normalize_student_email((string) ($payload['email'] ?? ''));
        $groupId = required_int($payload['groupId'] ?? null, 'INVALID_STUDENT_GROUP', 'Bitte wählen Sie einen Kurs aus.');
        if (!is_valid_student_email($email)) {
            fail(422, 'INVALID_EMAIL', 'Bitte geben Sie eine gültige Studierenden-E-Mail-Adresse ein.');
        }
        require_student_group($pdo, $groupId);

        if (is_allowed_student_email($pdo, $email)) {
            $existingStudent = fetch_allowed_student($pdo, $email);
            if ($existingStudent !== null && (int) $existingStudent['group_id'] !== $groupId) {
                $participationCount = count_rows(
                    $pdo,
                    'SELECT COUNT(*) AS row_count FROM participations WHERE student_email = :student_email',
                    ['student_email' => $email]
                );
                if ($participationCount > 0) {
                    fail(409, 'STUDENT_GROUP_HAS_PARTICIPATIONS', 'Der Kurs kann nach der ersten Experimentzuweisung nicht mehr geändert werden.');
                }
            }
            $update = $pdo->prepare('UPDATE allowed_students SET group_id = :group_id WHERE student_email = :student_email');
            $update->execute(['group_id' => $groupId, 'student_email' => $email]);
            json_response(200, ['created' => false, 'email' => $email, 'groupId' => $groupId]);
        }

        $insert = $pdo->prepare(
            'INSERT INTO allowed_students (student_email, group_id)
             VALUES (:student_email, :group_id)'
        );
        $insert->execute(['student_email' => $email, 'group_id' => $groupId]);
        json_response(201, ['created' => true, 'email' => $email, 'groupId' => $groupId]);
    }

    if ($action === 'import_student_roster') {
        $roster = imported_student_roster((string) ($payload['roster'] ?? ''));
        $pdo->beginTransaction();
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $createdGroups = 0;
        $groupIds = [];
        $lookup = $pdo->prepare('SELECT id, group_id FROM allowed_students WHERE student_email = :student_email LIMIT 1');
        $insert = $pdo->prepare(
            'INSERT INTO allowed_students (student_email, group_id)
             VALUES (:student_email, :group_id)'
        );
        $update = $pdo->prepare('UPDATE allowed_students SET group_id = :group_id WHERE id = :id');

        foreach ($roster as $email => $groupName) {
            $groupKey = strtolower($groupName);
            if (!isset($groupIds[$groupKey])) {
                $groupIds[$groupKey] = find_or_create_student_group($pdo, $groupName, $createdGroups);
            }
            $groupId = $groupIds[$groupKey];
            $lookup->execute(['student_email' => $email]);
            $student = $lookup->fetch();
            if ($student === false) {
                $insert->execute(['student_email' => $email, 'group_id' => $groupId]);
                $created++;
                continue;
            }
            if ((int) $student['group_id'] === $groupId) {
                $unchanged++;
                continue;
            }
            $participationCount = count_rows(
                $pdo,
                'SELECT COUNT(*) AS row_count FROM participations WHERE student_email = :student_email',
                ['student_email' => $email]
            );
            if ($participationCount > 0) {
                $pdo->rollBack();
                fail(409, 'STUDENT_GROUP_HAS_PARTICIPATIONS', 'Die Liste würde den Kurs einer Person mit bestehenden Experimentzuweisungen ändern.');
            }
            $update->execute(['group_id' => $groupId, 'id' => (int) $student['id']]);
            $updated++;
        }
        $pdo->commit();

        json_response(201, [
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'createdGroups' => $createdGroups,
            'totalValid' => count($roster),
        ]);
    }

    if ($action === 'set_student_login_code') {
        $email = normalize_student_email((string) ($payload['email'] ?? ''));
        $accessCode = trim((string) ($payload['accessCode'] ?? ''));
        if (!is_valid_student_email($email)) {
            fail(422, 'INVALID_EMAIL', 'Bitte geben Sie eine gültige Studierenden-E-Mail-Adresse ein.');
        }
        if (!access_code_meets_requirements($accessCode)) {
            fail(422, 'INVALID_ACCESS_CODE', 'Der Zugangscode muss aus 5 bis 128 Buchstaben und Ziffern bestehen und mindestens einen Buchstaben und eine Ziffer enthalten.');
        }
        if (!is_allowed_student_email($pdo, $email)) {
            fail(404, 'ALLOWED_STUDENT_NOT_FOUND', 'Diese E-Mail-Adresse ist nicht in der Zulassungsliste.');
        }

        $update = $pdo->prepare(
            'UPDATE allowed_students
             SET login_code_hash = :login_code_hash,
                 login_code_version = login_code_version + 1,
                 login_code_set_at = CURRENT_TIMESTAMP
             WHERE student_email = :student_email'
        );
        $update->execute([
            'login_code_hash' => hash_student_access_code($accessCode),
            'student_email' => $email,
        ]);
        json_response(200, ['email' => $email, 'loginCodeSet' => true]);
    }

    if ($action === 'delete_allowed_student') {
        $email = normalize_student_email((string) ($payload['email'] ?? ''));
        if (!is_valid_student_email($email)) {
            fail(422, 'INVALID_EMAIL', 'Bitte geben Sie eine gültige Studierenden-E-Mail-Adresse ein.');
        }

        if (!is_allowed_student_email($pdo, $email)) {
            fail(404, 'ALLOWED_STUDENT_NOT_FOUND', 'Diese E-Mail-Adresse ist nicht in der Zulassungsliste.');
        }

        $participationCount = count_rows(
            $pdo,
            'SELECT COUNT(*) AS row_count FROM participations WHERE student_email = :student_email',
            ['student_email' => $email]
        );
        if ($participationCount > 0) {
            fail(409, 'ALLOWED_STUDENT_HAS_PARTICIPATIONS', 'Diese E-Mail-Adresse hat bereits Zuweisungen und kann nicht aus der globalen Liste entfernt werden.');
        }

        $eligibilityCount = count_rows(
            $pdo,
            'SELECT COUNT(*) AS row_count FROM experiment_eligibilities WHERE student_email = :student_email',
            ['student_email' => $email]
        );

        $delete = $pdo->prepare('DELETE FROM allowed_students WHERE student_email = :student_email');
        $delete->execute(['student_email' => $email]);

        json_response(200, [
            'email' => $email,
            'deleted' => true,
            'removedEligibilityCount' => $eligibilityCount,
        ]);
    }

    if ($action === 'save_experiment') {
        $id = nullable_int($payload['id'] ?? null);
        $name = clean_text($payload['name'] ?? '');
        $description = clean_text($payload['description'] ?? '');
        $adminNotes = clean_text($payload['adminNotes'] ?? '');
        $eligibilityMode = clean_text($payload['eligibilityMode'] ?? 'selected');
        $conditionMode = clean_text($payload['conditionMode'] ?? 'none');
        $sortOrder = (int) ($payload['sortOrder'] ?? 0);
        $opensAt = normalized_optional_datetime($payload['opensAt'] ?? null, 'INVALID_OPENS_AT');
        $closesAt = normalized_optional_datetime($payload['closesAt'] ?? null, 'INVALID_CLOSES_AT');
        $maxParticipants = normalized_optional_capacity($payload['maxParticipants'] ?? null);
        $rewardCredits = normalized_reward_credits($payload['rewardCredits'] ?? 0);
        $audienceMode = clean_text($payload['audienceMode'] ?? 'all_groups');
        $groupIds = normalized_group_id_array($payload['groupIds'] ?? []);
        $requestedOpen = bool_value($payload['isOpen'] ?? false);

        if ($name === '' || strlen($name) > 255) {
            fail(422, 'INVALID_NAME', 'Bitte geben Sie einen Experimentnamen ein.');
        }
        if (strlen($adminNotes) > 60000) {
            fail(422, 'INVALID_ADMIN_NOTES', 'Die internen Notizen sind zu lang.');
        }
        if (!is_valid_eligibility_mode($eligibilityMode)) {
            fail(422, 'INVALID_ELIGIBILITY_MODE', 'Der Freigabemodus ist ungültig.');
        }
        if (!is_valid_condition_mode($conditionMode)) {
            fail(422, 'INVALID_CONDITION_MODE', 'Der Bedingungsmodus ist ungültig.');
        }
        if (!in_array($audienceMode, ['all_groups', 'selected_groups'], true)) {
            fail(422, 'INVALID_AUDIENCE_MODE', 'Die Kursfreigabe ist ungültig.');
        }
        if ($audienceMode === 'selected_groups' && $groupIds === []) {
            fail(422, 'STUDENT_GROUP_REQUIRED', 'Bitte wählen Sie mindestens einen Kurs aus.');
        }
        if ($opensAt !== null && $closesAt !== null && strtotime($opensAt) >= strtotime($closesAt)) {
            fail(422, 'INVALID_AVAILABILITY_WINDOW', 'Der Öffnungszeitpunkt muss vor dem Schließzeitpunkt liegen.');
        }
        if ($groupIds !== []) {
            $groupParams = [];
            $groupSql = bind_int_list($groupIds, 'experiment_group_', $groupParams);
            $groupStatement = $pdo->prepare('SELECT COUNT(*) FROM student_groups WHERE id IN (' . $groupSql . ')');
            $groupStatement->execute($groupParams);
            if ((int) $groupStatement->fetchColumn() !== count($groupIds)) {
                fail(422, 'INVALID_STUDENT_GROUP', 'Mindestens ein ausgewählter Kurs existiert nicht.');
            }
        }

        if ($id !== null) {
            $existing = fetch_experiment($pdo, $id);
            if ($existing === null) {
                fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
            }
            $participationCount = participation_count_for_experiment($pdo, $id);
            if ($maxParticipants !== null && $maxParticipants < $participationCount) {
                fail(409, 'MAX_PARTICIPANTS_TOO_LOW', 'Das Teilnahmelimit darf nicht unter der Anzahl bestehender Zuweisungen liegen.');
            }
            if ($audienceMode === 'selected_groups') {
                $participantGroups = $pdo->prepare(
                    'SELECT DISTINCT a.group_id
                     FROM participations p
                     INNER JOIN allowed_students a ON a.student_email = p.student_email
                     WHERE p.experiment_id = :experiment_id'
                );
                $participantGroups->execute(['experiment_id' => $id]);
                foreach ($participantGroups->fetchAll() as $participantGroup) {
                    if (!in_array((int) $participantGroup['group_id'], $groupIds, true)) {
                        fail(409, 'AUDIENCE_HAS_PARTICIPATIONS', 'Kurse mit bestehenden Zuweisungen müssen in der Kursfreigabe bleiben.');
                    }
                }
            }
        }

        $created = $id === null;
        $pdo->beginTransaction();
        if ($created) {
            $statement = $pdo->prepare(
                'INSERT INTO experiments
                    (public_name, description, admin_notes, is_open, opens_at, closes_at, max_participants,
                     reward_credits, eligibility_mode, condition_mode, requires_time_slot, sort_order)
                 VALUES
                    (:public_name, :description, :admin_notes, 0, :opens_at, :closes_at, :max_participants,
                     :reward_credits, :eligibility_mode, :condition_mode, :requires_time_slot, :sort_order)'
            );
            $statement->execute([
                'public_name' => $name,
                'description' => $description !== '' ? $description : null,
                'admin_notes' => $adminNotes !== '' ? $adminNotes : null,
                'opens_at' => $opensAt,
                'closes_at' => $closesAt,
                'max_participants' => $maxParticipants,
                'reward_credits' => $rewardCredits,
                'eligibility_mode' => $eligibilityMode,
                'condition_mode' => $conditionMode,
                'requires_time_slot' => bool_value($payload['requiresTimeSlot'] ?? false) ? 1 : 0,
                'sort_order' => $sortOrder,
            ]);
            $id = (int) $pdo->lastInsertId();
        } else {
            $statement = $pdo->prepare(
                'UPDATE experiments
                 SET public_name = :public_name,
                     description = :description,
                     admin_notes = :admin_notes,
                     is_open = 0,
                     opens_at = :opens_at,
                     closes_at = :closes_at,
                     max_participants = :max_participants,
                     reward_credits = :reward_credits,
                     eligibility_mode = :eligibility_mode,
                     condition_mode = :condition_mode,
                     requires_time_slot = :requires_time_slot,
                     sort_order = :sort_order
                 WHERE id = :id'
            );
            $statement->execute([
                'id' => $id,
                'public_name' => $name,
                'description' => $description !== '' ? $description : null,
                'admin_notes' => $adminNotes !== '' ? $adminNotes : null,
                'opens_at' => $opensAt,
                'closes_at' => $closesAt,
                'max_participants' => $maxParticipants,
                'reward_credits' => $rewardCredits,
                'eligibility_mode' => $eligibilityMode,
                'condition_mode' => $conditionMode,
                'requires_time_slot' => bool_value($payload['requiresTimeSlot'] ?? false) ? 1 : 0,
                'sort_order' => $sortOrder,
            ]);
        }

        $deleteGroups = $pdo->prepare('DELETE FROM experiment_group_eligibilities WHERE experiment_id = :experiment_id');
        $deleteGroups->execute(['experiment_id' => $id]);
        if ($audienceMode === 'selected_groups') {
            $insertGroup = $pdo->prepare(
                'INSERT INTO experiment_group_eligibilities (experiment_id, group_id)
                 VALUES (:experiment_id, :group_id)'
            );
            foreach ($groupIds as $groupId) {
                $insertGroup->execute(['experiment_id' => $id, 'group_id' => $groupId]);
            }
        }

        $storedExperiment = fetch_experiment($pdo, $id);
        $readiness = experiment_readiness($pdo, $storedExperiment ?? []);
        if ($requestedOpen && !$readiness['ready']) {
            $pdo->rollBack();
            fail(409, 'EXPERIMENT_NOT_READY', 'Das Experiment ist noch nicht bereit zum Öffnen.', [
                'issues' => $readiness['issues'],
            ]);
        }
        $openUpdate = $pdo->prepare('UPDATE experiments SET is_open = :is_open WHERE id = :id');
        $openUpdate->execute(['is_open' => $requestedOpen ? 1 : 0, 'id' => $id]);
        $pdo->commit();

        json_response($created ? 201 : 200, [
            'experimentId' => $id,
            'readiness' => $readiness,
        ]);
    }

    if ($action === 'delete_experiment') {
        $experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
        if (fetch_experiment($pdo, $experimentId) === null) {
            fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
        }

        $pdo->beginTransaction();
        $deleteParticipations = $pdo->prepare('DELETE FROM participations WHERE experiment_id = :experiment_id');
        $deleteParticipations->execute(['experiment_id' => $experimentId]);
        $deleteExperiment = $pdo->prepare('DELETE FROM experiments WHERE id = :id');
        $deleteExperiment->execute(['id' => $experimentId]);
        $pdo->commit();

        json_response(200, ['experimentId' => $experimentId, 'deleted' => true]);
    }

    if ($action === 'save_condition') {
        $id = nullable_int($payload['id'] ?? null);
        $experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
        $name = clean_text($payload['name'] ?? '');
        $sortOrder = (int) ($payload['sortOrder'] ?? 0);

        if (fetch_experiment($pdo, $experimentId) === null) {
            fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
        }
        if ($name === '') {
            fail(422, 'INVALID_NAME', 'Bitte geben Sie einen Namen für die Bedingung ein.');
        }

        if ($id === null) {
            $statement = $pdo->prepare(
                'INSERT INTO experiment_conditions (experiment_id, public_name, sort_order)
                 VALUES (:experiment_id, :public_name, :sort_order)'
            );
            $statement->execute([
                'experiment_id' => $experimentId,
                'public_name' => $name,
                'sort_order' => $sortOrder,
            ]);
            json_response(201, ['conditionId' => (int) $pdo->lastInsertId()]);
        }

        ensure_condition_for_experiment($pdo, $id, $experimentId);
        $statement = $pdo->prepare(
            'UPDATE experiment_conditions
             SET public_name = :public_name,
                 sort_order = :sort_order
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'public_name' => $name,
            'sort_order' => $sortOrder,
        ]);
        json_response(200, ['conditionId' => $id]);
    }

    if ($action === 'delete_condition') {
        $conditionId = required_int($payload['conditionId'] ?? null, 'INVALID_CONDITION', 'Bitte wählen Sie eine Bedingung aus.');
        $condition = fetch_condition($pdo, $conditionId);
        if ($condition === null) {
            fail(404, 'CONDITION_NOT_FOUND', 'Die Bedingung wurde nicht gefunden.');
        }

        $participationCount = count_rows(
            $pdo,
            'SELECT COUNT(*) AS row_count FROM participations WHERE condition_id = :condition_id',
            ['condition_id' => $conditionId]
        );
        if ($participationCount > 0) {
            fail(409, 'CONDITION_HAS_PARTICIPATIONS', 'Diese Bedingung hat bereits Zuweisungen und kann nicht gelöscht werden.');
        }

        delete_by_id($pdo, 'experiment_conditions', $conditionId);
        json_response(200, ['conditionId' => $conditionId, 'deleted' => true]);
    }

    if ($action === 'save_access_field') {
        $id = nullable_int($payload['id'] ?? null);
        $experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
        $conditionId = ensure_condition_for_experiment($pdo, nullable_int($payload['conditionId'] ?? null), $experimentId);
        $label = clean_text($payload['label'] ?? '');
        $fieldKey = clean_text($payload['fieldKey'] ?? '');
        $valueType = clean_text($payload['valueType'] ?? 'text');
        $valueSource = clean_text($payload['valueSource'] ?? 'shared');
        $sharedValue = clean_text($payload['sharedValue'] ?? '');
        $sortOrder = (int) ($payload['sortOrder'] ?? 0);

        if (fetch_experiment($pdo, $experimentId) === null) {
            fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
        }
        if ($label === '') {
            fail(422, 'INVALID_LABEL', 'Bitte geben Sie eine Feldbezeichnung ein.');
        }
        if ($fieldKey === '') {
            $fieldKey = field_key_from_label($label);
        }
        if (!preg_match('/^[a-z0-9_]{1,64}$/', $fieldKey)) {
            fail(422, 'INVALID_FIELD_KEY', 'Der Feldschlüssel darf nur Kleinbuchstaben, Zahlen und Unterstriche enthalten.');
        }
        if (!is_valid_value_type($valueType)) {
            fail(422, 'INVALID_VALUE_TYPE', 'Der Feldtyp ist ungültig.');
        }
        if (!is_valid_value_source($valueSource)) {
            fail(422, 'INVALID_VALUE_SOURCE', 'Die Datenquelle ist ungültig.');
        }
        if ($valueSource !== 'shared') {
            $sharedValue = '';
        }

        $existingField = null;
        if ($id !== null) {
            $lookup = $pdo->prepare(
                'SELECT *
                 FROM access_fields
                 WHERE id = :id
                   AND experiment_id = :experiment_id
                 LIMIT 1'
            );
            $lookup->execute([
                'id' => $id,
                'experiment_id' => $experimentId,
            ]);
            $existingField = $lookup->fetch();
            if ($existingField === false) {
                fail(404, 'FIELD_NOT_FOUND', 'Das Zugangsfeld wurde nicht gefunden.');
            }
        }

        $idFilter = $id === null ? '' : ' AND id <> :existing_id';
        if ($conditionId === null) {
            $duplicate = $pdo->prepare(
                'SELECT id
                 FROM access_fields
                 WHERE experiment_id = :experiment_id
                   AND condition_id IS NULL
                   AND field_key = :field_key' . $idFilter . '
                 LIMIT 1'
            );
            $duplicateParams = [
                'experiment_id' => $experimentId,
                'field_key' => $fieldKey,
            ];
        } else {
            $duplicate = $pdo->prepare(
                'SELECT id
                 FROM access_fields
                 WHERE experiment_id = :experiment_id
                   AND condition_id = :condition_id
                   AND field_key = :field_key' . $idFilter . '
                 LIMIT 1'
            );
            $duplicateParams = [
                'experiment_id' => $experimentId,
                'condition_id' => $conditionId,
                'field_key' => $fieldKey,
            ];
        }
        if ($id !== null) {
            $duplicateParams['existing_id'] = $id;
        }
        $duplicate->execute($duplicateParams);
        if ($duplicate->fetch() !== false) {
            fail(409, 'FIELD_KEY_EXISTS', 'Dieser Feldschlüssel ist für diese Auswahl bereits vorhanden.');
        }

        if ($id === null) {
            $statement = $pdo->prepare(
                'INSERT INTO access_fields
                    (experiment_id, condition_id, field_key, label, value_type, value_source, shared_value, is_visible, sort_order)
                 VALUES
                    (:experiment_id, :condition_id, :field_key, :label, :value_type, :value_source, :shared_value, :is_visible, :sort_order)'
            );
            $statement->execute([
                'experiment_id' => $experimentId,
                'condition_id' => $conditionId,
                'field_key' => $fieldKey,
                'label' => $label,
                'value_type' => $valueType,
                'value_source' => $valueSource,
                'shared_value' => $sharedValue !== '' ? $sharedValue : null,
                'is_visible' => bool_value($payload['isVisible'] ?? true) ? 1 : 0,
                'sort_order' => $sortOrder,
            ]);
            json_response(201, ['fieldId' => (int) $pdo->lastInsertId()]);
        }

        if (
            $existingField !== null
            && access_field_has_runtime_values($pdo, $id)
            && (
                nullable_int($existingField['condition_id'] ?? null) !== $conditionId
                || (string) $existingField['field_key'] !== $fieldKey
                || (string) $existingField['value_type'] !== $valueType
                || (string) $existingField['value_source'] !== $valueSource
            )
        ) {
            fail(409, 'FIELD_RUNTIME_CONTRACT_LOCKED', 'Dieses Zugangsfeld wird bereits in Zuweisungen verwendet. Bedingung, Schlüssel, Typ und Quelle können nicht mehr geändert werden.');
        }

        $statement = $pdo->prepare(
            'UPDATE access_fields
             SET condition_id = :condition_id,
                 field_key = :field_key,
                 label = :label,
                 value_type = :value_type,
                 value_source = :value_source,
                 shared_value = :shared_value,
                 is_visible = :is_visible,
                 sort_order = :sort_order
             WHERE id = :id
               AND experiment_id = :experiment_id'
        );
        $statement->execute([
            'id' => $id,
            'experiment_id' => $experimentId,
            'condition_id' => $conditionId,
            'field_key' => $fieldKey,
            'label' => $label,
            'value_type' => $valueType,
            'value_source' => $valueSource,
            'shared_value' => $sharedValue !== '' ? $sharedValue : null,
            'is_visible' => bool_value($payload['isVisible'] ?? true) ? 1 : 0,
            'sort_order' => $sortOrder,
        ]);
        json_response(200, ['fieldId' => $id]);
    }

    if ($action === 'delete_access_field') {
        $fieldId = required_int($payload['fieldId'] ?? null, 'INVALID_FIELD', 'Bitte wählen Sie ein Zugangsfeld aus.');
        if (access_field_has_runtime_values($pdo, $fieldId)) {
            fail(409, 'FIELD_HAS_RUNTIME_VALUES', 'Dieses Zugangsfeld wird bereits in Zuweisungen verwendet und kann nicht gelöscht werden.');
        }

        if (!delete_by_id($pdo, 'access_fields', $fieldId)) {
            fail(404, 'FIELD_NOT_FOUND', 'Das Zugangsfeld wurde nicht gefunden.');
        }

        json_response(200, ['fieldId' => $fieldId, 'deleted' => true]);
    }

    if ($action === 'import_pool_rows') {
        $experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
        $conditionId = ensure_condition_for_experiment($pdo, nullable_int($payload['conditionId'] ?? null), $experimentId);
        require_valid_pool_import_scope($pdo, $experimentId, $conditionId);
        $rawTable = (string) ($payload['table'] ?? '');
        [$headers, $rows] = parse_table_lines($rawTable);
        if ($rows === []) {
            fail(422, 'EMPTY_POOL_ROWS', 'Bitte fügen Sie mindestens eine Pool-Zeile ein.');
        }
        $fields = fetch_pool_fields_for_import($pdo, $experimentId, $conditionId);

        $fieldByHeader = [];
        foreach ($fields as $field) {
            $fieldByHeader[strtolower((string) $field['field_key'])] = $field;
            $fieldByHeader[strtolower((string) $field['label'])] = $field;
        }

        $matchedFields = [];
        foreach ($headers as $header) {
            if (isset($fieldByHeader[$header])) {
                $matchedFields[(int) $fieldByHeader[$header]['id']] = $fieldByHeader[$header];
            }
        }

        foreach ($fields as $field) {
            if (!isset($matchedFields[(int) $field['id']])) {
                fail(422, 'MISSING_POOL_FIELD', 'Die Import-Tabelle enthält nicht alle Pool-Felder.', [
                    'missingField' => $field['field_key'],
                ]);
            }
        }

        if ($conditionId === null) {
            $assignedCount = count_rows(
                $pdo,
                'SELECT COUNT(*) AS row_count
                 FROM access_pool_rows
                 WHERE experiment_id = :experiment_id
                   AND condition_id IS NULL
                   AND is_assigned = 1',
                ['experiment_id' => $experimentId]
            );
        } else {
            $assignedCount = count_rows(
                $pdo,
                'SELECT COUNT(*) AS row_count
                 FROM access_pool_rows
                 WHERE experiment_id = :experiment_id
                   AND condition_id = :condition_id
                   AND is_assigned = 1',
                ['experiment_id' => $experimentId, 'condition_id' => $conditionId]
            );
        }
        if ($assignedCount > 0) {
            fail(409, 'POOL_HAS_ASSIGNMENTS', 'Dieser Pool kann nicht ersetzt werden, weil bereits Zugangsdaten zugewiesen wurden.');
        }

        $pdo->beginTransaction();
        if ($conditionId === null) {
            $deleteValues = $pdo->prepare(
                'DELETE FROM access_pool_values
                 WHERE pool_row_id IN (
                     SELECT id
                     FROM access_pool_rows
                     WHERE experiment_id = :experiment_id
                       AND condition_id IS NULL
                 )'
            );
            $deleteValues->execute(['experiment_id' => $experimentId]);
            $deleteRows = $pdo->prepare(
                'DELETE FROM access_pool_rows
                 WHERE experiment_id = :experiment_id
                   AND condition_id IS NULL'
            );
            $deleteRows->execute(['experiment_id' => $experimentId]);
        } else {
            $deleteValues = $pdo->prepare(
                'DELETE FROM access_pool_values
                 WHERE pool_row_id IN (
                     SELECT id
                     FROM access_pool_rows
                     WHERE experiment_id = :experiment_id
                       AND condition_id = :condition_id
                 )'
            );
            $deleteValues->execute(['experiment_id' => $experimentId, 'condition_id' => $conditionId]);
            $deleteRows = $pdo->prepare(
                'DELETE FROM access_pool_rows
                 WHERE experiment_id = :experiment_id
                   AND condition_id = :condition_id'
            );
            $deleteRows->execute(['experiment_id' => $experimentId, 'condition_id' => $conditionId]);
        }

        $poolInsert = $pdo->prepare(
            'INSERT INTO access_pool_rows (experiment_id, condition_id)
             VALUES (:experiment_id, :condition_id)'
        );
        $valueInsert = $pdo->prepare(
            'INSERT INTO access_pool_values (pool_row_id, field_id, field_value)
             VALUES (:pool_row_id, :field_id, :field_value)'
        );

        $imported = 0;
        foreach ($rows as $row) {
            $poolInsert->execute([
                'experiment_id' => $experimentId,
                'condition_id' => $conditionId,
            ]);
            $poolRowId = (int) $pdo->lastInsertId();

            foreach ($matchedFields as $field) {
                $headerKey = strtolower((string) $field['field_key']);
                $labelKey = strtolower((string) $field['label']);
                $value = $row[$headerKey] ?? $row[$labelKey] ?? '';
                if ($value === '') {
                    $pdo->rollBack();
                    fail(422, 'EMPTY_POOL_VALUE', 'Eine importierte Zeile enthält leere Pool-Werte.');
                }
                $valueInsert->execute([
                    'pool_row_id' => $poolRowId,
                    'field_id' => (int) $field['id'],
                    'field_value' => $value,
                ]);
            }
            $imported++;
        }

        $pdo->commit();
        json_response(201, ['imported' => $imported]);
    }

    if ($action === 'delete_pool_row') {
        $poolRowId = required_int($payload['poolRowId'] ?? null, 'INVALID_POOL_ROW', 'Bitte wählen Sie eine Pool-Zeile aus.');
        $lookup = $pdo->prepare(
            'SELECT is_assigned
             FROM access_pool_rows
             WHERE id = :id
             LIMIT 1'
        );
        $lookup->execute(['id' => $poolRowId]);
        $poolRow = $lookup->fetch();
        if ($poolRow === false) {
            fail(404, 'POOL_ROW_NOT_FOUND', 'Die Pool-Zeile wurde nicht gefunden.');
        }
        if (bool_value($poolRow['is_assigned'])) {
            fail(409, 'POOL_ROW_ASSIGNED', 'Diese Pool-Zeile ist bereits zugewiesen und kann nicht gelöscht werden.');
        }

        delete_by_id($pdo, 'access_pool_rows', $poolRowId);
        json_response(200, ['poolRowId' => $poolRowId, 'deleted' => true]);
    }

    if ($action === 'clear_access_pool_rows') {
        $experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
        if (fetch_experiment($pdo, $experimentId) === null) {
            fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
        }
        $assignedCount = count_rows(
            $pdo,
            'SELECT COUNT(*) AS row_count
             FROM access_pool_rows
             WHERE experiment_id = :experiment_id
               AND is_assigned = 1',
            ['experiment_id' => $experimentId]
        );
        if ($assignedCount > 0) {
            fail(409, 'POOL_HAS_ASSIGNMENTS', 'Dieser Pool kann nicht gelöscht werden, weil bereits Zugangsdaten zugewiesen wurden.');
        }

        $rowCount = count_rows(
            $pdo,
            'SELECT COUNT(*) AS row_count
             FROM access_pool_rows
             WHERE experiment_id = :experiment_id',
            ['experiment_id' => $experimentId]
        );

        $pdo->beginTransaction();
        $deleteValues = $pdo->prepare(
            'DELETE FROM access_pool_values
             WHERE pool_row_id IN (
                 SELECT id
                 FROM access_pool_rows
                 WHERE experiment_id = :experiment_id
             )'
        );
        $deleteValues->execute(['experiment_id' => $experimentId]);
        $deleteRows = $pdo->prepare('DELETE FROM access_pool_rows WHERE experiment_id = :experiment_id');
        $deleteRows->execute(['experiment_id' => $experimentId]);
        $pdo->commit();

        json_response(200, ['experimentId' => $experimentId, 'deletedCount' => $rowCount]);
    }

    if ($action === 'save_slot') {
        $id = nullable_int($payload['id'] ?? null);
        $experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
        $label = clean_text($payload['label'] ?? '');
        $capacity = max(1, (int) ($payload['capacity'] ?? 1));
        $isUndated = bool_value($payload['isUndated'] ?? false);
        $startsAt = $isUndated ? null : normalized_optional_datetime($payload['startsAt'] ?? null, 'INVALID_SLOT_START');
        $endsAt = $isUndated ? null : normalized_optional_datetime($payload['endsAt'] ?? null, 'INVALID_SLOT_END');
        $sortOrder = (int) ($payload['sortOrder'] ?? 0);

        if (fetch_experiment($pdo, $experimentId) === null) {
            fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
        }
        if ($label === '') {
            fail(422, 'INVALID_SLOT_LABEL', 'Bitte geben Sie eine Bezeichnung für den Zeitslot ein.');
        }
        if (!$isUndated && ($startsAt === null || $endsAt === null)) {
            fail(422, 'SLOT_DATES_REQUIRED', 'Datierte Zeitslots benötigen einen Start- und Endzeitpunkt. Wählen Sie sonst „Ohne Termin“ aus.');
        }
        if (!$isUndated && strtotime((string) $startsAt) >= strtotime((string) $endsAt)) {
            fail(422, 'INVALID_SLOT_WINDOW', 'Der Startzeitpunkt des Zeitslots muss vor dem Endzeitpunkt liegen.');
        }

        if ($id !== null) {
            $lookup = $pdo->prepare(
                'SELECT id
                 FROM time_slots
                 WHERE id = :id
                   AND experiment_id = :experiment_id
                 LIMIT 1'
            );
            $lookup->execute([
                'id' => $id,
                'experiment_id' => $experimentId,
            ]);
            if ($lookup->fetch() === false) {
                fail(404, 'SLOT_NOT_FOUND', 'Der Zeitslot wurde nicht gefunden.');
            }

            $choiceCount = count_rows(
                $pdo,
                'SELECT COUNT(*) AS row_count FROM slot_choices WHERE time_slot_id = :time_slot_id',
                ['time_slot_id' => $id]
            );
            if ($choiceCount > $capacity) {
                fail(409, 'SLOT_CAPACITY_TOO_LOW', 'Die Kapazität darf nicht unter die Anzahl bestehender Auswahlen fallen.');
            }
        }

        if ($id === null) {
            $statement = $pdo->prepare(
                'INSERT INTO time_slots
                    (experiment_id, label, starts_at, ends_at, capacity, is_active, is_undated, sort_order)
                 VALUES
                    (:experiment_id, :label, :starts_at, :ends_at, :capacity, :is_active, :is_undated, :sort_order)'
            );
            $statement->execute([
                'experiment_id' => $experimentId,
                'label' => $label,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'capacity' => $capacity,
                'is_active' => bool_value($payload['isActive'] ?? true) ? 1 : 0,
                'is_undated' => $isUndated ? 1 : 0,
                'sort_order' => $sortOrder,
            ]);
            json_response(201, ['slotId' => (int) $pdo->lastInsertId()]);
        }

        $statement = $pdo->prepare(
            'UPDATE time_slots
             SET label = :label,
                 starts_at = :starts_at,
                 ends_at = :ends_at,
                 capacity = :capacity,
                 is_active = :is_active,
                 is_undated = :is_undated,
                 sort_order = :sort_order
             WHERE id = :id
               AND experiment_id = :experiment_id'
        );
        $statement->execute([
            'id' => $id,
            'experiment_id' => $experimentId,
            'label' => $label,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'capacity' => $capacity,
            'is_active' => bool_value($payload['isActive'] ?? true) ? 1 : 0,
            'is_undated' => $isUndated ? 1 : 0,
            'sort_order' => $sortOrder,
        ]);
        json_response(200, ['slotId' => $id]);
    }

    if ($action === 'delete_slot') {
        $slotId = required_int($payload['slotId'] ?? null, 'INVALID_SLOT', 'Bitte wählen Sie einen Zeitslot aus.');
        $choiceCount = count_rows(
            $pdo,
            'SELECT COUNT(*) AS row_count FROM slot_choices WHERE time_slot_id = :time_slot_id',
            ['time_slot_id' => $slotId]
        );
        if ($choiceCount > 0) {
            fail(409, 'SLOT_HAS_CHOICES', 'Dieser Zeitslot wurde bereits gewählt und kann nicht gelöscht werden.');
        }

        if (!delete_by_id($pdo, 'time_slots', $slotId)) {
            fail(404, 'SLOT_NOT_FOUND', 'Der Zeitslot wurde nicht gefunden.');
        }
        json_response(200, ['slotId' => $slotId, 'deleted' => true]);
    }

    if ($action === 'save_eligibility_selection') {
        $experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
        $mode = clean_text($payload['mode'] ?? 'selected');
        if (!is_valid_eligibility_mode($mode)) {
            fail(422, 'INVALID_ELIGIBILITY_MODE', 'Der Freigabemodus ist ungültig.');
        }
        if (fetch_experiment($pdo, $experimentId) === null) {
            fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
        }

        if ($mode === 'all_allowed') {
            $statement = $pdo->prepare('UPDATE experiments SET eligibility_mode = :mode WHERE id = :id');
            $statement->execute(['id' => $experimentId, 'mode' => $mode]);
            $selectedCount = count_rows($pdo, 'SELECT COUNT(*) AS row_count FROM allowed_students', []);
            json_response(200, ['mode' => $mode, 'selectedCount' => $selectedCount]);
        }

        $emails = normalized_email_array($payload['emails'] ?? []);
        require_allowed_email_list($pdo, $emails, $experimentId);
        $emailSet = array_fill_keys($emails, true);
        foreach (participation_emails_for_experiment($pdo, $experimentId) as $participationEmail) {
            if (!isset($emailSet[$participationEmail])) {
                fail(409, 'ELIGIBILITY_SELECTION_HAS_PARTICIPATIONS', 'Studierende mit bestehenden Zuweisungen müssen in der Experimentfreigabe bleiben.');
            }
        }

        $pdo->beginTransaction();
        $statement = $pdo->prepare('UPDATE experiments SET eligibility_mode = :mode WHERE id = :id');
        $statement->execute(['id' => $experimentId, 'mode' => $mode]);

        if ($emails === []) {
            $deleteValues = $pdo->prepare(
                'DELETE FROM eligibility_field_values
                 WHERE eligibility_id IN (
                     SELECT id
                     FROM experiment_eligibilities
                     WHERE experiment_id = :experiment_id
                 )'
            );
            $deleteValues->execute(['experiment_id' => $experimentId]);
            $delete = $pdo->prepare('DELETE FROM experiment_eligibilities WHERE experiment_id = :experiment_id');
            $delete->execute(['experiment_id' => $experimentId]);
        } else {
            $placeholders = implode(', ', array_fill(0, count($emails), '?'));
            $deleteValues = $pdo->prepare(
                'DELETE FROM eligibility_field_values
                 WHERE eligibility_id IN (
                     SELECT id
                     FROM experiment_eligibilities
                     WHERE experiment_id = ?
                       AND student_email NOT IN (' . $placeholders . ')
                 )'
            );
            $deleteValues->execute(array_merge([$experimentId], $emails));
            $delete = $pdo->prepare(
                'DELETE FROM experiment_eligibilities
                 WHERE experiment_id = ?
                   AND student_email NOT IN (' . $placeholders . ')'
            );
            $delete->execute(array_merge([$experimentId], $emails));
        }

        foreach ($emails as $email) {
            upsert_eligibility($pdo, $experimentId, $email, null, 'manual', true);
        }
        $pdo->commit();
        json_response(200, ['mode' => $mode, 'selectedCount' => count($emails)]);
    }

    if ($action === 'clear_eligibility_selection') {
        $experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
        if (fetch_experiment($pdo, $experimentId) === null) {
            fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
        }
        if (participation_count_for_experiment($pdo, $experimentId) > 0) {
            fail(409, 'ELIGIBILITY_SELECTION_HAS_PARTICIPATIONS', 'Diese Auswahl kann nicht aufgehoben werden, weil bereits Studierende eine Zuweisung haben.');
        }

        $pdo->beginTransaction();
        $update = $pdo->prepare('UPDATE experiments SET eligibility_mode = :mode WHERE id = :id');
        $update->execute(['id' => $experimentId, 'mode' => 'selected']);
        $deleteValues = $pdo->prepare(
            'DELETE FROM eligibility_field_values
             WHERE eligibility_id IN (
                 SELECT id
                 FROM experiment_eligibilities
                 WHERE experiment_id = :experiment_id
             )'
        );
        $deleteValues->execute(['experiment_id' => $experimentId]);
        $deleteEligibilities = $pdo->prepare('DELETE FROM experiment_eligibilities WHERE experiment_id = :experiment_id');
        $deleteEligibilities->execute(['experiment_id' => $experimentId]);
        delete_randomization_runs_for_experiment($pdo, $experimentId);
        $pdo->commit();

        json_response(200, ['experimentId' => $experimentId, 'cleared' => true, 'selectedCount' => 0]);
    }

    if ($action === 'save_condition_assignments') {
        $experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
        $source = clean_text($payload['source'] ?? 'manual');
        $assignments = is_array($payload['assignments'] ?? null) ? $payload['assignments'] : [];
        $experiment = fetch_experiment($pdo, $experimentId);
        if ($experiment === null) {
            fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
        }
        if ($experiment['condition_mode'] !== 'assigned') {
            fail(409, 'CONDITION_ASSIGNMENT_DISABLED', 'Dieses Experiment verwendet keine zugewiesenen Bedingungen.');
        }
        if (!in_array($source, ['manual', 'random'], true)) {
            fail(422, 'INVALID_ASSIGNMENT_SOURCE', 'Die Zuweisungsquelle ist ungültig.');
        }
        if ($assignments === []) {
            fail(422, 'ASSIGNMENTS_REQUIRED', 'Bitte geben Sie mindestens eine Bedingungszuweisung an.');
        }

        $normalizedAssignments = [];
        foreach ($assignments as $assignment) {
            $email = normalize_student_email((string) ($assignment['email'] ?? ''));
            require_allowed_student($pdo, $email);
            $conditionId = ensure_condition_for_experiment($pdo, nullable_int($assignment['conditionId'] ?? null), $experimentId);
            if ($conditionId === null) {
                fail(422, 'CONDITION_REQUIRED', 'Jede Zuweisung benötigt eine Bedingung.');
            }
            if ($experiment['eligibility_mode'] === 'selected' && fetch_eligibility($pdo, $experimentId, $email) === null) {
                fail(409, 'STUDENT_NOT_SELECTED', 'Diese Person ist nicht für das Experiment freigegeben.');
            }
            $participation = fetch_participation($pdo, $experimentId, $email);
            if ($participation !== null && nullable_int($participation['condition_id'] ?? null) !== $conditionId) {
                fail(409, 'CONDITION_ASSIGNMENT_HAS_PARTICIPATION', 'Bedingungen von Studierenden mit bestehenden Zuweisungen können nicht geändert werden.');
            }
            $normalizedAssignments[$email] = $conditionId;
        }

        $pdo->beginTransaction();
        foreach ($normalizedAssignments as $email => $conditionId) {
            upsert_eligibility($pdo, $experimentId, $email, $conditionId, $source, false);
        }
        $pdo->commit();
        json_response(200, ['assignedCount' => count($normalizedAssignments)]);
    }

    if ($action === 'clear_condition_assignments') {
        $experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
        $experiment = fetch_experiment($pdo, $experimentId);
        if ($experiment === null) {
            fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
        }
        if (participation_count_for_experiment($pdo, $experimentId) > 0) {
            fail(409, 'CONDITION_ASSIGNMENT_HAS_PARTICIPATIONS', 'Diese Bedingungszuweisung kann nicht aufgehoben werden, weil bereits Studierende eine Zuweisung haben.');
        }

        $pdo->beginTransaction();
        $deleteValues = $pdo->prepare(
            'DELETE FROM eligibility_field_values
             WHERE eligibility_id IN (
                 SELECT id
                 FROM experiment_eligibilities
                 WHERE experiment_id = :experiment_id
             )'
        );
        $deleteValues->execute(['experiment_id' => $experimentId]);
        if ($experiment['eligibility_mode'] === 'all_allowed') {
            $deleteEligibilities = $pdo->prepare('DELETE FROM experiment_eligibilities WHERE experiment_id = :experiment_id');
            $deleteEligibilities->execute(['experiment_id' => $experimentId]);
        } else {
            $clearAssignments = $pdo->prepare(
                'UPDATE experiment_eligibilities
                 SET condition_id = NULL,
                     source = :source
                 WHERE experiment_id = :experiment_id'
            );
            $clearAssignments->execute([
                'experiment_id' => $experimentId,
                'source' => 'manual',
            ]);
        }
        delete_randomization_runs_for_experiment($pdo, $experimentId);
        $pdo->commit();

        json_response(200, ['experimentId' => $experimentId, 'cleared' => true]);
    }

    if ($action === 'save_staff_eligibility_field_values') {
        $experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
        $rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];
        $experiment = fetch_experiment($pdo, $experimentId);
        if ($experiment === null) {
            fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
        }
        if (participation_count_for_experiment($pdo, $experimentId) > 0) {
            fail(409, 'STAFF_VALUES_HAVE_PARTICIPATIONS', 'Staff-Zugangsdaten können nicht mehr geändert werden, nachdem Studierende Zugang geöffnet haben.');
        }

        $fieldLookup = $pdo->prepare(
            'SELECT *
             FROM access_fields
             WHERE experiment_id = :experiment_id
               AND value_source = :value_source
               AND value_type <> :appointment_type
             ORDER BY sort_order ASC, id ASC'
        );
        $fieldLookup->execute([
            'experiment_id' => $experimentId,
            'value_source' => 'staff_entry',
            'appointment_type' => 'appointment',
        ]);
        $staffFields = [];
        foreach ($fieldLookup->fetchAll() as $field) {
            $staffFields[(int) $field['id']] = $field;
        }
        if ($staffFields === []) {
            fail(422, 'NO_STAFF_ENTRY_FIELDS', 'Für dieses Experiment sind keine Staff-Zugangsfelder definiert.');
        }

        $normalizedRows = [];
        foreach ($rows as $row) {
            $email = normalize_student_email((string) ($row['email'] ?? ''));
            require_allowed_student($pdo, $email);
            $eligibility = fetch_eligibility($pdo, $experimentId, $email);
            if ($experiment['eligibility_mode'] === 'selected' && $eligibility === null) {
                fail(409, 'STUDENT_NOT_SELECTED', 'Diese Person ist nicht für das Experiment freigegeben.');
            }
            $conditionId = $eligibility !== null ? nullable_int($eligibility['condition_id'] ?? null) : null;

            $values = is_array($row['values'] ?? null) ? $row['values'] : [];
            $normalizedValues = [];
            foreach ($values as $valueRow) {
                $fieldId = required_int($valueRow['fieldId'] ?? null, 'INVALID_FIELD', 'Bitte wählen Sie ein Zugangsfeld aus.');
                if (!isset($staffFields[$fieldId])) {
                    fail(422, 'FIELD_NOT_STAFF_ENTRY', 'Dieses Feld kann nicht durch Staff vorbereitet werden.');
                }
                $fieldConditionId = nullable_int($staffFields[$fieldId]['condition_id'] ?? null);
                if ($fieldConditionId !== null && $fieldConditionId !== $conditionId) {
                    fail(422, 'FIELD_NOT_APPLICABLE', 'Dieses Feld gehört nicht zur Bedingung der ausgewählten Person.');
                }
                $normalizedValues[$fieldId] = clean_text($valueRow['value'] ?? '');
            }

            $normalizedRows[$email] = $normalizedValues;
        }

        $pdo->beginTransaction();
        $eligibilityLookup = $pdo->prepare(
            'SELECT *
             FROM experiment_eligibilities
             WHERE experiment_id = :experiment_id
               AND student_email = :student_email
             LIMIT 1'
        );
        $valueLookup = $pdo->prepare(
            'SELECT id
             FROM eligibility_field_values
             WHERE eligibility_id = :eligibility_id
               AND field_id = :field_id
             LIMIT 1'
        );
        $insertValue = $pdo->prepare(
            'INSERT INTO eligibility_field_values (eligibility_id, field_id, field_value)
             VALUES (:eligibility_id, :field_id, :field_value)'
        );
        $updateValue = $pdo->prepare(
            'UPDATE eligibility_field_values
             SET field_value = :field_value
             WHERE eligibility_id = :eligibility_id
               AND field_id = :field_id'
        );
        $deleteValue = $pdo->prepare(
            'DELETE FROM eligibility_field_values
             WHERE eligibility_id = :eligibility_id
               AND field_id = :field_id'
        );

        $savedCount = 0;
        foreach ($normalizedRows as $email => $values) {
            $eligibilityLookup->execute([
                'experiment_id' => $experimentId,
                'student_email' => $email,
            ]);
            $eligibility = $eligibilityLookup->fetch();
            $hasValue = count(array_filter($values, static fn (string $fieldValue): bool => $fieldValue !== '')) > 0;
            if ($eligibility === false && !$hasValue) {
                continue;
            }
            if ($eligibility === false) {
                upsert_eligibility($pdo, $experimentId, $email, null, 'manual', true);
                $eligibilityLookup->execute([
                    'experiment_id' => $experimentId,
                    'student_email' => $email,
                ]);
                $eligibility = $eligibilityLookup->fetch();
                if ($eligibility === false) {
                    throw new RuntimeException('Eligibility upsert failed.');
                }
            }
            $eligibilityId = (int) $eligibility['id'];

            foreach ($values as $fieldId => $fieldValue) {
                $valueLookup->execute([
                    'eligibility_id' => $eligibilityId,
                    'field_id' => $fieldId,
                ]);
                $existingValue = $valueLookup->fetch();

                if ($fieldValue === '') {
                    $deleteValue->execute([
                        'eligibility_id' => $eligibilityId,
                        'field_id' => $fieldId,
                    ]);
                    continue;
                }

                $statement = $existingValue === false ? $insertValue : $updateValue;
                $statement->execute([
                    'eligibility_id' => $eligibilityId,
                    'field_id' => $fieldId,
                    'field_value' => $fieldValue,
                ]);
                $savedCount++;
            }
        }
        $pdo->commit();

        json_response(200, ['experimentId' => $experimentId, 'savedCount' => $savedCount]);
    }

    if ($action === 'assign_student') {
        $email = normalize_student_email((string) ($payload['email'] ?? ''));
        $experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
        $conditionId = ensure_condition_for_experiment($pdo, nullable_int($payload['conditionId'] ?? null), $experimentId);

        require_allowed_student($pdo, $email);
        $student = fetch_allowed_student($pdo, $email);
        if ($student === null || !student_group_is_eligible($pdo, $experimentId, (int) $student['group_id'])) {
            fail(422, 'STUDENT_OUTSIDE_COURSE_AUDIENCE', 'Diese E-Mail-Adresse gehört nicht zur Kursfreigabe des Experiments.');
        }
        if (fetch_experiment($pdo, $experimentId) === null) {
            fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
        }

        $existing = fetch_eligibility($pdo, $experimentId, $email);
        if ($existing === null) {
            $statement = $pdo->prepare(
                'INSERT INTO experiment_eligibilities (experiment_id, student_email, condition_id, source)
                 VALUES (:experiment_id, :student_email, :condition_id, :source)'
            );
        } else {
            $statement = $pdo->prepare(
                'UPDATE experiment_eligibilities
                 SET condition_id = :condition_id,
                     source = :source
                 WHERE experiment_id = :experiment_id
                   AND student_email = :student_email'
            );
        }
        $statement->execute([
            'experiment_id' => $experimentId,
            'student_email' => $email,
            'condition_id' => $conditionId,
            'source' => 'manual',
        ]);

        json_response(200, ['email' => $email, 'experimentId' => $experimentId, 'conditionId' => $conditionId]);
    }

    if ($action === 'delete_eligibility') {
        $eligibilityId = required_int($payload['eligibilityId'] ?? null, 'INVALID_ELIGIBILITY', 'Bitte wählen Sie eine Freigabe aus.');
        $deleteValues = $pdo->prepare('DELETE FROM eligibility_field_values WHERE eligibility_id = :eligibility_id');
        $deleteValues->execute(['eligibility_id' => $eligibilityId]);
        if (!delete_by_id($pdo, 'experiment_eligibilities', $eligibilityId)) {
            fail(404, 'ELIGIBILITY_NOT_FOUND', 'Die Freigabe wurde nicht gefunden.');
        }

        json_response(200, ['eligibilityId' => $eligibilityId, 'deleted' => true]);
    }

    if ($action === 'randomize') {
        $experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
        $seed = clean_text($payload['seed'] ?? '');
        $allocations = is_array($payload['allocations'] ?? null) ? $payload['allocations'] : [];

        if ($seed === '') {
            fail(422, 'SEED_REQUIRED', 'Bitte geben Sie einen Seed für die Randomisierung ein.');
        }
        if (fetch_experiment($pdo, $experimentId) === null) {
            fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
        }
        if ($allocations === []) {
            fail(422, 'ALLOCATIONS_REQUIRED', 'Bitte geben Sie mindestens eine Bedingung mit Prozentwert an.');
        }

        $participationCount = $pdo->prepare(
            'SELECT COUNT(*) AS row_count
             FROM participations
             WHERE experiment_id = :experiment_id'
        );
        $participationCount->execute(['experiment_id' => $experimentId]);
        if ((int) ($participationCount->fetch()['row_count'] ?? 0) > 0) {
            fail(409, 'RANDOMIZE_HAS_PARTICIPATIONS', 'Dieses Experiment hat bereits Zuweisungen und kann nicht neu randomisiert werden.');
        }

        $normalizedAllocations = [];
        foreach ($allocations as $allocation) {
            $conditionId = ensure_condition_for_experiment($pdo, nullable_int($allocation['conditionId'] ?? null), $experimentId);
            if ($conditionId === null) {
                fail(422, 'INVALID_ALLOCATION', 'Jede Randomisierung benötigt eine Bedingung.');
            }
            $percentage = (float) ($allocation['percentage'] ?? 0);
            if ($percentage <= 0) {
                fail(422, 'INVALID_PERCENTAGE', 'Alle Prozentwerte müssen größer als 0 sein.');
            }
            $normalizedAllocations[] = [
                'conditionId' => $conditionId,
                'percentage' => $percentage,
            ];
        }

        $emails = [];
        foreach ($pdo->query('SELECT student_email, group_id FROM allowed_students ORDER BY student_email ASC')->fetchAll() as $row) {
            if (student_group_is_eligible($pdo, $experimentId, (int) $row['group_id'])) {
                $emails[] = (string) $row['student_email'];
            }
        }
        $emails = deterministic_email_order($emails, $seed);
        $counts = allocation_counts($normalizedAllocations, count($emails));

        $pdo->beginTransaction();
        $deleteValues = $pdo->prepare(
            'DELETE FROM eligibility_field_values
             WHERE eligibility_id IN (
                 SELECT id
                 FROM experiment_eligibilities
                 WHERE experiment_id = :experiment_id
             )'
        );
        $deleteValues->execute(['experiment_id' => $experimentId]);
        $delete = $pdo->prepare('DELETE FROM experiment_eligibilities WHERE experiment_id = :experiment_id');
        $delete->execute(['experiment_id' => $experimentId]);

        $runInsert = $pdo->prepare(
            'INSERT INTO randomization_runs (experiment_id, seed, total_students)
             VALUES (:experiment_id, :seed, :total_students)'
        );
        $runInsert->execute([
            'experiment_id' => $experimentId,
            'seed' => $seed,
            'total_students' => count($emails),
        ]);
        $runId = (int) $pdo->lastInsertId();

        $eligibilityInsert = $pdo->prepare(
            'INSERT INTO experiment_eligibilities (experiment_id, student_email, condition_id, source)
             VALUES (:experiment_id, :student_email, :condition_id, :source)'
        );
        $allocationInsert = $pdo->prepare(
            'INSERT INTO randomization_run_allocations (run_id, condition_id, percentage, assigned_count)
             VALUES (:run_id, :condition_id, :percentage, :assigned_count)'
        );

        $emailIndex = 0;
        foreach ($normalizedAllocations as $index => $allocation) {
            $assignedCount = $counts[$index] ?? 0;
            for ($i = 0; $i < $assignedCount; $i++) {
                if (!isset($emails[$emailIndex])) {
                    break;
                }
                $eligibilityInsert->execute([
                    'experiment_id' => $experimentId,
                    'student_email' => $emails[$emailIndex],
                    'condition_id' => $allocation['conditionId'],
                    'source' => 'random',
                ]);
                $emailIndex++;
            }
            $allocationInsert->execute([
                'run_id' => $runId,
                'condition_id' => $allocation['conditionId'],
                'percentage' => $allocation['percentage'],
                'assigned_count' => $assignedCount,
            ]);
        }
        $pdo->commit();

        json_response(200, ['runId' => $runId, 'totalStudents' => count($emails)]);
    }

    if ($action === 'bulk_grading_operation') {
        $experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
        if (fetch_experiment($pdo, $experimentId) === null) {
            fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
        }

        $operation = clean_text($payload['operation'] ?? '');
        if (!in_array($operation, ['confirm', 'unconfirm', 'reset'], true)) {
            fail(422, 'INVALID_BULK_OPERATION', 'Bitte wählen Sie eine gültige Sammelaktion aus.');
        }

        $participationIds = required_int_list(
            $payload['participationIds'] ?? null,
            'INVALID_PARTICIPATIONS',
            'Bitte wählen Sie mindestens eine Zuweisung aus.'
        );
        $idParams = [];
        $idSql = bind_int_list($participationIds, 'participation_id_', $idParams);
        $scopedParams = ['experiment_id' => $experimentId] + $idParams;
        $lookup = $pdo->prepare(
            'SELECT id, access_pool_row_id
             FROM participations
             WHERE experiment_id = :experiment_id
               AND id IN (' . $idSql . ')'
        );
        $lookup->execute($scopedParams);
        $participations = $lookup->fetchAll();
        if (count($participations) !== count($participationIds)) {
            fail(422, 'PARTICIPATION_SCOPE_MISMATCH', 'Die Auswahl enthält Zuweisungen aus einem anderen Experiment oder nicht mehr vorhandene Zuweisungen.');
        }

        if ($operation === 'confirm' || $operation === 'unconfirm') {
            $pdo->beginTransaction();
            $affectedCount = 0;
            $creditedRewards = [];
            foreach ($participationIds as $participationId) {
                $result = update_participation_confirmation($pdo, $participationId, $operation === 'confirm');
                if (bool_value($result['changed'] ?? false)) {
                    $affectedCount++;
                }
                if ($operation === 'confirm') {
                    $creditedRewards[(string) $participationId] = $result['creditedReward'] ?? 0;
                }
            }
            $pdo->commit();
            json_response(200, [
                'operation' => $operation,
                'selectedCount' => count($participationIds),
                'affectedCount' => $affectedCount,
                'creditedRewards' => $creditedRewards,
            ]);
        }

        $pdo->beginTransaction();
        $releasePoolRows = $pdo->prepare(
            'UPDATE access_pool_rows
             SET is_assigned = 0,
                 assigned_participation_id = NULL,
                 assigned_at = NULL
             WHERE assigned_participation_id IN (' . $idSql . ')'
        );
        $releasePoolRows->execute($idParams);
        $releasedAccessCount = $releasePoolRows->rowCount();

        $deleteFieldValues = $pdo->prepare(
            'DELETE FROM participation_field_values
             WHERE participation_id IN (' . $idSql . ')'
        );
        $deleteFieldValues->execute($idParams);

        $deleteAppointments = $pdo->prepare(
            'DELETE FROM appointments
             WHERE participation_id IN (' . $idSql . ')'
        );
        $deleteAppointments->execute($idParams);

        $deleteSlotChoices = $pdo->prepare(
            'DELETE FROM slot_choices
             WHERE participation_id IN (' . $idSql . ')'
        );
        $deleteSlotChoices->execute($idParams);

        $deleteParticipations = $pdo->prepare(
            'DELETE FROM participations
             WHERE experiment_id = :experiment_id
               AND id IN (' . $idSql . ')'
        );
        $deleteParticipations->execute($scopedParams);
        $affectedCount = $deleteParticipations->rowCount();
        $pdo->commit();

        json_response(200, [
            'operation' => $operation,
            'selectedCount' => count($participationIds),
            'affectedCount' => $affectedCount,
            'releasedAccessCount' => $releasedAccessCount,
        ]);
    }

    if ($action === 'toggle_confirmation') {
        $participationId = required_int($payload['participationId'] ?? null, 'INVALID_PARTICIPATION', 'Bitte wählen Sie eine Zuweisung aus.');
        $statement = $pdo->prepare('SELECT confirmed_at FROM participations WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $participationId]);
        $row = $statement->fetch();
        if ($row === false) {
            fail(404, 'PARTICIPATION_NOT_FOUND', 'Die Zuweisung wurde nicht gefunden.');
        }

        $confirmed = ($row['confirmed_at'] ?? null) === null;
        $pdo->beginTransaction();
        $result = update_participation_confirmation($pdo, $participationId, $confirmed);
        $pdo->commit();
        json_response(200, [
            'participationId' => $participationId,
            'confirmed' => $confirmed,
            'creditedReward' => $result['creditedReward'] ?? null,
        ]);
    }

    if ($action === 'save_appointment') {
        $participationId = required_int($payload['participationId'] ?? null, 'INVALID_PARTICIPATION', 'Bitte wählen Sie eine Zuweisung aus.');
        $appointmentText = clean_text($payload['appointmentText'] ?? '');

        $exists = $pdo->prepare('SELECT id FROM participations WHERE id = :id LIMIT 1');
        $exists->execute(['id' => $participationId]);
        if ($exists->fetch() === false) {
            fail(404, 'PARTICIPATION_NOT_FOUND', 'Die Zuweisung wurde nicht gefunden.');
        }

        $appointmentLookup = $pdo->prepare('SELECT id FROM appointments WHERE participation_id = :participation_id LIMIT 1');
        $appointmentLookup->execute(['participation_id' => $participationId]);
        $appointment = $appointmentLookup->fetch();

        if ($appointmentText === '') {
            $delete = $pdo->prepare('DELETE FROM appointments WHERE participation_id = :participation_id');
            $delete->execute(['participation_id' => $participationId]);
            json_response(200, ['participationId' => $participationId, 'appointmentText' => null]);
        }

        if ($appointment === false) {
            $statement = $pdo->prepare(
                'INSERT INTO appointments (participation_id, appointment_text)
                 VALUES (:participation_id, :appointment_text)'
            );
        } else {
            $statement = $pdo->prepare(
                'UPDATE appointments
                 SET appointment_text = :appointment_text
                 WHERE participation_id = :participation_id'
            );
        }
        $statement->execute([
            'participation_id' => $participationId,
            'appointment_text' => $appointmentText,
        ]);
        json_response(200, ['participationId' => $participationId, 'appointmentText' => $appointmentText]);
    }


    if ($action === 'reset_participation') {
        $participationId = required_int($payload['participationId'] ?? null, 'INVALID_PARTICIPATION', 'Bitte wählen Sie eine Zuweisung aus.');
        $releaseAccess = bool_value($payload['releaseAccess'] ?? true);

        $statement = $pdo->prepare('SELECT * FROM participations WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $participationId]);
        $participation = $statement->fetch();
        if ($participation === false) {
            fail(404, 'PARTICIPATION_NOT_FOUND', 'Die Zuweisung wurde nicht gefunden.');
        }

        $pdo->beginTransaction();
        $deleteFieldValues = $pdo->prepare('DELETE FROM participation_field_values WHERE participation_id = :participation_id');
        $deleteFieldValues->execute(['participation_id' => $participationId]);

        $poolRowId = nullable_int($participation['access_pool_row_id'] ?? null);
        if ($poolRowId !== null) {
            $release = $pdo->prepare(
                'UPDATE access_pool_rows
                 SET is_assigned = :is_assigned,
                     assigned_participation_id = NULL,
                     assigned_at = :assigned_at
                 WHERE id = :id'
            );
            $release->execute([
                'id' => $poolRowId,
                'is_assigned' => $releaseAccess ? 0 : 1,
                'assigned_at' => $releaseAccess ? null : ($participation['assigned_at'] ?? null),
            ]);
        }

        $delete = $pdo->prepare('DELETE FROM participations WHERE id = :id');
        $delete->execute(['id' => $participationId]);
        $pdo->commit();

        json_response(200, ['participationId' => $participationId, 'releasedAccess' => $releaseAccess && $poolRowId !== null]);
    }
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fail(500, 'MANAGEMENT_ACTION_FAILED', 'Die Aktion konnte nicht abgeschlossen werden.');
}

fail(400, 'UNKNOWN_ACTION', 'Diese Verwaltungsaktion ist unbekannt.');
