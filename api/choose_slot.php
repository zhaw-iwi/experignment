<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

require_method('POST');

$payload = read_json_body();
$email = normalize_student_email((string) ($payload['email'] ?? ''));
$experimentId = required_int($payload['experimentId'] ?? null, 'INVALID_EXPERIMENT', 'Bitte wählen Sie ein Experiment aus.');
$slotId = required_int($payload['slotId'] ?? null, 'INVALID_SLOT', 'Bitte wählen Sie einen Zeitslot aus.');

$pdo = db();
require_allowed_student($pdo, $email);

$experiment = fetch_experiment($pdo, $experimentId);
if ($experiment === null) {
    fail(404, 'EXPERIMENT_NOT_FOUND', 'Das Experiment wurde nicht gefunden.');
}

if (!bool_value($experiment['is_open'])) {
    fail(409, 'EXPERIMENT_CLOSED', 'Dieses Experiment ist aktuell geschlossen.');
}

if (!bool_value($experiment['requires_time_slot'])) {
    fail(422, 'SLOT_NOT_REQUIRED', 'Für dieses Experiment ist keine Zeitslot-Auswahl vorgesehen.');
}

$participation = fetch_participation($pdo, $experimentId, $email);
if ($participation === null) {
    fail(409, 'PARTICIPATION_REQUIRED', 'Bitte öffnen Sie zuerst Ihre Experiment-Informationen.');
}

if (fetch_slot_choice($pdo, (int) $participation['id']) !== null) {
    fail(409, 'SLOT_ALREADY_CHOSEN', 'Sie haben bereits einen Zeitslot gewählt.');
}

try {
    $pdo->beginTransaction();

    $slotStatement = $pdo->prepare(
        'SELECT *
         FROM time_slots
         WHERE id = :id
           AND experiment_id = :experiment_id
           AND is_active = 1
         LIMIT 1' . for_update_sql($pdo)
    );
    $slotStatement->execute([
        'id' => $slotId,
        'experiment_id' => $experimentId,
    ]);
    $slot = $slotStatement->fetch();
    if ($slot === false) {
        $pdo->rollBack();
        fail(404, 'SLOT_NOT_FOUND', 'Der Zeitslot wurde nicht gefunden.');
    }

    $countStatement = $pdo->prepare(
        'SELECT COUNT(*) AS chosen_count
         FROM slot_choices
         WHERE time_slot_id = :time_slot_id'
    );
    $countStatement->execute(['time_slot_id' => $slotId]);
    $countRow = $countStatement->fetch();
    $chosenCount = (int) ($countRow['chosen_count'] ?? 0);
    if ($chosenCount >= (int) $slot['capacity']) {
        $pdo->rollBack();
        fail(409, 'SLOT_FULL', 'Dieser Zeitslot ist bereits ausgebucht.');
    }

    $insert = $pdo->prepare(
        'INSERT INTO slot_choices (participation_id, time_slot_id)
         VALUES (:participation_id, :time_slot_id)'
    );
    $insert->execute([
        'participation_id' => (int) $participation['id'],
        'time_slot_id' => $slotId,
    ]);

    $pdo->commit();
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (fetch_slot_choice($pdo, (int) $participation['id']) !== null) {
        json_response(200, [
            'overview' => student_overview($pdo, $email),
        ]);
    }

    fail(500, 'SLOT_CHOICE_FAILED', 'Der Zeitslot konnte nicht gespeichert werden.');
}

json_response(200, [
    'overview' => student_overview($pdo, $email),
]);
