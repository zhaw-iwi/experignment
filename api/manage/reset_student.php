<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');

$payload = read_json_body();
$email = normalize_student_email((string) ($payload['email'] ?? ''));
$experimentId = nullable_int($payload['experimentId'] ?? null);
$releaseAccess = bool_value($payload['releaseAssignment'] ?? true);

if (!is_valid_student_email($email)) {
    fail(422, 'INVALID_EMAIL', 'Bitte geben Sie eine gültige Studierenden-E-Mail-Adresse ein.');
}

$pdo = db();

$sql = 'SELECT id, access_pool_row_id, assigned_at
        FROM participations
        WHERE student_email = :student_email';
$params = ['student_email' => $email];
if ($experimentId !== null) {
    $sql .= ' AND experiment_id = :experiment_id';
    $params['experiment_id'] = $experimentId;
}

$statement = $pdo->prepare($sql);
$statement->execute($params);
$participations = $statement->fetchAll();
if ($participations === []) {
    fail(404, 'EMAIL_NOT_FOUND', 'Für diese E-Mail-Adresse gibt es keinen zurücksetzbaren Eintrag.');
}

try {
    $pdo->beginTransaction();

    foreach ($participations as $participation) {
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
        $delete->execute(['id' => (int) $participation['id']]);
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
    'resetCount' => count($participations),
    'releasedAssignment' => $releaseAccess,
    'resetCompleted' => true,
]);
