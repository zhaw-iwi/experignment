# Experiment Assignment App Context

This file is the project-specific briefing for future Codex sessions. Read it together with `.agents/CODEX.md` and `.agents/PROJECT.md` before changing code.

## Purpose

This repository contains a deployable PHP/MySQL web application for assigning experiment participation opportunities and access information to ZHAW students. Students receive points for confirmed experiment participation; those confirmations affect course grading.

The app has two browser UIs:

- `index.html`: student-facing page.
- `manage/index.html`: staff-facing management page.

The deployed behavior began as V2, a greenfield continuation of an older one-experiment app. The V3 semester-preparation application behavior is complete and its clean production cutover is being prepared. Backward compatibility with the old V1 schema is intentionally not preserved.

## Current Product Decisions

- Students authenticate with a `@students.zhaw.ch` email address plus their individual access code.
- Student identity for overview, claim, and slot operations comes only from the server-side session, never from an email supplied to those endpoints.
- The staff UI and all management endpoints require the administrator access code configured as a hash in `.env`.
- Student and administrator sessions use secure, HTTP-only, SameSite cookies, idle expiration, login throttling, logout, and CSRF protection for writes.
- Changing a student's login-code version invalidates that student's existing session.
- The global roster is `allowed_students`; every student row has exactly one non-null `student_groups` membership.
- Course groups can be created explicitly or automatically by grouped roster import. Their point maximum may remain unset during initial import but must be completed before semester opening.
- Grouped roster imports are repeatable upserts: new students are added, existing course memberships are updated, and login-code hashes are preserved.
- A student's course membership cannot be changed after the first participation because that membership determines historical credit-cap ownership and experiment audience.
- Experiment course audience and individual eligibility are independent, intersecting gates. An empty course mapping means all courses; `all_allowed` includes every student in those courses, while `selected` additionally requires an `experiment_eligibilities` row.
- Students can claim at most one participation per visible experiment.
- Manual open state, `opens_at`, and `closes_at` jointly control current availability. Closed, scheduled, expired, and full experiments remain visible to eligible students, but the action button is disabled and unclaimed access data is not shown.
- `Zugewiesen` means the student has claimed or received access information for that experiment.
- `Angerechnet` means staff confirmed the participation for grading.
- Claiming access does not automatically mean `Angerechnet`.
- Staff can reset a participation and optionally release its access bundle for reuse.

## Confirmed V3 Preparation Decisions

- Each student belongs to one course group; an experiment can target multiple course groups.
- Each course group has a maximum credit total per student.
- Experiments have numeric reward credits, an optional maximum participant count, optional `opens_at` and `closes_at` datetimes, and admin-only notes.
- A reward that would cross the course maximum is partially counted, although course designers should configure rewards to avoid this case.
- Student login requires email plus a student login code. Generated codes are five lowercase alphanumeric characters containing at least one letter and one digit; manually set codes may be longer and use uppercase letters.
- Only login-code hashes are persisted. Plaintext generated codes are available in a one-time CSV response and cannot be recovered later.
- Generated codes are created only for students whose code hash is missing. Existing codes are never included in or replaced by that batch response.
- Administrators can manually set or rotate one student's code; this increments the code version and immediately revokes that student's session.
- Regenerating a student login code must invalidate existing student sessions.
- The administrator UI will use a hashed access code configured through `.env` and a protected server-side session.
- Staff cannot manually open an experiment while a required readiness indicator is incomplete. Course audience, course maxima, student codes, condition setup/assignment, access-data completeness, and required slot capacity are blocking checks; no schedule and no participant maximum are warnings.
- Confirming a participation stores the amount actually credited as a snapshot. It is the lesser of the experiment reward and the student's remaining course allowance, including zero after the cap; removing confirmation clears the snapshot.
- Participation remains possible after the course point maximum has been reached.
- Explicitly undated time slots have no start/end values and are the supported `Kein passender Termin` option.
- The audit log records successful authentication, student participation, code provisioning, and management actions without storing plaintext access codes.
- `APP_TIMEZONE` controls schedule parsing and comparison and defaults to `Europe/Zurich`.

## Experiment And Condition Semantics

`experiments` are visible student-facing rows/buttons. Students see the experiment's `public_name`.

`experiment_conditions` are optional visible conditions inside one experiment. They are useful when one grading unit has multiple conditions, for example:

- visible experiment: `Experiment 1`
- conditions: `Text`, `Tablet`
- grading: one `Angerechnet` status for `Experiment 1`, regardless of condition

