-- Reset all semester configuration and runtime data while preserving the schema.
-- This also removes student groups, students, login credentials, and audit events.

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE audit_events;
TRUNCATE TABLE authentication_throttles;
TRUNCATE TABLE appointments;
TRUNCATE TABLE slot_choices;
TRUNCATE TABLE participation_field_values;
TRUNCATE TABLE student_chest_events;
TRUNCATE TABLE participations;
TRUNCATE TABLE eligibility_field_values;
TRUNCATE TABLE randomization_run_allocations;
TRUNCATE TABLE randomization_runs;
TRUNCATE TABLE access_pool_values;
TRUNCATE TABLE access_pool_rows;
TRUNCATE TABLE time_slots;
TRUNCATE TABLE access_fields;
TRUNCATE TABLE experiment_eligibilities;
TRUNCATE TABLE experiment_group_eligibilities;
TRUNCATE TABLE experiment_conditions;
TRUNCATE TABLE experiments;
TRUNCATE TABLE allowed_students;
TRUNCATE TABLE student_groups;

SET FOREIGN_KEY_CHECKS = 1;
