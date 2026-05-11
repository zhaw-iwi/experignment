-- Reset script for V2 test cycles.
-- Keeps configured experiments, fields, pools, slots, and allowed students.

START TRANSACTION;

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE appointments;
TRUNCATE TABLE slot_choices;
TRUNCATE TABLE participation_field_values;
TRUNCATE TABLE participations;
TRUNCATE TABLE randomization_run_allocations;
TRUNCATE TABLE randomization_runs;

UPDATE access_pool_rows
SET is_assigned = 0,
    assigned_participation_id = NULL,
    assigned_at = NULL;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;
