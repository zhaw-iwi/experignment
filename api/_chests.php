<?php

declare(strict_types=1);

const STUDENT_CHEST_EVENT_PARTICIPATION_CREDITED = 'participation_credited';
const STUDENT_CHEST_DEFAULT_VARIANT = 'gold';
const STUDENT_CHEST_PAGE_LIMIT = 100;

function participation_chest_trigger_scope(int $participationId): string
{
    return 'participation:' . $participationId;
}

function find_participation_chest(PDO $pdo, int $participationId, bool $lock = false): ?array
{
    $statement = $pdo->prepare(
        'SELECT *
         FROM student_chest_events
         WHERE event_type = :event_type
           AND trigger_scope = :trigger_scope
         LIMIT 1' . ($lock ? for_update_sql($pdo) : '')
    );
    $statement->execute([
        'event_type' => STUDENT_CHEST_EVENT_PARTICIPATION_CREDITED,
        'trigger_scope' => participation_chest_trigger_scope($participationId),
    ]);
    $row = $statement->fetch();

    return $row !== false ? $row : null;
}

function reactivate_unopened_participation_chest(
    PDO $pdo,
    array $existing,
    int $participationId,
    string $studentEmail,
    string $experimentName
): array {
    if (($existing['opened_at'] ?? null) !== null) {
        return ['created' => false, 'reactivated' => false, 'eventId' => (int) $existing['id']];
    }

    if (($existing['revoked_at'] ?? null) === null) {
        return ['created' => false, 'reactivated' => false, 'eventId' => (int) $existing['id']];
    }

    $statement = $pdo->prepare(
        'UPDATE student_chest_events
         SET student_email = :student_email,
             source_participation_id = :source_participation_id,
             experiment_name_snapshot = :experiment_name_snapshot,
             earned_at = CURRENT_TIMESTAMP,
             revoked_at = NULL
         WHERE id = :id
           AND opened_at IS NULL'
    );
    $statement->execute([
        'id' => (int) $existing['id'],
        'student_email' => $studentEmail,
        'source_participation_id' => $participationId,
        'experiment_name_snapshot' => $experimentName,
    ]);

    return ['created' => false, 'reactivated' => true, 'eventId' => (int) $existing['id']];
}

function create_or_reactivate_participation_chest(
    PDO $pdo,
    int $participationId,
    string $studentEmail,
    string $experimentName
): array {
    $existing = find_participation_chest($pdo, $participationId, true);
    if ($existing !== null) {
        return reactivate_unopened_participation_chest(
            $pdo,
            $existing,
            $participationId,
            $studentEmail,
            $experimentName
        );
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO student_chest_events
                (student_email, source_participation_id, event_type, trigger_scope, variant, experiment_name_snapshot)
             VALUES
                (:student_email, :source_participation_id, :event_type, :trigger_scope, :variant, :experiment_name_snapshot)'
        );
        $statement->execute([
            'student_email' => $studentEmail,
            'source_participation_id' => $participationId,
            'event_type' => STUDENT_CHEST_EVENT_PARTICIPATION_CREDITED,
            'trigger_scope' => participation_chest_trigger_scope($participationId),
            'variant' => STUDENT_CHEST_DEFAULT_VARIANT,
            'experiment_name_snapshot' => $experimentName,
        ]);

        return ['created' => true, 'reactivated' => false, 'eventId' => (int) $pdo->lastInsertId()];
    } catch (PDOException $exception) {
        if ((string) $exception->getCode() !== '23000') {
            throw $exception;
        }

        $existing = find_participation_chest($pdo, $participationId, true);
        if ($existing === null) {
            throw $exception;
        }

        return reactivate_unopened_participation_chest(
            $pdo,
            $existing,
            $participationId,
            $studentEmail,
            $experimentName
        );
    }
}

function revoke_unopened_participation_chests(PDO $pdo, array $participationIds): int
{
    $ids = normalized_participation_ids($participationIds);
    if ($ids === []) {
        return 0;
    }

    $params = ['event_type' => STUDENT_CHEST_EVENT_PARTICIPATION_CREDITED];
    $placeholders = chest_participation_placeholders($ids, $params);

    $statement = $pdo->prepare(
        'UPDATE student_chest_events
         SET revoked_at = COALESCE(revoked_at, CURRENT_TIMESTAMP)
         WHERE event_type = :event_type
           AND source_participation_id IN (' . implode(', ', $placeholders) . ')
           AND opened_at IS NULL
           AND revoked_at IS NULL'
    );
    $statement->execute($params);

    return $statement->rowCount();
}

