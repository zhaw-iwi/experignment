<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

require_method('GET');
require_admin_authentication();

$pdo = db();

$allowedCount = (int) ($pdo->query('SELECT COUNT(*) AS row_count FROM allowed_students')->fetch()['row_count'] ?? 0);
$studentGroupRows = $pdo->query(
    'SELECT g.id, g.name, g.max_credits, g.created_at, g.updated_at,
            COUNT(DISTINCT a.id) AS student_count
     FROM student_groups g
     LEFT JOIN allowed_students a ON a.group_id = g.id
     GROUP BY g.id, g.name, g.max_credits, g.created_at, g.updated_at
     ORDER BY g.name ASC, g.id ASC'
)->fetchAll();
$studentGroups = [];
foreach ($studentGroupRows as $row) {
    $studentGroups[] = [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'maxCredits' => $row['max_credits'] === null ? null : (float) $row['max_credits'],
        'studentCount' => (int) $row['student_count'],
        'createdAt' => $row['created_at'],
        'updatedAt' => $row['updated_at'],
    ];
}

$allowedStudentRows = $pdo->query(
    'SELECT a.student_email, a.created_at, a.login_code_hash, a.login_code_set_at,
            g.id AS group_id, g.name AS group_name, g.max_credits AS group_max_credits,
            COUNT(DISTINCT p.id) AS participation_count,
            COUNT(DISTINCT CASE WHEN p.confirmed_at IS NOT NULL THEN p.id END) AS confirmed_count,
            COUNT(DISTINCT ee.id) AS eligibility_count
     FROM allowed_students a
     INNER JOIN student_groups g ON g.id = a.group_id
     LEFT JOIN participations p ON p.student_email = a.student_email
     LEFT JOIN experiment_eligibilities ee ON ee.student_email = a.student_email
     GROUP BY a.student_email, a.created_at, a.login_code_hash, a.login_code_set_at,
              g.id, g.name, g.max_credits
     ORDER BY g.name ASC, a.student_email ASC
     LIMIT 2000'
)->fetchAll();

$allowedStudents = [];
foreach ($allowedStudentRows as $row) {
    $allowedStudents[] = [
        'email' => $row['student_email'],
        'group' => [
            'id' => (int) $row['group_id'],
            'name' => $row['group_name'],
            'maxCredits' => $row['group_max_credits'] === null ? null : (float) $row['group_max_credits'],
        ],
        'loginCodeSet' => is_string($row['login_code_hash'] ?? null) && $row['login_code_hash'] !== '',
        'loginCodeSetAt' => $row['login_code_set_at'],
        'createdAt' => $row['created_at'],
        'participationCount' => (int) $row['participation_count'],
        'confirmedCount' => (int) ($row['confirmed_count'] ?? 0),
        'eligibilityCount' => (int) $row['eligibility_count'],
    ];
}

$experiments = [];
$experimentRows = $pdo->query(
    'SELECT *
     FROM experiments
     ORDER BY sort_order ASC, id ASC'
)->fetchAll();

