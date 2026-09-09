<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/env.php';

function assert_config_value(mixed $actual, mixed $expected, string $label): void
{
    if ($actual !== $expected) {
        fwrite(STDERR, 'FAILED: ' . $label . PHP_EOL);
        exit(1);
    }
}

$path = tempnam(sys_get_temp_dir(), 'experiment_env_');
if ($path === false) {
    fwrite(STDERR, 'FAILED: could not create temporary environment file' . PHP_EOL);
    exit(1);
}

$names = [
    'EXPERIMENT_TEST_SIMPLE',
    'EXPERIMENT_TEST_DOUBLE_QUOTED',
    'EXPERIMENT_TEST_SINGLE_QUOTED',
    'EXPERIMENT_TEST_EMPTY',
    'EXPERIMENT_TEST_EXISTING',
    'EXPERIMENT_TEST_BOOL',
];

try {
    putenv('EXPERIMENT_TEST_EXISTING=process-value');
    file_put_contents($path, implode(PHP_EOL, [
        '# test environment file',
        'EXPERIMENT_TEST_SIMPLE=simple-value',
        'EXPERIMENT_TEST_DOUBLE_QUOTED="quoted value"',
        "EXPERIMENT_TEST_SINGLE_QUOTED='literal # value'",
        'EXPERIMENT_TEST_EMPTY=',
        'EXPERIMENT_TEST_EXISTING=file-value',
        'EXPERIMENT_TEST_BOOL=yes',
    ]));

    load_environment_file($path);

    assert_config_value(environment_value('EXPERIMENT_TEST_SIMPLE'), 'simple-value', 'unquoted value');
    assert_config_value(environment_value('EXPERIMENT_TEST_DOUBLE_QUOTED'), 'quoted value', 'double-quoted value');
    assert_config_value(environment_value('EXPERIMENT_TEST_SINGLE_QUOTED'), 'literal # value', 'single-quoted value');
    assert_config_value(environment_value('EXPERIMENT_TEST_EMPTY'), '', 'empty value');
    assert_config_value(environment_value('EXPERIMENT_TEST_EXISTING'), 'process-value', 'process environment precedence');
    assert_config_value(environment_bool('EXPERIMENT_TEST_BOOL'), true, 'boolean value');
} finally {
    @unlink($path);
    foreach ($names as $name) {
        putenv($name);
        unset($_ENV[$name], $_SERVER[$name]);
    }
}

fwrite(STDOUT, 'config_test.php: ok' . PHP_EOL);