If variants should be graded separately, model them as separate visible experiments instead, for example:

- `Experiment 2a`
- `Experiment 2b`

Condition assignment modes:

- `none`: no condition.
- `student_choice`: student chooses one condition when claiming.
- `assigned`: staff or randomization assigns a condition before claiming.

Eligibility modes:

- `all_allowed`: every email in `allowed_students` can see and claim.
- `selected`: only rows in `experiment_eligibilities` can see and claim.

The staff UI manages experiment participation as a separate step from condition assignment. Participant selection can use all globally allowed students, a seeded random subset of size N, or manual email search/add from the global allowlist. For assigned-condition experiments, condition assignment is managed after the participant subset. Random condition assignment uses explicit percentages that must sum to 100 and a visible seed; manual assignment uses one condition selector per selected student. Participant selections and condition assignments can be cleared before participations exist. Students with existing participations must remain in the experiment participant subset, and their assigned condition cannot be changed or cleared through the bulk assignment UI.

## Access Information Model

Do not hardcode access columns such as PID, survey link, or chatbot link into experiment-specific code.

The V2 model uses generic access fields:

- `access_fields`: defines labels, keys, value types, visibility, source, and order.
- `access_pool_rows`: bundled personalized access records.
- `access_pool_values`: field values belonging to one bundle.
- `eligibility_field_values`: staff-prepared field values for allowed/selected students before they open access.
- `participation_field_values`: copied field values attached to the participation after access is opened.
- `appointments`: final staff-assigned appointment text.

Access field sources:

- `shared`: same value for everyone, stored in `access_fields.shared_value`.
- `pool`: assigned from a bundled row in `access_pool_rows`.
- `staff_entry`: prepared by staff in experiment setup before any student opens access. Values are stored in `eligibility_field_values` and copied to `participation_field_values` when the student opens access; appointment-type fields use the dedicated appointment text flow.

Bundled pool rows are important. If a student receives a PID, personalized survey link, and chatbot link, these values must stay coupled by one `access_pool_rows` record. Do not assign each value from independent pools.

Pool import is experiment/condition-scoped, not tied to the currently edited field form. The management UI exposes a separate pool card when at least one saved access field uses `pool`; the modal generates a CSV sample from the applicable pool-sourced field keys. For experiments with configured condition rows, imports must target one condition because a participation can reserve only one bundled pool row; the condition CSV includes both experiment-wide pool fields and fields specific to that condition. Experiment-wide pool imports are used while no conditions exist, even if the condition mode has already been set up. Importing replaces unassigned pool rows for the selected scope. The experiment pool can be cleared only while none of its rows has been assigned.

Typical examples:

- Current-style Experiment 1 condition: pool fields for Participant ID, survey URL, chatbot URL.
- Experiment 1b style: pool field for Participant ID plus shared link.
- Experiment 3 style: pool field for Participant ID plus time-slot choice plus staff-entered appointment text.

## Slot And Appointment Semantics

Slot-based experiments set `experiments.requires_time_slot = 1`.

- Staff creates `time_slots` with labels, active state, sort order, capacity, and either a valid start/end pair or an explicit undated marker.
- The management UI shows time-slot setup only for saved experiments with `requires_time_slot = 1`.
- A student chooses exactly one slot.
- Capacity is enforced server-side.
- Staff may later enter `appointments.appointment_text`, usually a specific time within the chosen half-day slot.
- The chosen slot and assigned appointment are both shown to the student when the experiment is open.

## Randomization Semantics

Randomization assigns all students in the experiment's course audience to conditions for one experiment.

- It uses a staff-provided seed.
- Assignment order is deterministic by hashing `seed|email`.
- Percentages are normalized; they do not need to add exactly to 100.
- Rounding uses largest fractional remainders.
- Existing eligibilities for the experiment are replaced by a new randomization run.
- Randomization is blocked once any participation exists for the experiment.
- Staff override of a random assignment is not required at this stage.

## Staff UI Responsibilities

The management UI should support:

