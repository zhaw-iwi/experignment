# Project Audit Trail

## Summary

Experiment Assignment App is a PHP/MySQL application for managing student experiment access, student-visible participation information, staff-controlled eligibility, slot choices, appointments, and grading confirmation.

## Milestones

- [x] 2026-05-11: V2 greenfield multi-experiment implementation
- [x] 2026-05-11: Expanded project context for future Codex sessions
- [x] 2026-05-11: Fixed seed eligibility foreign key references
- [x] 2026-05-11: Split real and example seed data
- [x] 2026-05-11: Management operations completeness pass
- [ ] Migration from historical live database dump
- [ ] Staff authentication

## 2026-05-11: V2 Greenfield Multi-Experiment Implementation

### Goal

Replace the one-experiment implementation with a V2 model that supports multiple visible experiments, optional conditions, explicit eligibility, deterministic randomization, flexible access fields, bundled personalized access pools, time-slot selection, appointments, resets, and confirmation for grading.

### What Changed

- Replaced `database/schema.sql` with the V2 schema.
- Replaced `database/seed.sql` with sample V2 data.
- Replaced `database/reset.sql` with a V2 runtime reset script.
- Removed hardcoded production database defaults from `config/config.php`.
- Rebuilt student APIs:
  - `api/student_overview.php`
  - `api/claim.php`
  - `api/choose_slot.php`
  - `api/bootstrap.php`
- Rebuilt management APIs:
  - `api/manage/dashboard.php`
  - `api/manage/actions.php`
  - compatibility endpoints for allowlist search and reset
- Rebuilt `index.html`, `assets/app.js`, and `assets/app.css` for dynamic student workflows.
- Rebuilt `manage/index.html`, `manage/manage.js`, and `manage/manage.css` for V2 setup and operations.
- Updated validation, text-quality, and API smoke tests for V2.
- Added `.agents/CONTEXT.md` as canonical domain context.

### How To Run

1. Create an empty MySQL database.
2. Import `database/schema.sql`.
3. Optionally import `database/seed.sql`.
4. Set database environment variables:
   - `EXPERIMENT_DB_HOST`
   - `EXPERIMENT_DB_PORT`
   - `EXPERIMENT_DB_NAME`
   - `EXPERIMENT_DB_USER`
   - `EXPERIMENT_DB_PASSWORD`
   - `EXPERIMENT_DB_CHARSET`
5. Serve the repository with PHP or deploy it to the web host.

### How To Test

- `php tests/validation_test.php`
- `php tests/text_quality_test.php`
- `php tests/api_smoke_test.php`

The API smoke test requires `pdo_sqlite`; otherwise it skips with a clear message.

Observed on 2026-05-11:

- PHP syntax checks passed for all PHP files.
- `validation_test.php` passed.
- `text_quality_test.php` passed.
- `api_smoke_test.php` skipped because this PHP installation has no PDO drivers.
- JavaScript syntax checking could not be run because Node.js is not installed in this environment.

### Known Issues And Decisions

- Historical live data migration is intentionally deferred.
- Staff authentication is intentionally deferred.
- Email-only student recovery is intentionally accepted for now.
- Management actions use a single action endpoint for the V2 staff UI.
- Access pools are bundled by row to keep related values coupled.

### Next Steps

- Run a browser pass against a MySQL-backed local database.
- Build and test a migration script from the historical dump when the V2 workflow has been accepted.
- Add staff authentication before broader deployment.

## 2026-05-11: Expanded Project Context For Future Codex Sessions

### Goal

Turn `.agents/CONTEXT.md` into the canonical project-specific briefing for future Codex work on this repository.

### What Changed

- Expanded `.agents/CONTEXT.md` with product purpose, current decisions, domain semantics, access information model, slot behavior, randomization behavior, UI responsibilities, important files, deployment notes, test notes, and deferred work.

### How To Run

No runtime change.

### How To Test

Documentation-only change. Manual verification: read `.agents/CONTEXT.md` and confirm it captures the project-specific app context separately from `.agents/CODEX.md`.

