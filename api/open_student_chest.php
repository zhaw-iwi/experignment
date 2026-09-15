<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_chests.php';

require_method('POST');

$pdo = db();
$auth = require_student_authentication($pdo);
require_csrf_token($auth);
$payload = read_json_body();
$unknownFields = array_diff(array_keys($payload), ['chestId']);
if ($unknownFields !== []) {
    fail(422, 'INVALID_CHEST_PAYLOAD', 'Die Anfrage enthält unbekannte Felder.');
}
$chestId = required_int($payload['chestId'] ?? null, 'INVALID_CHEST_ID', 'Bitte wählen Sie eine gültige Truhe aus.');

try {
    $pdo->beginTransaction();
    $result = acknowledge_student_chest($pdo, $chestId, $auth['email']);
    if ($result === null) {
        $pdo->rollBack();
        fail(404, 'CHEST_NOT_FOUND', 'Die Truhe wurde nicht gefunden.');
    }
    if (($result['revoked'] ?? false) === true) {
        $pdo->rollBack();
        fail(409, 'CHEST_REVOKED', 'Diese Truhe ist nicht mehr verfügbar.');
    }
    $pdo->commit();
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Student chest acknowledgement failed for event ' . $chestId . '.');
    fail(500, 'CHEST_ACKNOWLEDGEMENT_FAILED', 'Die Truhe konnte nicht gespeichert werden.');
}

if (($result['changed'] ?? false) === true) {
    schedule_successful_audit_event(
        $pdo,
        'student',
        $auth['email'],
        'student_chest_opened',
        'student_chest_event',
        (string) $chestId
    );
}

json_response(200, [
    'event' => $result['event'],
    'alreadyOpened' => ($result['changed'] ?? false) !== true,
]);
