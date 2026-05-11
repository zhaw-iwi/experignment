<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/_bootstrap.php';

$checks = [
    ['label' => 'valid student email', 'actual' => is_valid_student_email('user@students.zhaw.ch'), 'expected' => true],
    ['label' => 'reject non-student email', 'actual' => is_valid_student_email('user@example.com'), 'expected' => false],
    ['label' => 'valid eligibility mode', 'actual' => is_valid_eligibility_mode('all_allowed'), 'expected' => true],
    ['label' => 'reject eligibility mode', 'actual' => is_valid_eligibility_mode('public'), 'expected' => false],
    ['label' => 'valid condition mode', 'actual' => is_valid_condition_mode('student_choice'), 'expected' => true],
    ['label' => 'reject condition mode', 'actual' => is_valid_condition_mode('free_text'), 'expected' => false],
    ['label' => 'valid value source', 'actual' => is_valid_value_source('pool'), 'expected' => true],
    ['label' => 'reject value source', 'actual' => is_valid_value_source('cookie'), 'expected' => false],
    ['label' => 'valid value type', 'actual' => is_valid_value_type('appointment'), 'expected' => true],
    ['label' => 'reject value type', 'actual' => is_valid_value_type('file'), 'expected' => false],
    ['label' => 'field key from label', 'actual' => field_key_from_label('Participant ID'), 'expected' => 'participant_id'],
    ['label' => 'nullable int accepts empty', 'actual' => nullable_int(''), 'expected' => null],
    ['label' => 'nullable int accepts number', 'actual' => nullable_int('42'), 'expected' => 42],
    ['label' => 'condition payload keeps null', 'actual' => condition_payload(null), 'expected' => null],
    [
        'label' => 'condition payload maps row',
        'actual' => condition_payload([
            'id' => '7',
            'public_name' => 'Text',
        ]),
        'expected' => [
            'id' => 7,
            'name' => 'Text',
        ],
    ],
];

foreach ($checks as $check) {
    if ($check['actual'] !== $check['expected']) {
        fwrite(STDERR, 'FAILED: ' . $check['label'] . PHP_EOL);
        exit(1);
    }
}

fwrite(STDOUT, 'validation_test.php: ok' . PHP_EOL);
