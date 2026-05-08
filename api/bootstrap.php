<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

require_method('GET');

$token = current_session_token();
if ($token === null) {
    json_response(200, ['assignment' => null]);
}

$assignment = fetch_assignment_by_token(db(), $token);
json_response(200, ['assignment' => $assignment]);
