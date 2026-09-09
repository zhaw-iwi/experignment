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
- [x] 2026-05-12: Compact grading access links
- [x] 2026-05-12: Experiment overview click-to-edit
- [x] 2026-05-12: Grading no-shows card
- [x] 2026-05-13: Condition-scoped pool import guard
- [x] 2026-05-20: Conditionless pool import guard correction
- [x] 2026-05-20: Grading filters and bulk operations
- [x] 2026-05-26: Pool renderer grading regression fix
- [x] 2026-07-05: Tabular approval reports
- [x] 2026-09-09: V3 secure configuration and schema foundation
- [x] 2026-09-09: V3 student and administrator authentication
- [x] 2026-09-09: V3 course groups, roster import, and login-code lifecycle
- [x] 2026-09-09: V3 experiment audiences, capacity, rewards, readiness, and completeness
- [ ] V3 operational QA and clean production cutover

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
- Made the grading table derive optional columns from the selected experiment configuration and show the access reveal timestamp.
- Moved pool provisioning into a dedicated card and CSV modal with generated sample rows, plus a guarded whole-pool clear action.
- Updated the student page to use a full-width navbar with `Experimente`, active email display, and `Beenden`.
- Harmonized the student overview content width so alerts, tables, and detail panels align inside the same container.
- Added modal-local alerts for participant selection, staff values, pool import, and condition assignment workflows.
- Disabled student access-information actions after a participation is graded, and auto-dismissed page-level alerts after five seconds.
- Reconciled database scripts: `reset.sql` now preserves only the allowlist and schema metadata, and `seed_examples.sql` covers current pool, staff-value, assigned-condition, and slot workflows.
- Removed the obsolete staff-values migration script and added `database/drop_tables.sql` for ordered full schema teardown.
- Added cancel actions to the condition and access-field edit forms so they reset to new-entry mode.
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
- `js_regression_test.php` passed.
- `api_smoke_test.php` passed.

### Known Issues And Decisions

- Removing a globally allowed student is blocked once participations exist, to avoid deleting grading-relevant records through allowlist cleanup.
- Staff authentication remains deferred and is still the largest production security gap.
- Browser QA on the deployed MySQL-backed UI is still needed.

### Next Steps

- Test the redesigned `/manage` flow on the deployment.
- Add staff authentication before broader deployment.

## 2026-05-12: Compact Grading Access Links

### Goal

Reduce row height and horizontal width in the management grading table when access fields contain long URLs.

### What Changed

- Extended management dashboard participation rows with generic `accessItems` derived from the existing student access payload helper.
- Updated the experiment-specific grading table so the `Zugangsdaten` column covers visible access fields, not only staff-entry values.
- Rendered URL access fields as compact buttons labeled with the field name and opening in a new tab.
- Rendered non-link access values as compact truncated chips.
- Added smoke-test assertions for management dashboard access items.
- Updated README and context notes for the grading-table behavior.

### How To Run

Open `manage/index.html`, choose an experiment, and open Step 3 `Anrechnung`. The `Zugangsdaten` column appears when the experiment has visible non-appointment access fields; link fields show as labeled buttons instead of raw URLs.

### How To Test

- `php -l api/manage/dashboard.php`
- `node --check manage/manage.js`
- `php tests/validation_test.php`
- `php tests/text_quality_test.php`
- `php tests/api_smoke_test.php`

Observed on 2026-05-12:

- PHP syntax check passed for `api/manage/dashboard.php`.
- `validation_test.php` passed.
- `text_quality_test.php` passed.
- `api_smoke_test.php` skipped because this PHP installation has no PDO drivers.
- JavaScript syntax checking could not be run because Node.js is not installed in this environment.

### Known Issues And Decisions

- Appointment-type access fields stay in the dedicated appointment column.
- The grading access column follows student-visible access-field visibility.

### Next Steps

- Browser-check the grading table with representative long survey and chatbot URLs.

## 2026-05-12: Experiment Overview Click-To-Edit

### Goal

Make the management experiment overview faster to use by opening Step 2 `Bearbeiten` directly when staff click an experiment row.

### What Changed

