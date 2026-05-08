<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');

$query = normalize_student_email((string) ($_GET['q'] ?? ''));
if ($query === '' || strlen($query) < 2) {
    json_response(200, ['students' => []]);
}

$like = $query . '%';
$pdo = db();

$statement = $pdo->prepare(
    'SELECT pc.student_email,
            pal.assignment_item_id,
            ai.pool,
            ai.pin
     FROM participant_credits pc
     LEFT JOIN participant_assignment_links pal
       ON pal.student_email = pc.student_email
     LEFT JOIN assignment_items ai
       ON ai.id = pal.assignment_item_id
     WHERE pc.student_email LIKE :email_like
     ORDER BY pc.student_email ASC
     LIMIT 12'
);
$statement->execute([
    'email_like' => $like,
]);

$rows = $statement->fetchAll();
$students = [];
foreach ($rows as $row) {
    $students[] = [
        'email' => $row['student_email'],
        'hasAssignment' => $row['assignment_item_id'] !== null,
        'assignment' => $row['assignment_item_id'] !== null
            ? [
                'pool' => $row['pool'],
                'pin' => $row['pin'],
            ]
            : null,
    ];
}

json_response(200, ['students' => $students]);
