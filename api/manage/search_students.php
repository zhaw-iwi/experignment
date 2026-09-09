<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');
require_admin_authentication();

$query = normalize_student_email((string) ($_GET['q'] ?? ''));
$groupId = nullable_int($_GET['groupId'] ?? null);
if ($query === '' || strlen($query) < 2) {
    json_response(200, ['students' => []]);
}

$pdo = db();
$sql = 'SELECT a.student_email, g.id AS group_id, g.name AS group_name,
            COUNT(p.id) AS participation_count,
            SUM(CASE WHEN p.confirmed_at IS NOT NULL THEN 1 ELSE 0 END) AS confirmed_count
     FROM allowed_students a
     INNER JOIN student_groups g ON g.id = a.group_id
     LEFT JOIN participations p ON p.student_email = a.student_email
     WHERE a.student_email LIKE :email_like';
$params = ['email_like' => $query . '%'];
if ($groupId !== null) {
    $sql .= ' AND a.group_id = :group_id';
    $params['group_id'] = $groupId;
}
$sql .= ' GROUP BY a.student_email, g.id, g.name
          ORDER BY a.student_email ASC
          LIMIT 20';
$statement = $pdo->prepare($sql);
$statement->execute($params);

$students = [];
foreach ($statement->fetchAll() as $row) {
    $students[] = [
        'email' => $row['student_email'],
        'group' => ['id' => (int) $row['group_id'], 'name' => $row['group_name']],
        'participationCount' => (int) $row['participation_count'],
        'confirmedCount' => (int) ($row['confirmed_count'] ?? 0),
    ];
}

json_response(200, ['students' => $students]);
