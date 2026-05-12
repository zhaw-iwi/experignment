-- Drop all application tables in dependency order.
-- Run only when you want to remove the full schema.

DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS slot_choices;
DROP TABLE IF EXISTS participation_field_values;
DROP TABLE IF EXISTS eligibility_field_values;
DROP TABLE IF EXISTS randomization_run_allocations;
DROP TABLE IF EXISTS participations;
DROP TABLE IF EXISTS access_pool_values;
DROP TABLE IF EXISTS access_pool_rows;
DROP TABLE IF EXISTS time_slots;
DROP TABLE IF EXISTS access_fields;
DROP TABLE IF EXISTS experiment_eligibilities;
DROP TABLE IF EXISTS randomization_runs;
DROP TABLE IF EXISTS experiment_conditions;
DROP TABLE IF EXISTS experiments;
DROP TABLE IF EXISTS allowed_students;
DROP TABLE IF EXISTS schema_versions;
