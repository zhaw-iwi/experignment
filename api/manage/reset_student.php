<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');

$payload = read_json_body();
$email = normalize_student_email((string) ($payload['email'] ?? ''));
$releaseAssignment = (bool) ($payload['releaseAssignment'] ?? false);

if (!is_valid_student_email($email)) {
    fail(422, 'INVALID_EMAIL', 'Bitte geben Sie eine gültige Studierenden-E-Mail-Adresse ein.');
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $linkLookup = $pdo->prepare(
        'SELECT assignment_item_id
         FROM participant_assignment_links
         WHERE student_email = :student_email
         LIMIT 1'
    );
    $linkLookup->execute([
        'student_email' => $email,
    ]);
    $linkRow = $linkLookup->fetch();
    $assignmentItemId = $linkRow !== false ? (int) $linkRow['assignment_item_id'] : null;

    $deleteParticipant = $pdo->prepare(
        'DELETE FROM participant_credits
         WHERE student_email = :student_email'
    );
    $deleteParticipant->execute([
        'student_email' => $email,
    ]);
    $participantDeleted = $deleteParticipant->rowCount() > 0;

    if ($assignmentItemId !== null) {
        $deleteToken = $pdo->prepare(
            'DELETE FROM browser_tokens
             WHERE assignment_item_id = :assignment_item_id'
        );
        $deleteToken->execute([
            'assignment_item_id' => $assignmentItemId,
        ]);

        $deleteLink = $pdo->prepare(
            'DELETE FROM participant_assignment_links
             WHERE student_email = :student_email'
        );
        $deleteLink->execute([
            'student_email' => $email,
        ]);

        if ($releaseAssignment) {
            $release = $pdo->prepare(
                'UPDATE assignment_items
                 SET is_assigned = 0, assigned_at = NULL
                 WHERE id = :assignment_item_id'
            );
            $release->execute([
                'assignment_item_id' => $assignmentItemId,
            ]);
        }
    }

    if (!$participantDeleted && $assignmentItemId === null) {
        $pdo->rollBack();
        fail(404, 'EMAIL_NOT_FOUND', 'Für diese E-Mail-Adresse gibt es keinen zurücksetzbaren Eintrag.');
    }

    $pdo->commit();
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fail(500, 'RESET_FAILED', 'Der Reset konnte nicht durchgeführt werden.');
}

json_response(200, [
    'email' => $email,
    'releasedAssignment' => $releaseAssignment && $assignmentItemId !== null,
    'resetCompleted' => true,
]);
