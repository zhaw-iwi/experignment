<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

require_method('POST');
$adminAuth = require_admin_authentication();
require_csrf_token($adminAuth);

if (function_exists('set_time_limit')) {
    @set_time_limit(300);
}

$pdo = db();
$rows = [];

try {
    $pdo->beginTransaction();
    $sql = "SELECT id, student_email
            FROM allowed_students
            WHERE login_code_hash IS NULL OR login_code_hash = ''
            ORDER BY student_email ASC";
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        $sql .= ' FOR UPDATE';
    }
    $students = $pdo->query($sql)->fetchAll();

    if ($students === []) {
        $pdo->rollBack();
        fail(409, 'NO_MISSING_ACCESS_CODES', 'Alle Studierenden haben bereits einen Zugangscode.');
    }

    $update = $pdo->prepare(
        'UPDATE allowed_students
         SET login_code_hash = :login_code_hash,
             login_code_version = login_code_version + 1,
             login_code_set_at = CURRENT_TIMESTAMP
         WHERE id = :id
           AND (login_code_hash IS NULL OR login_code_hash = \'\')'
    );
    $generatedCodes = [];
    foreach ($students as $student) {
        do {
            $accessCode = generate_student_access_code();
        } while (isset($generatedCodes[$accessCode]));
        $generatedCodes[$accessCode] = true;

        $update->execute([
            'login_code_hash' => hash_student_access_code($accessCode),
            'id' => (int) $student['id'],
        ]);
        if ($update->rowCount() !== 1) {
            throw new RuntimeException('Concurrent student access-code update detected.');
        }
        $rows[] = [(string) $student['student_email'], $accessCode];
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fail(500, 'ACCESS_CODE_GENERATION_FAILED', 'Die Zugangscodes konnten nicht erstellt werden.');
}

$stream = fopen('php://temp', 'w+');
if ($stream === false) {
    fail(500, 'CSV_CREATION_FAILED', 'Die CSV-Datei konnte nicht erstellt werden.');
}
fwrite($stream, "\xEF\xBB\xBF");
fputcsv($stream, ['email', 'access_code'], ';', '"', '');
foreach ($rows as $row) {
    fputcsv($stream, $row, ';', '"', '');
}
rewind($stream);
$csv = stream_get_contents($stream);
fclose($stream);
if (!is_string($csv)) {
    fail(500, 'CSV_CREATION_FAILED', 'Die CSV-Datei konnte nicht erstellt werden.');
}

http_response_code(200);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="student-access-codes-' . gmdate('Ymd-His') . '.csv"');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
echo $csv;
