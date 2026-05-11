<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');

$payload = read_json_body();
$action = clean_text($payload['action'] ?? '');
$pdo = db();

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

    $manualValues = count_rows(
        $pdo,
        'SELECT COUNT(*) AS row_count
         FROM participation_field_values
         WHERE field_id = :field_id',
        ['field_id' => $fieldId]
    );

    return $manualValues > 0;
}

function imported_student_emails(string $raw): array
{
    if (preg_match_all('/[A-Z0-9._%+\-]+@students\.zhaw\.ch/i', $raw, $matches) !== 1) {
        return [];
    }

    $emails = [];
    foreach ($matches[0] as $email) {
        $emails[] = normalize_student_email($email);
    }

    return array_values(array_unique($emails));
}

try {
    if ($action === 'add_allowed_student') {
        $email = normalize_student_email((string) ($payload['email'] ?? ''));
        if (!is_valid_student_email($email)) {
            fail(422, 'INVALID_EMAIL', 'Bitte geben Sie eine gültige Studierenden-E-Mail-Adresse ein.');
        }

        if (is_allowed_student_email($pdo, $email)) {
            json_response(200, ['created' => false, 'email' => $email]);
        }

        $insert = $pdo->prepare('INSERT INTO allowed_students (student_email) VALUES (:student_email)');
        $insert->execute(['student_email' => $email]);
        json_response(201, ['created' => true, 'email' => $email]);
    }

    if ($action === 'bulk_add_allowed_students') {
        $rawEmails = (string) ($payload['emails'] ?? '');
        $emails = imported_student_emails($rawEmails);
        if ($emails === []) {
            fail(422, 'NO_VALID_EMAILS', 'Es wurden keine gültigen Studierenden-E-Mail-Adressen gefunden.');
        }

        $insert = $pdo->prepare('INSERT INTO allowed_students (student_email) VALUES (:student_email)');
        $created = 0;
        $skipped = 0;
        foreach ($emails as $email) {
            if (is_allowed_student_email($pdo, $email)) {
                $skipped++;
                continue;
            }
            $insert->execute(['student_email' => $email]);
            $created++;
        }

        json_response(201, [
            'created' => $created,
            'skipped' => $skipped,
            'totalValid' => count($emails),
        ]);
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
        $eligibilityMode = clean_text($payload['eligibilityMode'] ?? 'selected');
        $conditionMode = clean_text($payload['conditionMode'] ?? 'none');
        $sortOrder = (int) ($payload['sortOrder'] ?? 0);

        if ($name === '') {
            fail(422, 'INVALID_NAME', 'Bitte geben Sie einen Experimentnamen ein.');
        }
        if (!is_valid_eligibility_mode($eligibilityMode)) {
            fail(422, 'INVALID_ELIGIBILITY_MODE', 'Der Freigabemodus ist ungültig.');
        }
        if (!is_valid_condition_mode($conditionMode)) {
            fail(422, 'INVALID_CONDITION_MODE', 'Der Bedingungsmodus ist ungültig.');
        }

        if ($id === null) {
            $statement = $pdo->prepare(
                'INSERT INTO experiments
                    (public_name, description, is_open, eligibility_mode, condition_mode, requires_time_slot, sort_order)
                 VALUES
                    (:public_name, :description, :is_open, :eligibility_mode, :condition_mode, :requires_time_slot, :sort_order)'
            );
            $statement->execute([
                'public_name' => $name,
                'description' => $description !== '' ? $description : null,
                'is_open' => bool_value($payload['isOpen'] ?? false) ? 1 : 0,
                'eligibility_mode' => $eligibilityMode,
                'condition_mode' => $conditionMode,
                'requires_time_slot' => bool_value($payload['requiresTimeSlot'] ?? false) ? 1 : 0,
                'sort_order' => $sortOrder,
            ]);
            json_response(201, ['experimentId' => (int) $pdo->lastInsertId()]);
        }

        if (fetch_experiment($pdo, $id) === null) {
            fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
        }

        $statement = $pdo->prepare(
            'UPDATE experiments
             SET public_name = :public_name,
                 description = :description,
                 is_open = :is_open,
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
            'is_open' => bool_value($payload['isOpen'] ?? false) ? 1 : 0,
            'eligibility_mode' => $eligibilityMode,
            'condition_mode' => $conditionMode,
            'requires_time_slot' => bool_value($payload['requiresTimeSlot'] ?? false) ? 1 : 0,
            'sort_order' => $sortOrder,
        ]);
        json_response(200, ['experimentId' => $id]);
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
        $rawTable = (string) ($payload['table'] ?? '');
        [$headers, $rows] = parse_table_lines($rawTable);
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

        $pdo->beginTransaction();
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

    if ($action === 'save_slot') {
        $id = nullable_int($payload['id'] ?? null);
        $experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
        $label = clean_text($payload['label'] ?? '');
        $capacity = max(1, (int) ($payload['capacity'] ?? 1));
        $startsAt = clean_text($payload['startsAt'] ?? '');
        $endsAt = clean_text($payload['endsAt'] ?? '');
        $sortOrder = (int) ($payload['sortOrder'] ?? 0);

        if (fetch_experiment($pdo, $experimentId) === null) {
            fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
        }
        if ($label === '') {
            fail(422, 'INVALID_SLOT_LABEL', 'Bitte geben Sie eine Bezeichnung für den Zeitslot ein.');
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
                    (experiment_id, label, starts_at, ends_at, capacity, is_active, sort_order)
                 VALUES
                    (:experiment_id, :label, :starts_at, :ends_at, :capacity, :is_active, :sort_order)'
            );
            $statement->execute([
                'experiment_id' => $experimentId,
                'label' => $label,
                'starts_at' => $startsAt !== '' ? $startsAt : null,
                'ends_at' => $endsAt !== '' ? $endsAt : null,
                'capacity' => $capacity,
                'is_active' => bool_value($payload['isActive'] ?? true) ? 1 : 0,
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
                 sort_order = :sort_order
             WHERE id = :id
               AND experiment_id = :experiment_id'
        );
        $statement->execute([
            'id' => $id,
            'experiment_id' => $experimentId,
            'label' => $label,
            'starts_at' => $startsAt !== '' ? $startsAt : null,
            'ends_at' => $endsAt !== '' ? $endsAt : null,
            'capacity' => $capacity,
            'is_active' => bool_value($payload['isActive'] ?? true) ? 1 : 0,
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

    if ($action === 'assign_student') {
        $email = normalize_student_email((string) ($payload['email'] ?? ''));
        $experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
        $conditionId = ensure_condition_for_experiment($pdo, nullable_int($payload['conditionId'] ?? null), $experimentId);

        require_allowed_student($pdo, $email);
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
        foreach ($pdo->query('SELECT student_email FROM allowed_students ORDER BY student_email ASC')->fetchAll() as $row) {
            $emails[] = (string) $row['student_email'];
        }
        $emails = deterministic_email_order($emails, $seed);
        $counts = allocation_counts($normalizedAllocations, count($emails));

        $pdo->beginTransaction();
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

    if ($action === 'toggle_confirmation') {
        $participationId = required_int($payload['participationId'] ?? null, 'INVALID_PARTICIPATION', 'Bitte wählen Sie eine Zuweisung aus.');
        $statement = $pdo->prepare('SELECT confirmed_at FROM participations WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $participationId]);
        $row = $statement->fetch();
        if ($row === false) {
            fail(404, 'PARTICIPATION_NOT_FOUND', 'Die Zuweisung wurde nicht gefunden.');
        }

        $confirmed = ($row['confirmed_at'] ?? null) === null;
        $update = $pdo->prepare(
            'UPDATE participations
             SET confirmed_at = ' . ($confirmed ? 'CURRENT_TIMESTAMP' : 'NULL') . '
             WHERE id = :id'
        );
        $update->execute(['id' => $participationId]);
        json_response(200, ['participationId' => $participationId, 'confirmed' => $confirmed]);
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
