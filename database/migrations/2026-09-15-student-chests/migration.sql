-- Experiment Assignment App: schema V3 -> V4
-- Adds durable student participation chest events.
--
-- Before running in phpMyAdmin:
--   1. Take and verify a complete database backup.
--   2. Require the first query below to return schema_version = 3.
--   3. Require the second query below to return chest_table_count = 0.
--
-- SELECT MAX(version_number) AS schema_version FROM schema_versions;
-- SELECT COUNT(*) AS chest_table_count
-- FROM information_schema.TABLES
-- WHERE TABLE_SCHEMA = DATABASE()
--   AND TABLE_NAME = 'student_chest_events';
--
-- MySQL DDL auto-commits. This script intentionally uses a plain CREATE TABLE
-- and plain schema-version INSERT: rerunning it fails clearly instead of
-- silently accepting a partial or incompatible table. If execution fails,
-- inspect the error and restore the verified backup before retrying.

CREATE TABLE student_chest_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_email VARCHAR(255) NOT NULL,
    source_participation_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(64) NOT NULL DEFAULT 'participation_credited',
    trigger_scope VARCHAR(191) NOT NULL,
    variant VARCHAR(32) NOT NULL DEFAULT 'gold',
    experiment_name_snapshot VARCHAR(255) NOT NULL,
    earned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    opened_at TIMESTAMP NULL DEFAULT NULL,
    revoked_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_student_chest_events_trigger (event_type, trigger_scope),
    KEY idx_student_chest_events_pending (student_email, opened_at, revoked_at, earned_at, id),
    KEY idx_student_chest_events_participation (source_participation_id),
    CONSTRAINT fk_student_chest_events_student
        FOREIGN KEY (student_email) REFERENCES allowed_students (student_email)
        ON DELETE CASCADE
        ON UPDATE RESTRICT,
    CONSTRAINT fk_student_chest_events_participation
        FOREIGN KEY (source_participation_id) REFERENCES participations (id)
        ON DELETE SET NULL
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- This migration intentionally contains no historical INSERT ... SELECT.
-- Confirmed V3 participations receive no chest rows.

INSERT INTO schema_versions (version_number, description)
VALUES (4, 'Student participation chest events');

-- Post-migration verification (run in phpMyAdmin after successful import):
-- SELECT MAX(version_number) AS schema_version FROM schema_versions;
-- SHOW CREATE TABLE student_chest_events;
-- SELECT COUNT(*) AS chest_event_count FROM student_chest_events;
-- Expected values: schema_version = 4 and chest_event_count = 0.
--
-- Reliable rollback: restore the pre-migration database backup. Dropping the
-- table after V4 has served traffic would permanently lose chest history.
