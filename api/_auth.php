<?php

declare(strict_types=1);

const AUTHENTICATION_MAX_FAILURES = 5;
const AUTHENTICATION_WINDOW_SECONDS = 900;
const AUTHENTICATION_LOCK_SECONDS = 900;
const DUMMY_ACCESS_CODE_HASH = '$2y$10$tRO.hRCW83UkMe2vgz1CsOJcy4Af4LZMr3csBRX2xQAoe7TZGxnS2';

function access_code_meets_requirements(string $code): bool
{
    return preg_match('/\A(?=.*[A-Za-z])(?=.*[0-9])[A-Za-z0-9]{5,128}\z/', $code) === 1;
}

function hash_student_access_code(string $code): string
{
    $hash = password_hash($code, PASSWORD_BCRYPT, ['cost' => 10]);
    if (!is_string($hash) || $hash === '') {
        throw new RuntimeException('The student access code could not be hashed.');
    }

    return $hash;
}

function generate_student_access_code(): string
{
    $letters = 'abcdefghjkmnpqrstuvwxyz';
    $digits = '23456789';
    $alphabet = $letters . $digits;
    $characters = [
        $letters[random_int(0, strlen($letters) - 1)],
        $digits[random_int(0, strlen($digits) - 1)],
    ];

    while (count($characters) < 5) {
        $characters[] = $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    for ($index = count($characters) - 1; $index > 0; $index--) {
        $other = random_int(0, $index);
        [$characters[$index], $characters[$other]] = [$characters[$other], $characters[$index]];
    }

    return implode('', $characters);
}

function request_is_https(): bool
{
    if (strtolower((string) ($_SERVER['HTTPS'] ?? '')) === 'on') {
        return true;
    }

    return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function start_application_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $config = app_config()['session'] ?? [];
    $secureSetting = $config['secure'] ?? null;
    $secure = is_bool($secureSetting) ? $secureSetting : request_is_https();

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    session_name((string) ($config['name'] ?? 'experiment_assignment_v3'));
    session_start([
        'cookie_lifetime' => 0,
        'cookie_path' => '/',
        'cookie_secure' => $secure,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'use_only_cookies' => true,
    ]);
}

function session_idle_seconds(string $role): int
{
    $authConfig = app_config()['auth'] ?? [];
    $configured = $role === 'admin'
        ? (int) ($authConfig['admin_idle_seconds'] ?? 7200)
        : (int) ($authConfig['student_idle_seconds'] ?? 28800);

    return max(300, $configured);
}

function session_auth_is_expired(array $auth, string $role): bool
{
    $lastActive = (int) ($auth['last_active_at'] ?? 0);
    return $lastActive <= 0 || (time() - $lastActive) > session_idle_seconds($role);
}

function clear_student_authentication(): void
{
    start_application_session();
    unset($_SESSION['student_auth']);
}

function clear_admin_authentication(): void
{
    start_application_session();
    unset($_SESSION['admin_auth']);
}

function current_student_authentication(PDO $pdo): ?array
{
    start_application_session();
    $auth = $_SESSION['student_auth'] ?? null;
    if (!is_array($auth) || session_auth_is_expired($auth, 'student')) {
        clear_student_authentication();
        return null;
    }

    $email = normalize_student_email((string) ($auth['email'] ?? ''));
    $statement = $pdo->prepare(
        'SELECT student_email, login_code_hash, login_code_version
         FROM allowed_students
         WHERE student_email = :student_email
         LIMIT 1'
    );
    $statement->execute(['student_email' => $email]);
    $student = $statement->fetch();

    if (
        $student === false
        || !is_string($student['login_code_hash'] ?? null)
        || ($student['login_code_hash'] ?? '') === ''
        || (int) ($student['login_code_version'] ?? -1) !== (int) ($auth['code_version'] ?? -2)
    ) {
        clear_student_authentication();
        return null;
    }

    $_SESSION['student_auth']['last_active_at'] = time();
    return [
        'email' => $email,
        'csrf_token' => (string) ($auth['csrf_token'] ?? ''),
        'code_version' => (int) $student['login_code_version'],
    ];
}

function require_student_authentication(PDO $pdo): array
{
    $auth = current_student_authentication($pdo);
    if ($auth === null) {
        fail(401, 'STUDENT_AUTHENTICATION_REQUIRED', 'Bitte melden Sie sich mit Ihrer E-Mail-Adresse und Ihrem Zugangscode an.');
    }
    return $auth;
}

function current_admin_authentication(): ?array
{
    start_application_session();
    $auth = $_SESSION['admin_auth'] ?? null;
    if (!is_array($auth) || session_auth_is_expired($auth, 'admin')) {
        clear_admin_authentication();
        return null;
    }

    $_SESSION['admin_auth']['last_active_at'] = time();
    return [
        'csrf_token' => (string) ($auth['csrf_token'] ?? ''),
    ];
}

function require_admin_authentication(): array
{
    $auth = current_admin_authentication();
    if ($auth === null) {
        fail(401, 'ADMIN_AUTHENTICATION_REQUIRED', 'Bitte melden Sie sich mit dem Administrationscode an.');
    }
    return $auth;
}

function require_csrf_token(array $auth): void
{
    $expected = (string) ($auth['csrf_token'] ?? '');
    $provided = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
        fail(403, 'CSRF_TOKEN_INVALID', 'Die Sicherheitsprüfung ist fehlgeschlagen. Bitte laden Sie die Seite neu.');
    }
}