### Known Issues And Decisions

- `.agents/CONTEXT.md` is now the canonical project context file.
- `.agents/CODEX.md` remains project-independent working rules.

### Next Steps

- Keep `.agents/CONTEXT.md` updated whenever domain decisions or operational assumptions change.

## 2026-05-11: Fixed Seed Eligibility Foreign Key References

### Goal

Fix `database/seed.sql` so it can be imported after `database/schema.sql` without violating the `experiment_eligibilities.student_email` foreign key.

### What Changed

- Replaced leftover sample eligibility emails `alice@students.zhaw.ch` and `bob@students.zhaw.ch` with emails that are inserted into `allowed_students` by the same seed file.

### How To Run

Import into an empty V2 database:

1. `database/schema.sql`
2. `database/seed.sql`

### How To Test

- `php tests/validation_test.php`
- `php tests/text_quality_test.php`

### Known Issues And Decisions

- This only fixes the seed data. It does not migrate historical live data.

### Next Steps

- Re-run the seed import on the deployment database after resetting/recreating the empty V2 schema.

## 2026-05-11: Split Real And Example Seed Data

### Goal

Keep deployable seed data free of example experiments/access data while preserving a self-contained example seed for development and demos.

### What Changed

- Moved the example experiment/access dataset to `database/seed_examples.sql`.
- Restored the example students in `seed_examples.sql` to `alice@students.zhaw.ch`, `bob@students.zhaw.ch`, `charlie@students.zhaw.ch`, and `dana@students.zhaw.ch`.
- Restored the example eligibility foreign key references to `alice@students.zhaw.ch` and `bob@students.zhaw.ch`.
- Recreated `database/seed.sql` with only the real course `allowed_students` list.
- Updated `README.md` and `.agents/CONTEXT.md` to document the split.

### How To Run

Production-style setup:

1. Import `database/schema.sql`.
2. Import `database/seed.sql`.
3. Configure experiments and access data through the management UI.

Demo setup:

1. Import `database/schema.sql`.
2. Import `database/seed_examples.sql`.

### How To Test

- `php tests/validation_test.php`
- `php tests/text_quality_test.php`

### Known Issues And Decisions

- `seed_examples.sql` should not be imported into a production course database.
- `seed.sql` intentionally contains no experiments, conditions, fields, access pools, or slots.

### Next Steps

- Use the management UI to configure real experiments after importing `seed.sql`.

## 2026-05-11: Management Operations Completeness Pass

### Goal

Fill the main operational gaps in the V2 staff UI so setup and day-to-day management can be handled from `/manage` rather than direct SQL.

### What Changed

- Extended `api/manage/dashboard.php` with:
  - experiment eligibility rows
  - access pool rows and values
  - slot choices grouped by experiment
  - randomization allocation summaries
- Extended `api/manage/actions.php` with:
  - bulk allowlist import
  - delete experiment
  - delete condition with participation guard
  - delete access field
  - delete unassigned pool row
  - delete unused slot
  - delete eligibility row
- Updated `manage/index.html`, `manage/manage.js`, and `manage/manage.css` with:
  - bulk student import
  - delete buttons for setup records
  - eligibility review/removal
  - pool row inspection/removal
  - slot choice summaries
- Updated README and context docs.

### How To Run

Open `manage/index.html` after importing `database/schema.sql` and `database/seed.sql`.

### How To Test

- `php -l api/manage/dashboard.php`
- `php -l api/manage/actions.php`
- `php tests/validation_test.php`
- `php tests/text_quality_test.php`

### Known Issues And Decisions

- Deleting an experiment is a hard delete and removes its participations as well.
- Conditions cannot be deleted once participations reference them.
- Assigned pool rows cannot be deleted until the participation is reset/released.
- Slots cannot be deleted once students chose them.
- JavaScript syntax and browser behavior still need a deployed/MySQL QA pass because Node.js and local PDO drivers are unavailable in this environment.

### Next Steps

- Run a browser pass against the deployed database.
- Add more focused automated tests for management write actions once a usable PDO test driver is available.
