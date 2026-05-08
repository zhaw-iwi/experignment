<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

require_method('POST');

$payload = read_json_body();
$email = normalize_student_email((string) ($payload['email'] ?? ''));
$pool = strtolower(trim((string) ($payload['pool'] ?? '')));
$tabletConfirmed = (bool) ($payload['tabletConfirmed'] ?? false);

if (!is_valid_student_email($email)) {
    fail(422, 'INVALID_EMAIL', 'Bitte verwenden Sie Ihre ZHAW-Studierenden-E-Mail-Adresse.');
}

if (!is_valid_pool($pool)) {
    fail(422, 'INVALID_POOL', 'Die gewählte Variante ist ungültig.');
}

if ($pool === 'tablet' && $tabletConfirmed !== true) {
    fail(422, 'TABLET_CONFIRMATION_REQUIRED', 'Bitte bestätigen Sie vorab, dass das Tablet bereit ist.');
}

$pdo = db();
$currentToken = current_session_token();
if ($currentToken !== null) {
    $existingAssignment = fetch_assignment_by_token($pdo, $currentToken);
    if ($existingAssignment !== null) {
        json_response(200, [
            'assignment' => $existingAssignment,
            'reused' => true,
        ]);
    }
}

if (!is_allowed_student_email($pdo, $email)) {
    fail(422, 'EMAIL_NOT_RECOGNIZED', 'Wir haben die E-Mail-Adresse nicht erkannt.');
}

$participantLookup = $pdo->prepare(
    'SELECT id
     FROM participant_credits
     WHERE student_email = :student_email
     LIMIT 1'
);
$participantLookup->execute([
    'student_email' => $email,
]);

if ($participantLookup->fetch() !== false) {
    fail(
        409,
        'EMAIL_ALREADY_USED',
        'Diese E-Mail-Adresse wurde bereits verwendet. Aus Datenschutzgründen kann der Zugang nur im ursprünglich verwendeten Browser erneut angezeigt werden.'
    );
}

try {
    $pdo->beginTransaction();

    $assignmentRow = random_unassigned_assignment($pdo, $pool);
    if ($assignmentRow === null) {
        $pdo->rollBack();
        fail(409, 'POOL_EXHAUSTED', 'Für diese Variante sind derzeit keine freien Zugänge mehr verfügbar.');
    }

    $markAssigned = $pdo->prepare(
        'UPDATE assignment_items
         SET is_assigned = 1, assigned_at = CURRENT_TIMESTAMP
         WHERE id = :id AND is_assigned = 0'
    );
    $markAssigned->execute([
        'id' => $assignmentRow['id'],
    ]);

    if ($markAssigned->rowCount() !== 1) {
        $pdo->rollBack();
        fail(409, 'ASSIGNMENT_CONFLICT', 'Die Zuweisung konnte nicht eindeutig reserviert werden. Bitte versuchen Sie es erneut.');
    }

    $insertParticipant = $pdo->prepare(
        'INSERT INTO participant_credits (student_email)
         VALUES (:student_email)'
    );
    $insertParticipant->execute([
        'student_email' => $email,
    ]);

    $insertAssignmentLink = $pdo->prepare(
        'INSERT INTO participant_assignment_links (student_email, assignment_item_id)
         VALUES (:student_email, :assignment_item_id)'
    );
    $insertAssignmentLink->execute([
        'student_email' => $email,
        'assignment_item_id' => $assignmentRow['id'],
    ]);

    $token = new_session_token();
    $insertToken = $pdo->prepare(
        'INSERT INTO browser_tokens (token_hash, assignment_item_id)
         VALUES (:token_hash, :assignment_item_id)'
    );
    $insertToken->execute([
        'token_hash' => token_hash_value($token),
        'assignment_item_id' => $assignmentRow['id'],
    ]);

    $pdo->commit();
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fail(500, 'CLAIM_FAILED', 'Die Zuweisung konnte nicht gespeichert werden.');
}

set_session_cookie($token);

json_response(200, [
    'assignment' => assignment_payload($assignmentRow),
    'reused' => false,
]);
