<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');

$auth = require_admin_authentication();
require_csrf_token($auth);
schedule_successful_audit_event(db(), 'admin', 'admin', 'admin_logout', 'session', null);
clear_admin_authentication();

json_response(200, ['authenticated' => false]);
