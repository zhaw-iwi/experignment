<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/_bootstrap.php';

$checks = [
    ['label' => 'valid student email', 'actual' => is_valid_student_email('user@students.zhaw.ch'), 'expected' => true],
    ['label' => 'reject non-student email', 'actual' => is_valid_student_email('user@example.com'), 'expected' => false],
    ['label' => 'valid text pool', 'actual' => is_valid_pool('text'), 'expected' => true],
    ['label' => 'reject invalid pool', 'actual' => is_valid_pool('other'), 'expected' => false],
    [
        'label' => 'assignment payload keeps null agent',
        'actual' => assignment_payload([
            'pool' => 'tablet',
            'pin' => '53',
            'survey_url' => 'https://example.test/survey',
            'agent_url' => null,
        ]),
        'expected' => [
            'pool' => 'tablet',
            'pin' => '53',
            'surveyUrl' => 'https://example.test/survey',
            'agentUrl' => null,
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