- Made overview experiment list items clickable and keyboard-focusable.
- Kept the three-dot action menu independent so `Anrechnung` and delete actions do not also open editing.
- Updated README and context notes for the overview behavior.

### How To Run

Open `manage/index.html`. In the experiment overview, click any experiment list row to open its editing view.

### How To Test

- `node --check manage/manage.js`
- `php tests/text_quality_test.php`

Observed on 2026-05-12:

- `text_quality_test.php` passed.
- JavaScript syntax checking could not be run because Node.js is not installed in this environment.

### Known Issues And Decisions

- The action menu remains the path for opening Step 3 `Anrechnung` directly from an overview row.

### Next Steps

- Browser-check row click, keyboard Enter/Space, and action-menu behavior in `/manage`.

## 2026-05-12: Grading No-Shows Card

### Goal

Show staff which registered students have not opened experiment access yet.

### What Changed

- Added a `No-Shows` card underneath the Step 3 `Anrechnung` grading card.
- Calculated no-shows client-side from the experiment's effective participant set minus existing participation rows.
- Included condition/source metadata where available.
- Added a scroll limit so large all-allowed experiments do not make the grading view unwieldy.
- Updated README and context notes.

### How To Run

Open `manage/index.html`, choose an experiment, and open Step 3 `Anrechnung`. The `No-Shows` card lists registered or eligible students who have not clicked `Teilnehmen`.

### How To Test

- `node --check manage/manage.js`
- `php tests/text_quality_test.php`

Observed on 2026-05-12:

- `text_quality_test.php` passed.
- JavaScript syntax checking could not be run because Node.js is not installed in this environment.

### Known Issues And Decisions

- For `all_allowed` experiments, no-shows are all globally allowed students minus those with participation rows.
- For selected-participant experiments, no-shows are selected eligibilities without participation rows.

### Next Steps

- Browser-check the card with both selected and all-allowed experiments.

## 2026-05-13: Condition-Scoped Pool Import Guard

### Goal

Prevent staff from importing misleading experiment-wide pool rows for experiments whose participations are assigned to conditions.

### What Changed

- Disabled the `Experimentweit` pool scope in the management pool modal when an experiment uses conditions.
- Added modal help text explaining that condition CSV imports include both experiment-wide pool fields and condition-specific pool fields.
- Added a server-side `CONDITION_POOL_REQUIRED` guard for direct `import_pool_rows` calls without a condition on conditioned experiments.
- Added smoke-test coverage for the new guard.
- Updated README and project context documentation.

### How To Run

Open `manage/index.html`, select a conditioned experiment with pool fields, and open `Zugangsdaten-Pool bereitstellen`.

### How To Test

- `php -l api/manage/actions.php`
- `node --check manage/manage.js`
- `php tests/validation_test.php`
- `php tests/text_quality_test.php`
- `php tests/api_smoke_test.php`

### Known Issues And Decisions

- A participation still reserves one bundled pool row. For conditioned experiments, duplicate experiment-wide values such as PID or survey link inside each condition-specific CSV when they need to stay coupled with condition-specific links.
- Existing mistakenly imported experiment-wide rows are not deleted automatically.

### Next Steps

- Remove any unused experiment-wide pool rows from affected conditioned experiments before opening them to students.

## 2026-05-20: Conditionless Pool Import Guard Correction

### Goal

Keep the condition-scoped pool import guard for experiments that actually have conditions, while allowing experiment-wide pool imports before any condition rows exist.

### What Changed

- Changed the management pool modal to require a condition only when condition mode is active and the experiment has saved condition rows.
- Hid and disabled the pool condition selector for conditionless experiments.
- Changed the server-side `CONDITION_POOL_REQUIRED` guard to use the same saved-condition-row rule.
- Added smoke-test coverage for an assigned-mode experiment with no conditions importing an experiment-wide pool.
- Updated README and project context documentation.

### How To Run

Open `manage/index.html`, choose a saved experiment with no conditions, add or select an access field sourced from `Aus Pool zuweisen`, and open `Zugangsdaten-Pool bereitstellen`.

### How To Test

- `php -l api/manage/actions.php`
- `node --check manage/manage.js`
- `php tests/validation_test.php`
- `php tests/text_quality_test.php`
- `php tests/api_smoke_test.php`

