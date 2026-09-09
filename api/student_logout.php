<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

require_method('POST');

$auth = require_student_authentication(db());
require_csrf_token($auth);
clear_student_authentication();

json_response(200, ['authenticated' => false]);
