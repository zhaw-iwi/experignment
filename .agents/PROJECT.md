# Project Audit Trail

## Summary

Experiment Assignment App is a PHP/MySQL application for managing student experiment access, student-visible participation information, staff-controlled eligibility, slot choices, appointments, and grading confirmation.

## Milestones

- [x] 2026-05-11: V2 greenfield multi-experiment implementation
- [x] 2026-05-11: Expanded project context for future Codex sessions
- [x] 2026-05-11: Fixed seed eligibility foreign key references
- [x] 2026-05-11: Split real and example seed data
- [x] 2026-05-11: Management operations completeness pass
- [x] 2026-05-11: Production readiness hardening pass
- [x] 2026-05-12: Management UI hierarchy redesign
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

## 2026-05-11: Production Readiness Hardening Pass

### Goal

Trace the user-interface to API to database workflows, remove concrete production-readiness gaps, and raise automated coverage around the highest-value student and staff flows.

### What Changed

- Removed committed database connection defaults from `config/config.php`.
- Added structured `DATABASE_NOT_CONFIGURED` failures when required database environment variables are missing.
- Made duplicate claim and duplicate slot-choice races return the existing successful state instead of a generic 500 when another request already completed the operation.
- Hardened management access-field operations:
  - missing field updates now return 404
  - fields backing assigned runtime values cannot be deleted
  - fields backing assigned runtime values cannot have their condition, key, type, or source changed
- Hardened slot updates:
  - missing slot updates now return 404
  - existing capacity cannot be reduced below the number of submitted choices
- Made tabular pool import compatible with PHP 8.5 by passing the `str_getcsv` escape parameter explicitly.
- Fixed the management reload button so click events are not rendered as success messages.
- Made the management "Neu" action visibly switch the experiment form into a new-experiment state and focus the name field.
- Hid experiment-dependent management panels until a saved experiment is selected.
- Expanded `tests/api_smoke_test.php` to cover management setup, eligibility assignment, pool import, managed claiming, confirmation, appointment retrieval, reset, deletion guards, and randomization when `pdo_sqlite` is available.
- Hardened the smoke-test server harness on Windows by preserving the parent process environment and launching the built-in PHP server without an intermediate shell.
- Updated README and context notes for the configuration and test coverage changes.

### How To Run

1. Configure database environment variables or set `EXPERIMENT_DB_DSN`.
2. Import `database/schema.sql`.
3. Import `database/seed.sql` for production-style setup or `database/seed_examples.sql` for a throwaway demo database.
4. Serve `index.html` and `manage/index.html` through PHP/web hosting.

### How To Test

- `Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }`
- `php tests/validation_test.php`
- `php tests/text_quality_test.php`
- `php tests/api_smoke_test.php`

Observed on 2026-05-11:

- PHP syntax checks passed for all PHP files.
- `validation_test.php` passed.
- `text_quality_test.php` passed.
- `api_smoke_test.php` passed after enabling `pdo_sqlite` in the active PHP `php.ini`.

### Known Issues And Decisions

- Staff authentication is still deferred and remains the largest production security gap.
- Browser/manual QA against a MySQL-backed environment is still needed before live use.
- Historical live data migration remains deferred.

### Next Steps

- Run a browser pass against a MySQL-backed database.
- Add staff authentication before broader deployment.

## 2026-05-12: Management UI Hierarchy Redesign

### Goal

Reduce cognitive load in the staff UI by replacing the single dense management surface with an overview, experiment editing view, and experiment-specific grading view.

### What Changed

- Rebuilt `manage/index.html` around Bootstrap cards and explicit views:
  - overview with experiments only
  - dedicated global allowed-student list opened from the count badge
  - experiment edit view with existing setup sections stacked full-width
  - experiment grading view with participation confirmation and appointment controls
- Replaced the breadcrumb with a full-width workflow strip for the overview, experiment edit view, and experiment grading view.
- Moved the global allowed-student entry point out of the hierarchy and into a right-aligned editable student-count badge.
- Added a single-step allowlist state with step number `0`, and made the navbar brand return to the experiment overview workflow.
- Made the management navbar full-width instead of constraining its brand and actions inside a Bootstrap container.
- Added an explicit `Abbrechen` action for unsaved new experiments; delete is only shown after the experiment has been saved.
- Hid `Gemeinsamer Wert` unless the access-field source is `Gleicher Wert für alle`, and prevented stale shared values from being saved for pool or staff-entry fields.
- Hid pool import controls until at least one saved access field uses `Aus Pool zuweisen`; pool import remains experiment/condition-scoped rather than tied to the currently edited field.
- Added form subheadings to separate existing configuration rows from create/edit forms for conditions, access data, and time slots.
- Made primary bottom actions span the available card width where appropriate, including experiment save/delete, access-field save, pool import, slot save, and randomization.
- Hid the time-slot setup card unless the saved experiment is marked as requiring time slots.
- Split experiment participant selection from condition assignment in the management UI.
- Added participant-selection and condition-assignment modals:
  - participants can be selected as all globally allowed students, a seeded random subset of size N, or manual email search/add
  - assigned-condition experiments can assign selected students manually or by seeded percentage randomization, with percentages required to sum to 100
- Aligned participant-selection modal method cards and changed their headings to verb phrases.
- Added `save_eligibility_selection` and `save_condition_assignments` management actions.
- Added guarded clear actions and management buttons for experiment participant selections and condition assignments.
- Guarded participant subset saves and condition assignment saves so students with existing participations are not removed or reassigned inconsistently.
- Added experiment-setup editing for `staff_entry` access-field values, backed by `eligibility_field_values` and copied into participations when access is opened.
- Added `database/migrate_staff_values.sql` for existing V2 deployments.
- Made the grading table derive optional columns from the selected experiment configuration and show the access reveal timestamp.
- Moved pool provisioning into a dedicated card and CSV modal with generated sample rows, plus a guarded whole-pool clear action.
- Changed experiment list items to expose a three-dot menu for editing, grading, and deletion.
- Added the global allowed-student list to `api/manage/dashboard.php`.
- Added `delete_allowed_student` in `api/manage/actions.php`; removal is blocked when a student already has participations.
- Updated `manage/manage.js` to use explicit view state instead of rendering every management section at once.
- Simplified `manage/manage.css` around Bootstrap cards, stacked views, and compact list items.
- Expanded `tests/api_smoke_test.php` with allowlist list/removal and removal guard coverage.
- Added smoke coverage that pool-sourced access fields do not persist shared values.
- Added smoke coverage for participant subset saving/clearing, condition assignment saving/clearing, staff-entered access values, and participation-related guards.
- Updated README and context documentation for the new staff UI structure and config-file deployment choice.

### How To Run

Open `manage/index.html` after importing `database/schema.sql` and `database/seed.sql`.

### How To Test

- `Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }`
- `node --check manage/manage.js`
- `php tests/validation_test.php`
- `php tests/text_quality_test.php`
- `php tests/api_smoke_test.php`

Observed on 2026-05-12:

- PHP syntax checks passed for all PHP files.
- `node --check manage/manage.js` passed.
- `validation_test.php` passed.
- `text_quality_test.php` passed.
- `api_smoke_test.php` passed.

### Known Issues And Decisions

- Removing a globally allowed student is blocked once participations exist, to avoid deleting grading-relevant records through allowlist cleanup.
- Staff authentication remains deferred and is still the largest production security gap.
- Browser QA on the deployed MySQL-backed UI is still needed.

### Next Steps

- Test the redesigned `/manage` flow on the deployment.
- Add staff authentication before broader deployment.