Observed on 2026-05-20:

- PHP syntax check passed for `api/manage/actions.php`.
- JavaScript syntax check passed for `manage/manage.js`.
- `validation_test.php` passed.
- `text_quality_test.php` passed.
- `api_smoke_test.php` passed.

### Known Issues And Decisions

- Condition mode alone does not make a pool import condition-scoped; at least one saved condition row must exist.
- Existing condition-scoped guard behavior is preserved for experiments with configured conditions.

### Next Steps

- Browser-check the live management page after deployment with both conditionless and conditioned pool-field experiments.

## 2026-05-20: Grading Filters And Bulk Operations

### Goal

Make experiment grading easier to operate by adding column-level filters/sort controls and checked-row bulk actions for `Anrechnen`, `Anrechnung entfernen`, and `Reset`.

### What Changed

- Added reusable data-column text accessors for the grading table.
- Added per-column search, value selection, and sort dropdowns above the grading table.
- Added a grading bulk-action modal with the same filter/sort model, explicit row checkboxes, select-all-visible, and clear-selection controls.
- Changed the bulk modal dropdown behavior to build selections additively, so repeated student searches keep previously checked rows until they are unchecked in the table.
- Added `bulk_grading_operation` to `api/manage/actions.php` for confirm, unconfirm, and reset operations across selected participation IDs.
- Made bulk reset transactional and set-based across pool-row release, participation field values, appointments, slot choices, and participation deletion.
- Added a navbar ready/working indicator backed by a shared frontend request counter.
- Added smoke-test coverage for bulk confirm, bulk unconfirm, bulk reset, and wrong-experiment participation rejection.
- Updated README and project context documentation.

### How To Run

Open `manage/index.html`, choose an experiment, open Step 3 `Anrechnung`, use the column dropdowns to filter/sort the table, or open `Sammelaktion` to select checked rows and execute a bulk grading action.

### How To Test

- `php -l api/manage/actions.php`
- `node --check manage/manage.js`
- `php tests/validation_test.php`
- `php tests/text_quality_test.php`
- `php tests/api_smoke_test.php`

Observed on 2026-05-20:

- PHP syntax check passed for `api/manage/actions.php`.
- JavaScript syntax check passed for `manage/manage.js`.
- `validation_test.php` passed.
- `text_quality_test.php` passed.
- `api_smoke_test.php` passed.

### Known Issues And Decisions

- Bulk actions send explicit participation IDs to the server, not client-side filter definitions.
- Server-side validation rejects selected IDs that do not belong to the requested experiment.
- `Entfernen` means removing `Angerechnet`; `Reset` remains the operation that deletes the participation assignment and releases access data.
- Main-table dropdowns remain normal filters; bulk-modal dropdowns are additive selection helpers.

### Next Steps

- Browser-check the grading table dropdown behavior and the bulk modal on representative deployed data.

## 2026-05-26: Pool Renderer Grading Regression Fix

### Goal

Restore the management setup and grading views for experiments that use access-pool fields.

### What Changed

- Fixed `renderPoolSection()` so its empty-state check uses the pool section's local `rows` variable instead of the grading table's `visibleRows` variable.
- Added `tests/js_regression_test.php` to catch this management-client regression before deployment.
- Updated README and context documentation with the new regression test command.

### How To Run

Open `manage/index.html`, choose an experiment with pool-sourced access fields, and open either `Setup bearbeiten` or `Anrechnung`.

### How To Test

- `node --check manage/manage.js`
- `php tests/validation_test.php`
- `php tests/text_quality_test.php`
- `php tests/js_regression_test.php`
- `php tests/api_smoke_test.php`

Observed on 2026-05-26:

- JavaScript syntax check passed for `manage/manage.js`.
- PHP syntax checks passed for all PHP files.
- `validation_test.php` passed.
- `text_quality_test.php` passed.
- `js_regression_test.php` passed.
- `api_smoke_test.php` passed.

### Known Issues And Decisions

- The live database dump was used only to confirm that the affected experiments all have pool-sourced access fields. It was not modified.
- This is a frontend rendering regression; no schema or API contract changed.

