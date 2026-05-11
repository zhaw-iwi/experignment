<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');

$query = normalize_student_email((string) ($_GET['q'] ?? ''));
if ($query === '' || strlen($query) < 2) {
    json_response(200, ['students' => []]);
}

$pdo = db();
$statement = $pdo->prepare(
    'SELECT a.student_email,
            COUNT(p.id) AS participation_count,
            SUM(CASE WHEN p.confirmed_at IS NOT NULL THEN 1 ELSE 0 END) AS confirmed_count
     FROM allowed_students a
     LEFT JOIN participations p ON p.student_email = a.student_email
     WHERE a.student_email LIKE :email_like
     GROUP BY a.student_email
     ORDER BY a.student_email ASC
     LIMIT 20'
);
$statement->execute([
    'email_like' => $query . '%',
]);

$students = [];
foreach ($statement->fetchAll() as $row) {
    $students[] = [
        'email' => $row['student_email'],
        'participationCount' => (int) $row['participation_count'],
        'confirmedCount' => (int) ($row['confirmed_count'] ?? 0),
    ];
}

json_response(200, ['students' => $students]);