function normalized_participation_ids(array $participationIds): array
{
    return array_values(array_unique(array_filter(
        array_map(static fn (mixed $id): int => (int) $id, $participationIds),
        static fn (int $id): bool => $id > 0
    )));
}

function chest_participation_placeholders(array $ids, array &$params): array
{
    $placeholders = [];
    foreach ($ids as $index => $id) {
        $key = 'participation_id_' . $index;
        $params[$key] = $id;
        $placeholders[] = ':' . $key;
    }

    return $placeholders;
}

function detach_participation_chests(PDO $pdo, array $participationIds): int
{
    $ids = normalized_participation_ids($participationIds);
    if ($ids === []) {
        return 0;
    }

    $params = [];
    $placeholders = chest_participation_placeholders($ids, $params);
    $statement = $pdo->prepare(
        'UPDATE student_chest_events
         SET source_participation_id = NULL
         WHERE source_participation_id IN (' . implode(', ', $placeholders) . ')'
    );
    $statement->execute($params);

    return $statement->rowCount();
}

function participation_ids_for_experiment(PDO $pdo, int $experimentId, bool $lock = false): array
{
    $statement = $pdo->prepare(
        'SELECT id
         FROM participations
         WHERE experiment_id = :experiment_id
         ORDER BY id ASC' . ($lock ? for_update_sql($pdo) : '')
    );
    $statement->execute(['experiment_id' => $experimentId]);

    return array_map(static fn (array $row): int => (int) $row['id'], $statement->fetchAll());
}

function chest_event_payload(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'eventType' => (string) $row['event_type'],
        'variant' => (string) $row['variant'],
        'experimentName' => (string) $row['experiment_name_snapshot'],
        'earnedAt' => (string) $row['earned_at'],
        'openedAt' => ($row['opened_at'] ?? null) !== null ? (string) $row['opened_at'] : null,
        'revokedAt' => ($row['revoked_at'] ?? null) !== null ? (string) $row['revoked_at'] : null,
    ];
}

function pending_student_chest_count(PDO $pdo, string $studentEmail): int
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM student_chest_events
         WHERE student_email = :student_email
           AND opened_at IS NULL
           AND revoked_at IS NULL'
    );
    $statement->execute(['student_email' => $studentEmail]);

    return (int) $statement->fetchColumn();
}

function fetch_student_chests(PDO $pdo, string $studentEmail, string $status): array
{
    if ($status === 'pending') {
        $where = 'opened_at IS NULL AND revoked_at IS NULL';
        $order = 'earned_at ASC, id ASC';
    } else {
        $where = '(opened_at IS NOT NULL OR revoked_at IS NOT NULL)';
        $order = 'earned_at DESC, id DESC';
    }

    $statement = $pdo->prepare(
        'SELECT id, event_type, variant, experiment_name_snapshot, earned_at, opened_at, revoked_at
         FROM student_chest_events
         WHERE student_email = :student_email
           AND ' . $where . '
         ORDER BY ' . $order . '
         LIMIT ' . STUDENT_CHEST_PAGE_LIMIT
    );
    $statement->execute(['student_email' => $studentEmail]);

    return array_map('chest_event_payload', $statement->fetchAll());
}

function acknowledge_student_chest(PDO $pdo, int $chestId, string $studentEmail): ?array
{
    $statement = $pdo->prepare(
        'SELECT *
         FROM student_chest_events
         WHERE id = :id
           AND student_email = :student_email
         LIMIT 1' . for_update_sql($pdo)
    );
    $statement->execute(['id' => $chestId, 'student_email' => $studentEmail]);
    $event = $statement->fetch();
    if ($event === false) {
        return null;
    }

    if (($event['revoked_at'] ?? null) !== null && ($event['opened_at'] ?? null) === null) {
        return ['revoked' => true, 'changed' => false, 'event' => chest_event_payload($event)];
    }

    $changed = ($event['opened_at'] ?? null) === null;
    if ($changed) {
        $update = $pdo->prepare(
            'UPDATE student_chest_events
             SET opened_at = COALESCE(opened_at, CURRENT_TIMESTAMP)
             WHERE id = :id
               AND student_email = :student_email
               AND revoked_at IS NULL'
        );
        $update->execute(['id' => $chestId, 'student_email' => $studentEmail]);

        $statement->execute(['id' => $chestId, 'student_email' => $studentEmail]);
        $event = $statement->fetch();
        if ($event === false) {
            return null;
        }
    }

    return ['revoked' => false, 'changed' => $changed, 'event' => chest_event_payload($event)];
}