### Next Steps

- Browser-check `Experiment 1c`, `Experiment 2a`, `Experiment 2b`, and `Experiment 2c` in both setup and grading views after deployment.

## 2026-07-05: Tabular Approval Reports

### Goal

Add a management report that can be copied into grading workflows: one row per student, one `Kürzel` column, and one `0`/`1` column per experiment based on staff-confirmed `Angerechnet` status.

### What Changed

- Added `student_code_from_email()` to derive the ZHAW student `Kürzel` from the email local part.
- Added `api/manage/report.php` as a read-only report endpoint over all allowed students and all experiments.
- Added a Reports view to `manage/index.html` and `manage/manage.js`.
- Added client-side report sorting by any column.
- Added `Kürzel` filtering.
- Added CSV download for the currently displayed filtered/sorted report table.
- Styled compact report sort controls in `manage/manage.css`.
- Extended validation, text-quality, and API smoke coverage for the report behavior.
- Updated README and project context documentation.

### How To Run

Open `manage/index.html`, click `Reports` in the navbar, optionally filter by `Kürzel`, sort columns by clicking headers, and use `CSV herunterladen` to download the displayed table.

### How To Test

- `Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }`
- `node --check manage/manage.js`
- `php tests/validation_test.php`
- `php tests/text_quality_test.php`
- `php tests/js_regression_test.php`
- `php tests/api_smoke_test.php`

Observed on 2026-07-05:

- PHP syntax checks passed for all PHP files.
- `node --check manage/manage.js` passed.
- `validation_test.php` passed.
- `text_quality_test.php` passed.
- `api_smoke_test.php` passed.
- Local fixture-backed HTTP checks passed for `manage/index.html`, `api/manage/dashboard.php`, and `api/manage/report.php`.
- A direct local check against the configured live MySQL database could not be completed because the database connection was unavailable from this environment.

### Known Issues And Decisions

- Report columns are all experiments sorted by experiment order and id.
- Report rows are all globally allowed students.
- A report value is `1` only when `participations.confirmed_at` is set; opened access without confirmation remains `0`.
- The CSV uses semicolon delimiters and a UTF-8 BOM for practical Excel import in the German/Swiss locale.
- The CSV reflects the currently displayed filtered/sorted table.

### Next Steps

- Browser-check the Reports view against the live MySQL-backed deployment data.

## 2026-09-09: V3 Secure Configuration And Schema Foundation

### Goal

Prepare an additive V3 database and configuration foundation for a clean-semester deployment without breaking the existing V2 runtime flows while subsequent milestones activate authentication, course groups, capacity, rewards, scheduling, readiness checks, and auditing.

### What Changed

- Removed the live database credential literals from tracked `config/config.php`.
- Added a strict, dependency-free `.env` loader with process-environment precedence.
- Added `.env.example` for database and administrator-code-hash configuration.
- Added ignore rules for `.env`, local live database dumps, temporary student data, logs, and local test state.
- Added Apache rules that disable directory listing and deny direct HTTP access to hidden and internal project paths.
- Advanced the clean-install schema marker to version 3.
- Added additive schema foundations for:
  - course groups and per-group maximum credits
  - one optional course-group reference and login-code metadata per student
  - authentication throttling state
  - experiment admin notes, availability schedule, participant maximum, and numeric reward
  - experiment-to-group eligibility
  - participation reward snapshots
  - explicitly undated time slots
  - audit events
- Kept `allowed_students.group_id` nullable during this compatibility milestone; the structured roster milestone will require a group at application boundaries before activating the feature.
- Replaced the production seed roster with an intentionally empty production seed.
- Updated the example seed with representative course groups and student memberships.
- Added `database/reset_all_data.sql` for a true semester reset while keeping `database/reset.sql` as the experiment-only reset that preserves groups and students.
- Updated the full drop script for all V3 tables.
- Advanced the bootstrap version response to 3.
- Added focused environment-loader and schema-contract tests.
- Updated README and canonical project context documentation, preserving the newer approval-report milestone from `origin/main`.

### How To Run

