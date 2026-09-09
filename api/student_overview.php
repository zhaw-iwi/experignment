<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

require_method('GET');

$pdo = db();
$auth = require_student_authentication($pdo);
$overview = student_overview($pdo, $auth['email']);

json_response(200, $overview);
