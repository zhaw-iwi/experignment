<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');
require_admin_authentication();

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
    [
        'key' => 'group',
        'label' => 'Kurs',
        'type' => 'group',
    ],
    [
        'key' => 'totalCredits',
        'label' => 'Punkte',
        'type' => 'credits',
    ],
    [
        'key' => 'courseMaximum',
        'label' => 'Maximum',
        'type' => 'credits',
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
    'SELECT a.student_email, g.id AS group_id, g.name AS group_name, g.max_credits,
            COALESCE(SUM(CASE WHEN p.confirmed_at IS NOT NULL THEN e.reward_credits ELSE 0 END), 0) AS total_credits
     FROM allowed_students a
     INNER JOIN student_groups g ON g.id = a.group_id
     LEFT JOIN participations p ON p.student_email = a.student_email
     LEFT JOIN experiments e ON e.id = p.experiment_id
     GROUP BY a.student_email, g.id, g.name, g.max_credits
     ORDER BY g.name ASC, a.student_email ASC'
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
        'groupId' => (int) $studentRow['group_id'],
        'groupName' => $studentRow['group_name'],
        'totalCredits' => round((float) $studentRow['total_credits'], 2),
        'courseMaximum' => $studentRow['max_credits'] === null ? null : (float) $studentRow['max_credits'],
        'values' => $values,
    ];
}

json_response(200, [
    'generatedAt' => gmdate('c'),
    'columns' => $columns,
    'groups' => array_values(array_map(
        static fn (array $group): array => ['id' => (int) $group['id'], 'name' => $group['name']],
        $pdo->query('SELECT id, name FROM student_groups ORDER BY name ASC, id ASC')->fetchAll()
    )),
    'rows' => $rows,
]);
