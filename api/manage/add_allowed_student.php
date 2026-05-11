<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');

$payload = read_json_body();
$email = normalize_student_email((string) ($payload['email'] ?? ''));

if (!is_valid_student_email($email)) {
    fail(422, 'INVALID_EMAIL', 'Bitte geben Sie eine gültige Studierenden-E-Mail-Adresse ein.');
}

$pdo = db();
if (is_allowed_student_email($pdo, $email)) {
    json_response(200, [
        'email' => $email,
        'created' => false,
    ]);
}

try {
    $insert = $pdo->prepare('INSERT INTO allowed_students (student_email) VALUES (:student_email)');
    $insert->execute(['student_email' => $email]);
} catch (Throwable $throwable) {
    fail(500, 'ALLOWLIST_UPDATE_FAILED', 'Die E-Mail-Adresse konnte nicht zur Zulassungsliste hinzugefügt werden.');
}

json_response(201, [
    'email' => $email,
    'created' => true,
]);
