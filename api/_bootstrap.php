<?php

declare(strict_types=1);

const SESSION_COOKIE_NAME = 'experiment_assignment_token';

require_once __DIR__ . '/../config/config.php';

function app_config(): array
{
    return $GLOBALS['APP_CONFIG'];
}

function json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(int $statusCode, string $errorCode, string $message, array $details = []): void
{
    json_response($statusCode, [
        'error_code' => $errorCode,
        'message' => $message,
        'details' => $details,
    ]);
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    try {
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        fail(400, 'INVALID_JSON', 'Die Anfrage konnte nicht gelesen werden.');
    }

    return is_array($data) ? $data : [];
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = app_config()['db'];
    try {
        $pdo = new PDO(
            $config['dsn'],
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $exception) {
        fail(500, 'DATABASE_UNAVAILABLE', 'Die Datenbankverbindung konnte nicht aufgebaut werden.');
    }

    return $pdo;
}

function request_method_is(string $method): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === strtoupper($method);
}

function require_method(string $method): void
{
    if (!request_method_is($method)) {
        fail(405, 'METHOD_NOT_ALLOWED', 'Diese HTTP-Methode ist hier nicht erlaubt.');
    }
}

function normalize_student_email(string $email): string
{
    return strtolower(trim($email));
}

function is_valid_student_email(string $email): bool
{
    return preg_match('/^[^@\s]+@students\.zhaw\.ch$/i', $email) === 1;
}

function is_valid_pool(string $pool): bool
{
    return in_array($pool, ['text', 'tablet'], true);
}

function is_allowed_student_email(PDO $pdo, string $email): bool
{
    $statement = $pdo->prepare(
        'SELECT id
         FROM allowed_students
         WHERE student_email = :student_email
         LIMIT 1'
    );
    $statement->execute([
        'student_email' => $email,
    ]);

    return $statement->fetch() !== false;
}

function set_session_cookie(string $token): void
{
    $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie(SESSION_COOKIE_NAME, $token, [
        'expires' => time() + (86400 * 180),
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function current_session_token(): ?string
{
    $token = $_COOKIE[SESSION_COOKIE_NAME] ?? null;
    if (!is_string($token) || $token === '') {
        return null;
    }

    return $token;
}

function token_hash_value(string $token): string
{
    return hash('sha256', $token);
}

function new_session_token(): string
{
    return bin2hex(random_bytes(32));
}

function assignment_payload(array $row): array
{
    return [
        'pool' => $row['pool'],
        'pin' => $row['pin'],
        'surveyUrl' => $row['survey_url'],
        'agentUrl' => $row['agent_url'] !== null ? $row['agent_url'] : null,
    ];
}

function fetch_assignment_by_token(PDO $pdo, string $token): ?array
{
    $statement = $pdo->prepare(
        'SELECT ai.pool, ai.pin, ai.survey_url, ai.agent_url
         FROM browser_tokens bt
         INNER JOIN assignment_items ai ON ai.id = bt.assignment_item_id
         WHERE bt.token_hash = :token_hash
         LIMIT 1'
    );
    $statement->execute([
        'token_hash' => token_hash_value($token),
    ]);
    $assignment = $statement->fetch();
    if ($assignment === false) {
        return null;
    }

    $touch = $pdo->prepare(
        'UPDATE browser_tokens
         SET last_seen_at = CURRENT_TIMESTAMP
         WHERE token_hash = :token_hash'
    );
    $touch->execute([
        'token_hash' => token_hash_value($token),
    ]);

    return assignment_payload($assignment);
}

function random_unassigned_assignment(PDO $pdo, string $pool): ?array
{
    $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $orderBy = $driver === 'sqlite' ? 'RANDOM()' : 'RAND()';
    $forUpdate = $driver === 'sqlite' ? '' : ' FOR UPDATE';

    $statement = $pdo->prepare(
        sprintf(
            'SELECT id, pool, pin, survey_url, agent_url
             FROM assignment_items
             WHERE pool = :pool AND is_assigned = 0
             ORDER BY %s
             LIMIT 1%s',
            $orderBy,
            $forUpdate
        )
    );
    $statement->execute([
        'pool' => $pool,
    ]);
    $assignment = $statement->fetch();

    return $assignment !== false ? $assignment : null;
}
