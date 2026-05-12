-- Reset all experiment setup and runtime data while preserving the global allowlist.
-- Keeps allowed_students and schema_versions.

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE appointments;
TRUNCATE TABLE slot_choices;
TRUNCATE TABLE participation_field_values;
TRUNCATE TABLE participations;
TRUNCATE TABLE eligibility_field_values;
TRUNCATE TABLE randomization_run_allocations;
TRUNCATE TABLE randomization_runs;
TRUNCATE TABLE access_pool_values;
TRUNCATE TABLE access_pool_rows;
TRUNCATE TABLE time_slots;
TRUNCATE TABLE access_fields;
TRUNCATE TABLE experiment_eligibilities;
TRUNCATE TABLE experiment_conditions;
TRUNCATE TABLE experiments;

SET FOREIGN_KEY_CHECKS = 1;
