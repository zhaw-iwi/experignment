<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_chests.php';

require_method('GET');

$pdo = db();
$auth = require_student_authentication($pdo);
$rawStatus = $_GET['status'] ?? 'pending';
if (!is_string($rawStatus)) {
    fail(422, 'INVALID_CHEST_STATUS', 'Der angeforderte Truhenstatus ist ungültig.');
}
$status = clean_text($rawStatus);
if (!in_array($status, ['pending', 'history'], true)) {
    fail(422, 'INVALID_CHEST_STATUS', 'Der angeforderte Truhenstatus ist ungültig.');
}

json_response(200, [
    'status' => $status,
    'pendingCount' => pending_student_chest_count($pdo, $auth['email']),
    'events' => fetch_student_chests($pdo, $auth['email'], $status),
]);