function begin_student_authentication(string $email, int $codeVersion): array
{
    start_application_session();
    session_regenerate_id(true);
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['student_auth'] = [
        'email' => normalize_student_email($email),
        'code_version' => $codeVersion,
        'csrf_token' => $csrfToken,
        'last_active_at' => time(),
    ];

    return ['email' => normalize_student_email($email), 'csrf_token' => $csrfToken];
}

function begin_admin_authentication(): array
{
    start_application_session();
    session_regenerate_id(true);
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['admin_auth'] = [
        'csrf_token' => $csrfToken,
        'last_active_at' => time(),
    ];

    return ['csrf_token' => $csrfToken];
}

function authentication_subject_hash(string $type, string $identifier): string
{
    return hash('sha256', $type . '|' . strtolower(trim($identifier)));
}

function authentication_is_throttled(PDO $pdo, string $type, string $identifier): bool
{
    $statement = $pdo->prepare(
        'SELECT locked_until
         FROM authentication_throttles
         WHERE subject_type = :subject_type
           AND subject_hash = :subject_hash
         LIMIT 1'
    );
    $statement->execute([
        'subject_type' => $type,
        'subject_hash' => authentication_subject_hash($type, $identifier),
    ]);
    $row = $statement->fetch();
    if ($row === false || !is_string($row['locked_until'] ?? null) || $row['locked_until'] === '') {
        return false;
    }

    return strtotime($row['locked_until'] . ' UTC') > time();
}

function record_authentication_failure(PDO $pdo, string $type, string $identifier): void
{
    $subjectHash = authentication_subject_hash($type, $identifier);
    $statement = $pdo->prepare(
        'SELECT failed_attempts, window_started_at
         FROM authentication_throttles
         WHERE subject_type = :subject_type
           AND subject_hash = :subject_hash
         LIMIT 1'
    );
    $statement->execute(['subject_type' => $type, 'subject_hash' => $subjectHash]);
    $row = $statement->fetch();

    $now = time();
    $windowStartedAt = $row !== false && is_string($row['window_started_at'] ?? null)
        ? strtotime($row['window_started_at'] . ' UTC')
        : false;
    $windowExpired = $windowStartedAt === false || ($now - $windowStartedAt) > AUTHENTICATION_WINDOW_SECONDS;
    $failedAttempts = $windowExpired ? 1 : ((int) ($row['failed_attempts'] ?? 0) + 1);
    $windowValue = gmdate('Y-m-d H:i:s', $windowExpired ? $now : $windowStartedAt);
    $lockedUntil = $failedAttempts >= AUTHENTICATION_MAX_FAILURES
        ? gmdate('Y-m-d H:i:s', $now + AUTHENTICATION_LOCK_SECONDS)
        : null;

    if ($row === false) {
        $insert = $pdo->prepare(
            'INSERT INTO authentication_throttles
                (subject_type, subject_hash, failed_attempts, window_started_at, locked_until)
             VALUES
                (:subject_type, :subject_hash, :failed_attempts, :window_started_at, :locked_until)'
        );
        $insert->execute([
            'subject_type' => $type,
            'subject_hash' => $subjectHash,
            'failed_attempts' => $failedAttempts,
            'window_started_at' => $windowValue,
            'locked_until' => $lockedUntil,
        ]);
        return;
    }

    $update = $pdo->prepare(
        'UPDATE authentication_throttles
         SET failed_attempts = :failed_attempts,
             window_started_at = :window_started_at,
             locked_until = :locked_until
         WHERE subject_type = :subject_type
           AND subject_hash = :subject_hash'
    );
    $update->execute([
        'subject_type' => $type,
        'subject_hash' => $subjectHash,
        'failed_attempts' => $failedAttempts,
        'window_started_at' => $windowValue,
        'locked_until' => $lockedUntil,
    ]);
}

function clear_authentication_failures(PDO $pdo, string $type, string $identifier): void
{
    $statement = $pdo->prepare(
        'DELETE FROM authentication_throttles
         WHERE subject_type = :subject_type
           AND subject_hash = :subject_hash'
    );
    $statement->execute([
        'subject_type' => $type,
        'subject_hash' => authentication_subject_hash($type, $identifier),
    ]);
}

function generic_authentication_failure(): never
{
    fail(401, 'AUTHENTICATION_FAILED', 'Die Anmeldung ist fehlgeschlagen. Bitte prüfen Sie Ihre Zugangsdaten.');
}

function throttled_authentication_failure(): never
{
    fail(429, 'AUTHENTICATION_THROTTLED', 'Die Anmeldung ist vorübergehend gesperrt. Bitte versuchen Sie es später erneut.');
}