$conditionStatement = $pdo->prepare(
    'SELECT *
     FROM experiment_conditions
     WHERE experiment_id = :experiment_id
     ORDER BY sort_order ASC, id ASC'
);
$fieldStatement = $pdo->prepare(
    'SELECT *
     FROM access_fields
     WHERE experiment_id = :experiment_id
     ORDER BY condition_id ASC, sort_order ASC, id ASC'
);
$poolStatement = $pdo->prepare(
    'SELECT condition_id,
            COUNT(*) AS total_count,
            SUM(CASE WHEN is_assigned = 1 THEN 1 ELSE 0 END) AS assigned_count
     FROM access_pool_rows
     WHERE experiment_id = :experiment_id
     GROUP BY condition_id'
);
$eligibilityStatement = $pdo->prepare(
    'SELECT COUNT(*) AS row_count
     FROM experiment_eligibilities
     WHERE experiment_id = :experiment_id'
);
$participationStatement = $pdo->prepare(
    'SELECT COUNT(*) AS row_count,
            SUM(CASE WHEN confirmed_at IS NOT NULL THEN 1 ELSE 0 END) AS confirmed_count
     FROM participations
     WHERE experiment_id = :experiment_id'
);
$randomizationStatement = $pdo->prepare(
    'SELECT id, seed, total_students, created_at
     FROM randomization_runs
     WHERE experiment_id = :experiment_id
     ORDER BY created_at DESC, id DESC
     LIMIT 5'
);
$randomizationAllocationStatement = $pdo->prepare(
    'SELECT rra.condition_id, ec.public_name AS condition_name, rra.percentage, rra.assigned_count
     FROM randomization_run_allocations rra
     INNER JOIN experiment_conditions ec ON ec.id = rra.condition_id
     WHERE rra.run_id = :run_id
     ORDER BY ec.sort_order ASC, ec.id ASC'
);
$eligibilityListStatement = $pdo->prepare(
    'SELECT ee.id, ee.student_email, ee.condition_id, ee.source, ee.created_at,
            ec.public_name AS condition_name,
            p.id AS participation_id
     FROM experiment_eligibilities ee
     LEFT JOIN experiment_conditions ec ON ec.id = ee.condition_id
     LEFT JOIN participations p
       ON p.experiment_id = ee.experiment_id
      AND p.student_email = ee.student_email
     WHERE ee.experiment_id = :experiment_id
     ORDER BY ee.student_email ASC
     LIMIT 1000'
);
$eligibilityFieldValueStatement = $pdo->prepare(
    'SELECT field_id, field_value
     FROM eligibility_field_values
     WHERE eligibility_id = :eligibility_id
     ORDER BY field_id ASC'
);
$poolRowsStatement = $pdo->prepare(
    'SELECT apr.id, apr.condition_id, apr.is_assigned, apr.assigned_participation_id, apr.assigned_at,
            ec.public_name AS condition_name,
            p.student_email AS assigned_student_email
     FROM access_pool_rows apr
     LEFT JOIN experiment_conditions ec ON ec.id = apr.condition_id
     LEFT JOIN participations p ON p.id = apr.assigned_participation_id
     WHERE apr.experiment_id = :experiment_id
     ORDER BY apr.condition_id ASC, apr.id ASC
     LIMIT 1000'
);
$poolValuesStatement = $pdo->prepare(
    'SELECT af.id AS field_id, af.field_key, af.label, af.value_type, apv.field_value
     FROM access_pool_values apv
     INNER JOIN access_fields af ON af.id = apv.field_id
     WHERE apv.pool_row_id = :pool_row_id
     ORDER BY af.sort_order ASC, af.id ASC'
);
$slotChoicesStatement = $pdo->prepare(
    'SELECT ts.id AS slot_id, ts.label AS slot_label,
            p.id AS participation_id, p.student_email, p.confirmed_at,
            ec.public_name AS condition_name,
            a.appointment_text
     FROM time_slots ts
     INNER JOIN slot_choices sc ON sc.time_slot_id = ts.id
     INNER JOIN participations p ON p.id = sc.participation_id
     LEFT JOIN experiment_conditions ec ON ec.id = p.condition_id
     LEFT JOIN appointments a ON a.participation_id = p.id
     WHERE ts.experiment_id = :experiment_id
     ORDER BY ts.sort_order ASC, ts.id ASC, p.student_email ASC'
);
$participationFieldValueStatement = $pdo->prepare(
    'SELECT field_id, field_value
     FROM participation_field_values
     WHERE participation_id = :participation_id
     ORDER BY field_id ASC'
);
$experimentGroupStatement = $pdo->prepare(
    'SELECT eg.group_id, g.name
     FROM experiment_group_eligibilities eg
     INNER JOIN student_groups g ON g.id = eg.group_id
     WHERE eg.experiment_id = :experiment_id
     ORDER BY g.name ASC, g.id ASC'
);

