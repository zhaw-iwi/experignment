<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');

$auth = current_admin_authentication();
json_response(200, $auth === null
    ? ['authenticated' => false]
    : ['authenticated' => true, 'csrfToken' => $auth['csrf_token']]);
