<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

require_method('POST');

$payload = read_json_body();
$email = normalize_student_email((string) ($payload['email'] ?? ''));
$accessCode = trim((string) ($payload['accessCode'] ?? ''));
$pdo = db();

if (authentication_is_throttled($pdo, 'student', $email)) {
    throttled_authentication_failure();
}

$statement = $pdo->prepare(
    'SELECT student_email, login_code_hash, login_code_version
     FROM allowed_students
     WHERE student_email = :student_email
     LIMIT 1'
);
$statement->execute(['student_email' => $email]);
$student = $statement->fetch();
$storedHash = $student !== false && is_string($student['login_code_hash'] ?? null) && $student['login_code_hash'] !== ''
    ? $student['login_code_hash']
    : DUMMY_ACCESS_CODE_HASH;
$valid = strlen($accessCode) <= 128
    && password_verify($accessCode, $storedHash)
    && $student !== false
    && is_valid_student_email($email)
    && is_string($student['login_code_hash'] ?? null)
    && $student['login_code_hash'] !== '';

if (!$valid) {
    record_authentication_failure($pdo, 'student', $email);
    generic_authentication_failure();
}

clear_authentication_failures($pdo, 'student', $email);
$auth = begin_student_authentication($email, (int) $student['login_code_version']);

json_response(200, [
    'authenticated' => true,
    'email' => $auth['email'],
    'csrfToken' => $auth['csrf_token'],
]);