- Add allowed students.
- Bulk-import allowed students.
- View and remove allowed students who have no participations.
- Create and rename experiments.
- Delete setup experiments when needed.
- Open and close experiments.
- Configure eligibility mode, condition mode, slot requirement, and sort order.
- Configure course audience, public availability window, participant maximum, numeric reward, and private notes.
- Review readiness indicators and open an experiment only after blocking setup issues are resolved.
- Add and rename conditions.
- Delete unused conditions.
- Define access fields.
- Delete access fields.
- Import tabular access pool rows.
- View and delete unassigned access pool rows.
- Manually assign students to experiments and optional conditions.
- View and remove experiment-specific eligibility rows.
- Randomize all globally allowed students across conditions.
- Configure time slots and capacities.
- Provide explicit undated time-slot alternatives where needed.
- View slot choices grouped by slot.
- Delete unused time slots.
- View participations, selected slots, compact access values, and appointment text.
- Filter and sort the grading table by each data column.
- Apply bulk grading actions to checked participations.
- Show a navbar status indicator while backend requests are running.
- View registered students who have not opened access yet.
- View a cross-experiment approval report with one row per globally allowed student.
- Sort approval-report columns, filter the report by student `Kürzel` or course, and download the displayed report as CSV.
- Enter appointment text per participation.
- Toggle `Angerechnet`.
- Review credited rewards, course totals, and recent audit events.
- Reset participations and release access data when desired.

The current V2 staff API is intentionally centralized in `api/manage/actions.php`, with dashboard data from `api/manage/dashboard.php`. The staff UI is organized into an experiment overview, a dedicated global allowlist view, an experiment editing view, and an experiment-specific grading view. Experiment states are shown in a top workflow strip. In the overview, clicking an experiment row opens its editing view. The global allowlist is reached from the editable student-count badge in the navbar instead of being part of the experiment hierarchy, and the navbar brand returns to the experiment overview.

The grading view builds its table from the selected experiment configuration. It always shows who opened access information and when (`Zugang geöffnet`, backed by `participations.assigned_at`), and only shows condition, slot, compact access-field, and appointment columns when those features are configured for the experiment. Link access fields are shown as buttons labeled with the field name instead of raw URLs. Staff can filter and sort each data column in the grading table. The bulk-grading modal uses the same column dropdown presentation as an additive selection builder: searches and value checks add matching rows to the checked set, while row checkboxes remove individual selections. Bulk actions apply `Anrechnen`, `Anrechnung entfernen`, or `Reset` to explicit participation IDs. Bulk reset releases access pool rows and deletes related runtime data inside one transaction. A separate no-shows card lists registered or eligible students who have not clicked `Teilnehmen` and therefore have no participation row yet.

The Reports view is cross-experiment and read-only. It derives each student `Kürzel` from the local part of `allowed_students.student_email`, includes the student's course, credited total, and course maximum, includes every globally allowed student as one row, and includes every experiment as a `0`/`1` column. A value is `1` only when the matching participation has `confirmed_at IS NOT NULL`; opening access without staff confirmation remains `0`. The visible rows can be filtered by `Kürzel` and course. The CSV download is generated from the currently displayed filtered/sorted table.

## Student UI Responsibilities

The student UI should:

- Ask for email and access code if no authenticated server session exists.
- Store only the email in local browser storage for convenience; never store the access code.
- Show a full-width navbar with `Experimente`, the active email, and a `Beenden` action once a student session is active.
- Load overview data dynamically from `api/student_overview.php`.
- Show visible experiments with columns for experiment, condition, assignment, assignment date, and `Angerechnet`.
- Show disabled buttons for closed experiments.
- Show course credit progress, experiment rewards, effective availability, and full-capacity state.
- Let students choose a condition only when the experiment uses `student_choice`.
- Claim/retrieve access through `api/claim.php`.
- Show access fields generically based on API payloads.
- Let students choose one slot through `api/choose_slot.php` when needed.
- Show staff-entered appointment text when available.

## Important Files

