<?php

declare(strict_types=1);

function assert_schema_condition(bool $condition, string $label): void
{
    if (!$condition) {
        fwrite(STDERR, 'FAILED: ' . $label . PHP_EOL);
        exit(1);
    }
}

$schema = file_get_contents(__DIR__ . '/../database/schema.sql');
$drop = file_get_contents(__DIR__ . '/../database/drop_tables.sql');
$resetAll = file_get_contents(__DIR__ . '/../database/reset_all_data.sql');
$seed = file_get_contents(__DIR__ . '/../database/seed.sql');

assert_schema_condition(is_string($schema), 'read schema.sql');
assert_schema_condition(is_string($drop), 'read drop_tables.sql');
assert_schema_condition(is_string($resetAll), 'read reset_all_data.sql');
assert_schema_condition(is_string($seed), 'read seed.sql');

$requiredSchemaFragments = [
    "VALUES (3, 'Semester preparation schema foundation')",
    'CREATE TABLE student_groups',
    'max_credits DECIMAL(8,2) NULL',
    'group_id BIGINT UNSIGNED NOT NULL',
    'login_code_hash VARCHAR(255) NULL',
    'login_code_version INT UNSIGNED NOT NULL DEFAULT 0',
    'CREATE TABLE authentication_throttles',
    'admin_notes TEXT NULL',
    'opens_at DATETIME NULL',
    'closes_at DATETIME NULL',
    'max_participants INT UNSIGNED NULL',
    'reward_credits DECIMAL(8,2) NOT NULL DEFAULT 1.00',
    'CREATE TABLE experiment_group_eligibilities',
    'reward_credits_snapshot DECIMAL(8,2) NULL',
    'is_undated TINYINT(1) NOT NULL DEFAULT 0',
    'CREATE TABLE audit_events',
];

foreach ($requiredSchemaFragments as $fragment) {
    assert_schema_condition(str_contains($schema, $fragment), 'schema contains ' . $fragment);
}

foreach (['audit_events', 'authentication_throttles', 'experiment_group_eligibilities', 'student_groups'] as $table) {
    assert_schema_condition(
        str_contains($drop, 'DROP TABLE IF EXISTS ' . $table . ';'),
        'drop script contains ' . $table
    );
    assert_schema_condition(
        str_contains($resetAll, 'TRUNCATE TABLE ' . $table . ';'),
        'full reset contains ' . $table
    );
}

assert_schema_condition(!str_contains($seed, '@students.zhaw.ch'), 'production seed contains no student addresses');

fwrite(STDOUT, 'schema_test.php: ok' . PHP_EOL);
