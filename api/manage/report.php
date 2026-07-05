<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');

$pdo = db();

$experimentRows = $pdo->query(
    'SELECT id, public_name
     FROM experiments
     ORDER BY sort_order ASC, id ASC'
)->fetchAll();

$columns = [
    [
        'key' => 'studentCode',
        'label' => 'Kürzel',
        'type' => 'student_code',
    ],
];
$experimentKeys = [];
foreach ($experimentRows as $experiment) {
    $experimentId = (int) $experiment['id'];
    $key = 'experiment_' . $experimentId;
    $experimentKeys[$experimentId] = $key;
    $columns[] = [
        'key' => $key,
        'label' => $experiment['public_name'],
        'type' => 'approval',
        'experimentId' => $experimentId,
    ];
}

$confirmedByStudent = [];
$confirmedRows = $pdo->query(
    'SELECT p.student_email, p.experiment_id
     FROM participations p
     INNER JOIN allowed_students a ON a.student_email = p.student_email
     INNER JOIN experiments e ON e.id = p.experiment_id
     WHERE p.confirmed_at IS NOT NULL'
)->fetchAll();
foreach ($confirmedRows as $confirmedRow) {
    $email = normalize_student_email((string) $confirmedRow['student_email']);
    $experimentId = (int) $confirmedRow['experiment_id'];
    if (!isset($experimentKeys[$experimentId])) {
        continue;
    }
    if (!isset($confirmedByStudent[$email])) {
        $confirmedByStudent[$email] = [];
    }
    $confirmedByStudent[$email][$experimentId] = true;
}

$studentRows = $pdo->query(
    'SELECT student_email
     FROM allowed_students
     ORDER BY student_email ASC'
)->fetchAll();

$rows = [];
foreach ($studentRows as $studentRow) {
    $email = normalize_student_email((string) $studentRow['student_email']);
    $values = [];
    foreach ($experimentKeys as $experimentId => $key) {
        $values[$key] = isset($confirmedByStudent[$email][$experimentId]) ? 1 : 0;
    }

    $rows[] = [
        'studentCode' => student_code_from_email($email),
        'email' => $email,
        'values' => $values,
    ];
}

json_response(200, [
    'generatedAt' => gmdate('c'),
    'columns' => $columns,
    'rows' => $rows,
]);