- `database/schema.sql`: canonical V3 schema.
- `database/seed.sql`: intentionally empty production seed; semester rosters are imported through management.
- `database/seed_examples.sql`: self-contained demo/dev data with sample students, representative experiments, access fields, staff-prepared values, access pools, and slots.
- `database/reset.sql`: removes experiment setup and runtime data while preserving student groups, students, login-code state, and schema metadata.
- `database/reset_all_data.sql`: removes all semester, group, student, authentication, and audit data while preserving the schema.
- `database/drop_tables.sql`: drops all application tables in dependency order for full teardown/rebuild cycles.
- `database/live_database.sql`: historical live dump from the V1 app. Treat it as migration context only; do not edit it unless the user explicitly asks for migration work.
- `.env.example`: deployment configuration template; the real `.env` is ignored.
- `.env.test.example`: local test-database template; copy it to ignored `.env.test` and select it with `EXPERIMENT_ENV_FILE=.env.test`.
- `config/config.php`: environment-backed deployment configuration plus `EXPERIMENT_DB_DSN` test override.
- `scripts/generate_admin_access_code.php`: one-time administrator access-code and hash generator.
- `scripts/deployment_preflight.php`: production configuration, schema, empty-state, and rolled-back runtime-permission verifier.
- `docs/PRODUCTION_CUTOVER.md`: canonical phpMyAdmin deployment, semester activation, acceptance, and rollback checklist.
- `api/_auth.php`: shared secure-session, authorization, CSRF, access-code validation, and throttling helpers.
- `api/student_login.php`, `api/student_session.php`, `api/student_logout.php`: student authentication lifecycle.
- `api/manage/login.php`, `api/manage/session.php`, `api/manage/logout.php`: administrator authentication lifecycle.
- `api/_bootstrap.php`: shared API helpers and domain read helpers.
- `api/student_overview.php`: student overview endpoint.
- `api/claim.php`: claim or retrieve participation access.
- `api/choose_slot.php`: slot choice endpoint.
- `api/manage/dashboard.php`: staff dashboard payload.
- `api/manage/report.php`: cross-experiment approval report payload for UI and CSV export.
- `api/manage/actions.php`: staff write actions.
- `api/manage/generate_student_codes.php`: hash-only batch code generation with one-time plaintext CSV delivery.
- `assets/app.js`: student UI logic.
- `manage/manage.js`: staff UI logic.
- `.agents/PROJECT.md`: milestone audit trail.

## Deployment And Configuration

Deploy the V3 schema into an empty database. Previous-semester records will not be migrated into the prepared deployment.

Database deployment settings are loaded from process environment variables or the ignored root `.env` file. `EXPERIMENT_DB_DSN` remains available as an optional override, mostly for tests. The database password that previously appeared in tracked configuration must be rotated before the next deployment.

The recommended cutover provisions a separate clean database and dedicated runtime account, imports `schema.sql` and the intentionally empty `seed.sql` in phpMyAdmin, then runs `php scripts/deployment_preflight.php --expect-empty`. The in-place fallback requires a verified backup followed by `drop_tables.sql` and a clean schema import; `reset_all_data.sql` is for an already-V3 schema, not V2 migration.

## Tests And Local Limitations

Run:

- `php tests/config_test.php`
- `php tests/schema_test.php`
- `php tests/validation_test.php`
- `php tests/text_quality_test.php`
- `php tests/js_regression_test.php`
- `php tests/api_smoke_test.php`

`js_regression_test.php` currently catches management-client regressions that JavaScript syntax checking would miss, including pool-rendering references to grading-only variables.
The API smoke test uses a temporary SQLite database and skips when `pdo_sqlite` is unavailable. When SQLite support is available, it covers authentication, grouped rosters and access-code provisioning, course audiences, availability schedules, participant limits, readiness, private notes, undated slots, student claim/retrieval, slot capacity, management setup, eligibility guards, condition assignment, bundled pool import, staff-entered access values, capped reward confirmation, audit events, participation reset, randomization, and the cross-experiment approval report.

On the current development machine as last observed:

- PHP syntax checks passed.
- `validation_test.php` passed.
- `text_quality_test.php` passed.
- `js_regression_test.php` passed.
- `api_smoke_test.php` passed after enabling `pdo_sqlite` in the active PHP `php.ini`.
- `node --check manage/manage.js` passed.
- Clean `schema.sql` plus `seed.sql` imports passed on MariaDB 10.6.28, MariaDB 11.4.13, and MySQL 8.4.10.
- Example-seed import followed by `reset_all_data.sql`, and full `drop_tables.sql` followed by rebuild, both passed.
- The deployment preflight passed against a dedicated MySQL account with only `SELECT`, `INSERT`, `UPDATE`, and `DELETE` privileges.
- A MySQL-backed HTTP acceptance flow passed administrator/student authentication, code generation, experiment opening, private-note isolation, participant-limit enforcement, partial reward confirmation, reports, and audit-code secrecy.
- The repository owner's `.env.test` target passed a destructive local MySQL 8.0.34 cycle: clean import, example-seed/full-reset, full drop/rebuild, preflight, and comprehensive authenticated HTTP acceptance. It was returned to an empty schema-version-3 state afterward.

## Known Deferred Work

- Complete the clean MySQL/MariaDB production cutover and browser QA against the deployed environment.
- Production access is not present in the repository: there is no deployment workflow, private `.env`, or production database/file-host credential in this workspace.
- More granular automated tests for management actions.
