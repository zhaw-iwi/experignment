<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

require_method('POST');

$payload = read_json_body();
$email = normalize_student_email((string) ($payload['email'] ?? ''));
$experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
$requestedConditionId = nullable_int($payload['conditionId'] ?? null);

$pdo = db();
require_allowed_student($pdo, $email);

$experiment = fetch_experiment($pdo, $experimentId);
if ($experiment === null) {
    fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
}

if (!bool_value($experiment['is_open'])) {
    fail(409, 'EXPERIMENT_CLOSED', 'Dieses Experiment ist aktuell geschlossen.');
}

$eligibility = fetch_eligibility($pdo, $experimentId, $email);
if (!student_is_eligible($experiment, $eligibility)) {
    fail(403, 'NOT_ELIGIBLE', 'Dieses Experiment ist für Sie nicht freigegeben.');
}

$existingParticipation = fetch_participation($pdo, $experimentId, $email);
if ($existingParticipation !== null) {
    json_response(200, [
        'reused' => true,
        'overview' => student_overview($pdo, $email),
    ]);
}

$conditionId = resolve_participation_condition($pdo, $experiment, $eligibility, $requestedConditionId);
$poolRow = null;

try {
    $pdo->beginTransaction();

    if (access_fields_require_pool($pdo, $experimentId, $conditionId)) {
        $poolRow = select_available_pool_row($pdo, $experimentId, $conditionId);
        if ($poolRow === null) {
            $pdo->rollBack();
            fail(409, 'ACCESS_POOL_EXHAUSTED', 'Für dieses Experiment sind aktuell keine freien Zugangsdaten verfügbar.');
        }
    }

    $insert = $pdo->prepare(
        'INSERT INTO participations (experiment_id, condition_id, student_email, access_pool_row_id)
         VALUES (:experiment_id, :condition_id, :student_email, :access_pool_row_id)'
    );
    $insert->execute([
        'experiment_id' => $experimentId,
        'condition_id' => $conditionId,
        'student_email' => $email,
        'access_pool_row_id' => $poolRow !== null ? (int) $poolRow['id'] : null,
    ]);
    $participationId = (int) $pdo->lastInsertId();

    if ($poolRow !== null) {
        $updatePool = $pdo->prepare(
            'UPDATE access_pool_rows
             SET is_assigned = 1,
                 assigned_participation_id = :participation_id,
                 assigned_at = CURRENT_TIMESTAMP
             WHERE id = :id
               AND is_assigned = 0'
        );
        $updatePool->execute([
            'participation_id' => $participationId,
            'id' => (int) $poolRow['id'],
        ]);

        if ($updatePool->rowCount() !== 1) {
            $pdo->rollBack();
            fail(409, 'ASSIGNMENT_CONFLICT', 'Die Zugangsdaten konnten nicht eindeutig reserviert werden. Bitte versuchen Sie es erneut.');
        }
    }

    $pdo->commit();
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $participationAfterConflict = fetch_participation($pdo, $experimentId, $email);
    if ($participationAfterConflict !== null) {
        json_response(200, [
            'reused' => true,
            'overview' => student_overview($pdo, $email),
        ]);
    }

    fail(500, 'CLAIM_FAILED', 'Die Zuweisung konnte nicht gespeichert werden.');
}

json_response(200, [
    'reused' => false,
    'overview' => student_overview($pdo, $email),
]);
