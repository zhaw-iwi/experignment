<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/_bootstrap.php';

$generatedAccessCode = generate_student_access_code();

$checks = [
    ['label' => 'valid student email', 'actual' => is_valid_student_email('user@students.zhaw.ch'), 'expected' => true],
    ['label' => 'reject non-student email', 'actual' => is_valid_student_email('user@example.com'), 'expected' => false],
    ['label' => 'student code from email', 'actual' => student_code_from_email('USER@students.zhaw.ch'), 'expected' => 'user'],
    ['label' => 'student code from raw value', 'actual' => student_code_from_email(' user '), 'expected' => 'user'],
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
    ['label' => 'access code accepts five mixed alphanumeric characters', 'actual' => access_code_meets_requirements('ab12c'), 'expected' => true],
    ['label' => 'access code accepts uppercase characters', 'actual' => access_code_meets_requirements('Abc123'), 'expected' => true],
    ['label' => 'access code rejects letters only', 'actual' => access_code_meets_requirements('abcde'), 'expected' => false],
    ['label' => 'access code rejects digits only', 'actual' => access_code_meets_requirements('12345'), 'expected' => false],
    ['label' => 'access code rejects short values', 'actual' => access_code_meets_requirements('a123'), 'expected' => false],
    ['label' => 'access code rejects punctuation', 'actual' => access_code_meets_requirements('ab12!'), 'expected' => false],
    ['label' => 'generated access code has required complexity', 'actual' => access_code_meets_requirements($generatedAccessCode), 'expected' => true],
    ['label' => 'generated access code is five lowercase characters', 'actual' => preg_match('/^[a-z0-9]{5}$/', $generatedAccessCode) === 1, 'expected' => true],
    ['label' => 'manual close disables experiment availability', 'actual' => experiment_is_available_now(['is_open' => 0]), 'expected' => false],
    [
        'label' => 'active scheduled experiment is available',
        'actual' => experiment_is_available_now([
            'is_open' => 1,
            'opens_at' => '2020-01-01 00:00:00',
            'closes_at' => '2099-12-31 23:59:59',
        ]),
        'expected' => true,
    ],
    [
        'label' => 'future experiment is unavailable',
        'actual' => experiment_is_available_now(['is_open' => 1, 'opens_at' => '2099-12-31 23:59:59']),
        'expected' => false,
    ],
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
