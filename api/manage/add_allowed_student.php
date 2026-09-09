<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$adminAuth = require_admin_authentication();
require_csrf_token($adminAuth);

$payload = read_json_body();
$email = normalize_student_email((string) ($payload['email'] ?? ''));
$groupId = required_int($payload['groupId'] ?? null, 'INVALID_STUDENT_GROUP', 'Bitte wählen Sie einen Kurs aus.');

if (!is_valid_student_email($email)) {
    fail(422, 'INVALID_EMAIL', 'Bitte geben Sie eine gültige Studierenden-E-Mail-Adresse ein.');
}

$pdo = db();
schedule_successful_audit_event($pdo, 'admin', 'admin', 'add_allowed_student', 'student', $email);
$groupLookup = $pdo->prepare('SELECT id FROM student_groups WHERE id = :id LIMIT 1');
$groupLookup->execute(['id' => $groupId]);
if ($groupLookup->fetch() === false) {
    fail(404, 'STUDENT_GROUP_NOT_FOUND', 'Der Kurs wurde nicht gefunden.');
}
if (is_allowed_student_email($pdo, $email)) {
    $student = fetch_allowed_student($pdo, $email);
    if ($student !== null && (int) $student['group_id'] !== $groupId) {
        $participations = $pdo->prepare('SELECT COUNT(*) FROM participations WHERE student_email = :student_email');
        $participations->execute(['student_email' => $email]);
        if ((int) $participations->fetchColumn() > 0) {
            fail(409, 'STUDENT_GROUP_HAS_PARTICIPATIONS', 'Der Kurs kann nach der ersten Experimentzuweisung nicht mehr geändert werden.');
        }
    }
    $update = $pdo->prepare('UPDATE allowed_students SET group_id = :group_id WHERE student_email = :student_email');
    $update->execute(['group_id' => $groupId, 'student_email' => $email]);
    json_response(200, [
        'email' => $email,
        'groupId' => $groupId,
        'created' => false,
    ]);
}

try {
    $insert = $pdo->prepare('INSERT INTO allowed_students (student_email, group_id) VALUES (:student_email, :group_id)');
    $insert->execute(['student_email' => $email, 'group_id' => $groupId]);
} catch (Throwable $throwable) {
    fail(500, 'ALLOWLIST_UPDATE_FAILED', 'Die E-Mail-Adresse konnte nicht zur Zulassungsliste hinzugefügt werden.');
}

json_response(201, [
    'email' => $email,
    'groupId' => $groupId,
    'created' => true,
]);