1. Copy `.env.example` to `.env` and replace its placeholders.
2. For a clean installation, import `database/schema.sql` into an empty database.
3. Do not import any live dump. `database/seed.sql` is optional and intentionally inserts no records.
4. Continue using the existing runtime UI only with a configured database; V3 behavior is activated by subsequent milestones.

### How To Test

- `Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }`
- `node --check manage/manage.js`
- `node --check assets/app.js`
- `php tests/config_test.php`
- `php tests/schema_test.php`
- `php tests/validation_test.php`
- `php tests/text_quality_test.php`
- `php tests/js_regression_test.php`
- `php tests/api_smoke_test.php`

Observed on 2026-09-09:

- PHP syntax checks passed for all PHP files.
- JavaScript syntax checks passed for both browser applications.
- `config_test.php` passed.
- `schema_test.php` passed.
- `validation_test.php` passed.
- `text_quality_test.php` passed.
- `js_regression_test.php` passed.
- `api_smoke_test.php` passed.
- A live MySQL import was not attempted because the local MySQL server requires credentials; MariaDB 10.6 clean-import verification remains part of the cutover milestone.

### Known Issues And Decisions

- The schema changes are intentionally additive in this milestone so the current runtime remains operable. New schema capabilities are not yet exposed as public behavior.
- Student group membership is nullable only during the compatibility sequence. The roster-management milestone will require exactly one group per imported or manually managed student.
- Generated login-code plaintext will be returned only in its one-time CSV response; only hashes will be stored.
- Course rewards may be partially counted at the group maximum, though course design should avoid requiring partial rewards.
- The current database password must be rotated before deployment because removing it from the latest source does not remove it from repository history.
- A real `.env` must be provisioned on the host before deploying this commit.
- Local live dumps and temporary student files remain on the filesystem but are ignored and were not modified or staged. The newer `database/live_database_dump.sql` already present on `origin/main` was preserved during the rebase.

### Next Steps

- Implement student email-plus-code authentication and administrator code authentication.
- Add secure sessions, code-version revocation, throttling, logout, CSRF protection, and protected management endpoints.
- Add an administrator-code hash provisioning helper.

## 2026-09-09: V3 Student And Administrator Authentication

### Goal

Replace email-only student identification and hidden-URL management access with secure, revocable student and administrator sessions before activating the new semester roster.

### What Changed

- Added student login, session-status, and logout endpoints using email plus an individual hashed access code.
- Bound student overview, claim, and slot-choice identity to the authenticated session instead of request-supplied email values.
- Added administrator login, session-status, and logout endpoints backed by `ADMIN_ACCESS_CODE_HASH` from `.env`.
- Protected all management read endpoints with administrator authentication and all student/management writes with session-bound CSRF tokens.
- Added secure session-cookie defaults, configurable idle timeouts, session-ID regeneration at login, and login-code-version revocation for student sessions.
- Added database-backed throttling for repeated student and administrator login failures and generic authentication error responses.
- Added no-store and content-type hardening headers to JSON responses.
- Added student and administrator login/logout user interfaces without storing plaintext codes in browser storage.
- Added `scripts/generate_admin_access_code.php` to generate a strong one-time administrator code and its password hash.
- Expanded validation, text-quality, configuration, and API smoke coverage for authentication, CSRF, identity binding, and code-change revocation.
- Updated deployment and canonical context documentation.

### How To Run

1. Run `php scripts/generate_admin_access_code.php`.
2. Put the displayed `ADMIN_ACCESS_CODE_HASH=...` line in the private deployment `.env`.
3. Keep the displayed plaintext administrator code in a separate secure channel; it cannot be recovered from the hash.
4. Use HTTPS in production with `APP_SESSION_SECURE=true`.
5. Student access-code creation and one-time CSV delivery are completed by the next roster milestone.

### How To Test

- `Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }`
- `node --check assets/app.js`
- `node --check manage/manage.js`
- `php tests/config_test.php`
- `php tests/schema_test.php`
- `php tests/validation_test.php`
- `php tests/text_quality_test.php`
- `php tests/js_regression_test.php`
- `php tests/api_smoke_test.php`

Observed on 2026-09-09:

- PHP syntax checks passed for all PHP files.
- JavaScript syntax checks passed for both browser applications.
- All six PHP test scripts passed, including the SQLite-backed authentication smoke flow.