foreach ($experimentRows as $experiment) {
    $experimentId = (int) $experiment['id'];
    $experimentGroupStatement->execute(['experiment_id' => $experimentId]);
    $experimentGroups = array_map(
        static fn (array $group): array => ['id' => (int) $group['group_id'], 'name' => $group['name']],
        $experimentGroupStatement->fetchAll()
    );

    $conditionStatement->execute(['experiment_id' => $experimentId]);
    $conditions = [];
    foreach ($conditionStatement->fetchAll() as $condition) {
        $conditions[] = [
            'id' => (int) $condition['id'],
            'name' => $condition['public_name'],
            'sortOrder' => (int) $condition['sort_order'],
        ];
    }

    $fieldStatement->execute(['experiment_id' => $experimentId]);
    $fields = [];
    foreach ($fieldStatement->fetchAll() as $field) {
        $fields[] = [
            'id' => (int) $field['id'],
            'conditionId' => nullable_int($field['condition_id'] ?? null),
            'key' => $field['field_key'],
            'label' => $field['label'],
            'valueType' => $field['value_type'],
            'valueSource' => $field['value_source'],
            'sharedValue' => $field['shared_value'],
            'isVisible' => bool_value($field['is_visible']),
            'sortOrder' => (int) $field['sort_order'],
        ];
    }

    $poolStatement->execute(['experiment_id' => $experimentId]);
    $poolCounts = [];
    foreach ($poolStatement->fetchAll() as $row) {
        $total = (int) $row['total_count'];
        $assigned = (int) ($row['assigned_count'] ?? 0);
        $poolCounts[] = [
            'conditionId' => nullable_int($row['condition_id'] ?? null),
            'total' => $total,
            'assigned' => $assigned,
            'available' => max(0, $total - $assigned),
        ];
    }

    $eligibilityStatement->execute(['experiment_id' => $experimentId]);
    $eligibilityCount = (int) ($eligibilityStatement->fetch()['row_count'] ?? 0);

    $participationStatement->execute(['experiment_id' => $experimentId]);
    $participationCountRow = $participationStatement->fetch();

    $randomizationStatement->execute(['experiment_id' => $experimentId]);
    $randomizationRuns = [];
    foreach ($randomizationStatement->fetchAll() as $run) {
        $randomizationAllocationStatement->execute(['run_id' => (int) $run['id']]);
        $allocations = [];
        foreach ($randomizationAllocationStatement->fetchAll() as $allocation) {
            $allocations[] = [
                'conditionId' => (int) $allocation['condition_id'],
                'conditionName' => $allocation['condition_name'],
                'percentage' => (float) $allocation['percentage'],
                'assignedCount' => (int) $allocation['assigned_count'],
            ];
        }

        $randomizationRuns[] = [
            'id' => (int) $run['id'],
            'seed' => $run['seed'],
            'totalStudents' => (int) $run['total_students'],
            'createdAt' => $run['created_at'],
            'allocations' => $allocations,
        ];
    }

    $eligibilityListStatement->execute(['experiment_id' => $experimentId]);
    $eligibilities = [];
    foreach ($eligibilityListStatement->fetchAll() as $eligibility) {
        $eligibilityFieldValueStatement->execute(['eligibility_id' => (int) $eligibility['id']]);
        $fieldValues = [];
        foreach ($eligibilityFieldValueStatement->fetchAll() as $fieldValue) {
            $fieldValues[] = [
                'fieldId' => (int) $fieldValue['field_id'],
                'value' => $fieldValue['field_value'],
            ];
        }

        $eligibilities[] = [
            'id' => (int) $eligibility['id'],
            'email' => $eligibility['student_email'],
            'conditionId' => nullable_int($eligibility['condition_id'] ?? null),
            'conditionName' => $eligibility['condition_name'],
            'source' => $eligibility['source'],
            'createdAt' => $eligibility['created_at'],
            'hasParticipation' => nullable_int($eligibility['participation_id'] ?? null) !== null,
            'fieldValues' => $fieldValues,
        ];
    }

    $poolRowsStatement->execute(['experiment_id' => $experimentId]);
    $accessPoolRows = [];
    foreach ($poolRowsStatement->fetchAll() as $poolRow) {
        $poolValuesStatement->execute(['pool_row_id' => (int) $poolRow['id']]);
        $values = [];
        foreach ($poolValuesStatement->fetchAll() as $value) {
            $values[] = [
                'fieldId' => (int) $value['field_id'],
                'key' => $value['field_key'],
                'label' => $value['label'],
                'valueType' => $value['value_type'],
                'value' => $value['field_value'],
            ];
        }

        $accessPoolRows[] = [
            'id' => (int) $poolRow['id'],
            'conditionId' => nullable_int($poolRow['condition_id'] ?? null),
            'conditionName' => $poolRow['condition_name'],
            'isAssigned' => bool_value($poolRow['is_assigned']),
            'assignedParticipationId' => nullable_int($poolRow['assigned_participation_id'] ?? null),
            'assignedStudentEmail' => $poolRow['assigned_student_email'],
            'assignedAt' => $poolRow['assigned_at'],
            'values' => $values,
        ];
    }

    $slotChoicesStatement->execute(['experiment_id' => $experimentId]);
    $slotChoices = [];
    foreach ($slotChoicesStatement->fetchAll() as $choice) {
        $slotChoices[] = [
            'slotId' => (int) $choice['slot_id'],
            'slotLabel' => $choice['slot_label'],
            'participationId' => (int) $choice['participation_id'],
            'email' => $choice['student_email'],
            'conditionName' => $choice['condition_name'],
            'confirmed' => ($choice['confirmed_at'] ?? null) !== null,
            'appointmentText' => $choice['appointment_text'],
        ];
    }

    $experiments[] = [
        'id' => $experimentId,
        'name' => $experiment['public_name'],
        'description' => $experiment['description'],
        'adminNotes' => $experiment['admin_notes'] ?? null,
        'isOpen' => bool_value($experiment['is_open']),
        'isAvailableNow' => experiment_is_available_now($experiment),
        'opensAt' => $experiment['opens_at'] ?? null,
        'closesAt' => $experiment['closes_at'] ?? null,
        'maxParticipants' => nullable_int($experiment['max_participants'] ?? null),
        'rewardCredits' => round((float) ($experiment['reward_credits'] ?? 0), 2),
        'audienceMode' => $experimentGroups === [] ? 'all_groups' : 'selected_groups',
        'groups' => $experimentGroups,
        'groupIds' => array_map(static fn (array $group): int => $group['id'], $experimentGroups),
        'eligibilityMode' => $experiment['eligibility_mode'],
        'conditionMode' => $experiment['condition_mode'],
        'requiresTimeSlot' => bool_value($experiment['requires_time_slot']),
        'sortOrder' => (int) $experiment['sort_order'],
        'conditions' => $conditions,
        'accessFields' => $fields,
        'poolCounts' => $poolCounts,
        'accessPoolRows' => $accessPoolRows,
        'eligibilities' => $eligibilities,
        'timeSlots' => fetch_time_slots($pdo, $experimentId),
        'slotChoices' => $slotChoices,
        'counts' => [
            'eligibilities' => $eligibilityCount,
            'participations' => (int) ($participationCountRow['row_count'] ?? 0),
            'confirmed' => (int) ($participationCountRow['confirmed_count'] ?? 0),
        ],
        'readiness' => experiment_readiness($pdo, $experiment),
        'randomizationRuns' => $randomizationRuns,
    ];
}

