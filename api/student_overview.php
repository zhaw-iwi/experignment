<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

require_method('GET');

$email = normalize_student_email((string) ($_GET['email'] ?? ''));
$overview = student_overview(db(), $email);

json_response(200, $overview);