### Known Issues And Decisions

- Plaintext student codes are intentionally not recoverable. Their generation, one-time CSV download, and manual rotation controls belong to the roster milestone.
- Student and administrator authentication share one application session cookie but use isolated role-specific session records and CSRF tokens.
- A student session is invalidated whenever that student's persisted `login_code_version` changes.
- Authentication throttling uses a 15-minute attempt window, five failures, and a 15-minute lock.
- Production cookie security assumes HTTPS and `APP_SESSION_SECURE=true`.

### Next Steps

- Implement mandatory course-group membership at roster import and student management boundaries.
- Add one-time generated student-code CSV delivery, generate-missing behavior, and manual code rotation.

## 2026-09-09: V3 Course Groups, Roster Import, And Login-Code Lifecycle

### Goal

Make course membership a required part of the semester roster and provide the complete, non-recoverable student access-code provisioning workflow.

### What Changed

- Made `allowed_students.group_id` non-null in the clean-install schema.
- Added management create, edit, and guarded-delete operations for course groups and their optional point maximum.
- Required a course for manual student creation and allowed manual updates to an existing student's course membership.
- Replaced email-only bulk import with repeatable grouped roster upserts supporting comma-, semicolon-, or tab-delimited `email` and `group` columns.
- Made roster imports preserve existing login codes, update changed course memberships, and create previously unknown course labels.
- Added course, code-completeness, and code-set-time information to the management dashboard without exposing password hashes.
- Added roster filters for email and course.
- Added course data and course filtering to the cross-experiment report and its displayed CSV export.
- Added a cryptographically random five-character lowercase letter/digit generator that guarantees at least one letter and one digit.
- Added a protected batch endpoint that hashes codes, generates only missing codes, and returns plaintext only in its immediate two-column CSV response.
- Added an explicit one-time-download warning and confirmation in the management UI.
- Added per-student manual code set/rotation with server-side complexity validation and code-version session invalidation.
- Expanded the SQLite HTTP smoke test for course operations, repeat imports, code generation, hash-only persistence, no-repeat export, manual code validation, and revocation.
- Updated README and canonical project context documentation.

### How To Run

1. Open the `Zugelassene Studierende` management view.
2. Create course groups directly, or import a roster with headers such as `email;group`; unknown group labels are created automatically.
3. Set each group's maximum points before opening the semester. An unset value remains visible as an incomplete configuration for the readiness milestone.
4. Click `Fehlende Codes erstellen und CSV laden` once the roster is ready, confirm the warning, and retain the downloaded CSV securely.
5. Use `Code setzen` or `Code ändern` on an individual student when manual provisioning or rotation is needed.

### How To Test

- `Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }`
- `node --check assets/app.js`
- `node --check manage/manage.js`
- `php tests/config_test.php`
- `php tests/schema_test.php`
- `php tests/validation_test.php`
- `php tests/text_quality_test.php`
- `php tests/js_regression_test.php`
- `php tests/api_smoke_test.php`

Observed on 2026-09-09:

- PHP syntax checks passed for all PHP files.
- JavaScript syntax checks passed for both browser applications.
- All six PHP test scripts passed, including the SQLite-backed grouped-roster and one-time-code flow.

### Known Issues And Decisions

- The production seed remains empty. The example seed intentionally leaves student codes unset so the one-time generation workflow can be exercised.
- Imports are upserts, not destructive synchronization: students omitted from a later file are retained.
- A student belongs to exactly one course. Re-importing that email with a different group updates the membership without changing the student's access code.
- A course cannot be deleted while referenced by a student or experiment audience.
- The batch endpoint never returns, recovers, or replaces existing codes. A lost CSV therefore requires explicit per-student rotation or clearing/regeneration in a future operation.
- Manually set codes allow uppercase characters but remain strictly alphanumeric and require at least one letter and one digit.

### Next Steps

- Activate experiment course audiences, schedules, participant maxima, rewards, and capped credit totals.
- Add ready-to-open validation, operational completeness indicators, explicit undated slots, admin notes, and audit events.