$participationRows = $pdo->query(
    'SELECT p.id, p.student_email, p.access_pool_row_id, p.assigned_at, p.confirmed_at, p.reward_credits_snapshot,
            e.id AS experiment_id, e.public_name AS experiment_name, e.reward_credits,
            ec.id AS condition_id, ec.public_name AS condition_name,
            ts.label AS slot_label,
            a.appointment_text
     FROM participations p
     INNER JOIN experiments e ON e.id = p.experiment_id
     LEFT JOIN experiment_conditions ec ON ec.id = p.condition_id
     LEFT JOIN slot_choices sc ON sc.participation_id = p.id
     LEFT JOIN time_slots ts ON ts.id = sc.time_slot_id
     LEFT JOIN appointments a ON a.participation_id = p.id
     ORDER BY e.sort_order ASC, p.assigned_at DESC, p.id DESC
     LIMIT 500'
)->fetchAll();

$participations = [];
foreach ($participationRows as $row) {
    $participationFieldValueStatement->execute(['participation_id' => (int) $row['id']]);
    $fieldValues = [];
    foreach ($participationFieldValueStatement->fetchAll() as $fieldValue) {
        $fieldValues[] = [
            'fieldId' => (int) $fieldValue['field_id'],
            'value' => $fieldValue['field_value'],
        ];
    }

    $participations[] = [
        'id' => (int) $row['id'],
        'email' => $row['student_email'],
        'experimentId' => (int) $row['experiment_id'],
        'experimentName' => $row['experiment_name'],
        'conditionId' => nullable_int($row['condition_id'] ?? null),
        'conditionName' => $row['condition_name'],
        'assignedAt' => $row['assigned_at'],
        'confirmed' => ($row['confirmed_at'] ?? null) !== null,
        'confirmedAt' => $row['confirmed_at'],
        'rewardCredits' => round((float) ($row['reward_credits'] ?? 0), 2),
        'creditedReward' => ($row['confirmed_at'] ?? null) === null
            ? null
            : round((float) ($row['reward_credits_snapshot'] ?? 0), 2),
        'slotLabel' => $row['slot_label'],
        'appointmentText' => $row['appointment_text'],
        'fieldValues' => $fieldValues,
        'accessItems' => access_payload($pdo, $row),
    ];
}

$auditEvents = [];
$auditRows = $pdo->query(
    'SELECT id, actor_type, actor_identifier, action, entity_type, entity_identifier, details_json, ip_address, created_at
     FROM audit_events
     ORDER BY created_at DESC, id DESC
     LIMIT 100'
)->fetchAll();
foreach ($auditRows as $auditRow) {
    $details = null;
    if (is_string($auditRow['details_json'] ?? null) && $auditRow['details_json'] !== '') {
        $decoded = json_decode($auditRow['details_json'], true);
        $details = is_array($decoded) ? $decoded : null;
    }
    $auditEvents[] = [
        'id' => (int) $auditRow['id'],
        'actorType' => $auditRow['actor_type'],
        'actorIdentifier' => $auditRow['actor_identifier'],
        'action' => $auditRow['action'],
        'entityType' => $auditRow['entity_type'],
        'entityIdentifier' => $auditRow['entity_identifier'],
        'details' => $details,
        'ipAddress' => $auditRow['ip_address'],
        'createdAt' => $auditRow['created_at'],
    ];
}

json_response(200, [
    'allowedStudentCount' => $allowedCount,
    'studentGroups' => $studentGroups,
    'allowedStudents' => $allowedStudents,
    'experiments' => $experiments,
    'participations' => $participations,
    'auditEvents' => $auditEvents,
]);