## 2026-09-09: V3 Experiment Audiences, Capacity, Rewards, Readiness, And Completeness

### Goal

Activate all semester-level experiment controls, capped reward accounting, operational readiness validation, explicit undated scheduling, private notes, and audit history on top of the authenticated grouped roster.

### What Changed

- Added experiment course audiences, with an empty mapping representing all courses and selected mappings intersecting the existing `all_allowed` or `selected` individual eligibility mode.
- Scoped participant selection, manual assignment, condition randomization, visibility, and claims to the experiment's course audience.
- Added public `opens_at` and `closes_at` availability windows interpreted in configurable `APP_TIMEZONE`, while retaining the manual open switch.
- Added optional experiment participant maxima with serialized server-side claim enforcement and guards against reducing a limit below existing participation.
- Added numeric experiment rewards and course credit maxima to student, grading, and report payloads and interfaces.
- Added transactional reward confirmation snapshots: the final reward can be partially credited at the course cap and later confirmed participations receive zero without blocking access or participation.
- Prevented course changes after a student's first participation, course-audience removal of existing participants, and course-maximum reductions below already credited totals.
- Added admin-only experiment notes that are omitted from every student response.
- Added ready-to-open indicators for audience, course maxima, access codes, conditions/assignments, access-data completeness, pool capacity, and time-slot capacity.
- Blocked opening on readiness errors while treating an omitted schedule or participant maximum as an advisory warning.
- Added explicit undated time slots that require null start/end values; dated slots require a valid start/end pair.
- Added a recent audit-event view and successful-event logging for authentication, student claims/retrievals, slot choices, student-code provisioning, and management actions without recording plaintext access codes.
- Extended the student UI with course point progress, reward values, effective availability, and full-capacity state.
- Extended reports with credited point totals and course maxima while retaining per-experiment confirmed `0`/`1` columns.
- Expanded schema, validation, regression, and SQLite HTTP smoke coverage for the new behavior.
- Updated README and canonical context documentation.

### How To Run

1. Set `APP_TIMEZONE=Europe/Zurich` or another valid PHP timezone in the private deployment `.env`.
2. Import a grouped roster, generate its missing student codes, and set every target course's point maximum.
3. Create an experiment, configure its course audience, schedule, capacity, reward, eligibility/access data, and any required slots.
4. Resolve all blocking readiness indicators, then enable `Für Studierende freigeben`.
5. Confirm completed participations from `Anrechnung`; credited totals and course limits appear in both grading and Reports.
6. Review recent operational events in the management overview audit card.

### How To Test

- `Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }`
- `node --check assets/app.js`
- `node --check manage/manage.js`
- `php tests/config_test.php`
- `php tests/schema_test.php`
- `php tests/validation_test.php`
- `php tests/text_quality_test.php`
- `php tests/js_regression_test.php`
- `php tests/api_smoke_test.php`

Observed on 2026-09-09:

- PHP syntax checks passed for all PHP files.
- JavaScript syntax checks passed for both browser applications.
- All six PHP test scripts passed, including the SQLite-backed end-to-end V3 operations flow.

### Known Issues And Decisions

- Course audience and individual eligibility are intentionally intersecting gates rather than alternatives.
- Readiness checks evaluate the effective target audience. For selected individual eligibility, access-code, condition, and staff-entry checks therefore apply only to selected students in target courses.
- No schedule and no maximum participant count are allowed but shown as warnings; incomplete audience, course maxima, codes, required condition/access data, or slot capacity block opening.
- Confirmation order determines who receives a partial last reward when a course maximum is reached. Reward snapshots keep already reported totals stable if an experiment's configured reward changes later.
- Removing `Angerechnet` clears its reward snapshot. Re-confirming recalculates it against the then-current remaining course allowance.
- Audit-event insertion failures are reported to the server error log but do not fail an otherwise successful user operation.
- Existing production data is not migrated. The cutover uses a clean V3 database and intentionally empty production seed.

### Next Steps

- Verify a clean `schema.sql` import against MySQL/MariaDB and run the complete HTTP/browser acceptance checklist.
- Prepare the production `.env`, rotate the historically exposed database password, generate a fresh administrator code hash, deploy the application, and import the new grouped roster.
