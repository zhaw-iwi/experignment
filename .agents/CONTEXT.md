# Experiment Assignment App Context

This file is the project-specific briefing for future Codex sessions. Read it together with `.agents/CODEX.md` and `.agents/PROJECT.md` before changing code.

## Purpose

This repository contains a deployable PHP/MySQL web application for assigning experiment participation opportunities and access information to ZHAW students. Students receive points for confirmed experiment participation; those confirmations affect course grading.

The app has two browser UIs:

- `index.html`: student-facing page.
- `manage/index.html`: staff-facing management page.

The deployed behavior began as V2, a greenfield continuation of an older one-experiment app. The V3 semester-preparation application behavior is complete. The canonical V4 application adds durable student participation chest events and an accessible student-side opening queue according to `.agents/PLAN_CHESTS.md`. Backward compatibility with the old V1 schema is intentionally not preserved.

## Current Product Decisions

- Students authenticate with a `@students.zhaw.ch` email address plus their individual access code.
- Student identity for overview, claim, and slot operations comes only from the server-side session, never from an email supplied to those endpoints.
- The staff UI and all management endpoints require the administrator access code configured as a hash in `.env`.
- Student and administrator sessions use secure, HTTP-only, SameSite cookies, idle expiration, login throttling, logout, and CSRF protection for writes.
- Changing a student's login-code version invalidates that student's existing session.
- The global roster is `allowed_students`; every student row has exactly one non-null `student_groups` membership.
- Course groups can be created explicitly or automatically by grouped roster import. Their point target may remain unset during initial import but must be completed before semester opening.
- Grouped roster imports are repeatable upserts: new students are added, existing course memberships are updated, and login-code hashes are preserved.
- A student's course membership cannot be changed after the first participation because that membership determines the applicable course target and experiment audience.
- Experiment course audience and individual eligibility are independent, intersecting gates. An empty course mapping means all courses; `all_allowed` includes every student in those courses, while `selected` additionally requires an `experiment_eligibilities` row.
- Students can claim at most one participation per visible experiment.
- Manual open state, `opens_at`, and `closes_at` jointly control current availability. Closed, scheduled, expired, and full experiments remain visible to eligible students, but the action button is disabled and unclaimed access data is not shown.
- `Zugewiesen` means the student has claimed or received access information for that experiment.
- `Angerechnet` means staff confirmed the participation for grading.
- Claiming access does not automatically mean `Angerechnet`.
- Staff can reset a participation and optionally release its access bundle for reuse.

## Confirmed V3 Preparation Decisions

- Each student belongs to one course group; an experiment can target multiple course groups.
- Each course group has its own point target per student; the target is not a hard cap.
- Experiments have numeric reward credits, an optional maximum participant count, optional `opens_at` and `closes_at` datetimes, and admin-only notes.
- Every confirmed participation contributes the experiment's full current reward. Earned totals may exceed the course target, and reward edits retroactively change computed totals.
- Student login requires email plus a student login code. Generated codes are five lowercase alphanumeric characters containing at least one letter and one digit; manually set codes may be longer and use uppercase letters.
- Only login-code hashes are persisted. Plaintext generated codes are available in a one-time CSV response and cannot be recovered later.
- Generated codes are created only for students whose code hash is missing. Existing codes are never included in or replaced by that batch response.
- Administrators can manually set or rotate one student's code; this increments the code version and immediately revokes that student's session.
- Regenerating a student login code must invalidate existing student sessions.
- The administrator UI will use a hashed access code configured through `.env` and a protected server-side session.
- Staff cannot manually open an experiment while a required readiness indicator is incomplete. Course audience, course point targets, student codes, condition setup/assignment, access-data completeness, and required slot capacity are blocking checks; no schedule and no participant maximum are warnings.
- `confirmed_at` is the grading-status gate. Student, dashboard, and report totals join confirmed participations to the current `experiments.reward_credits`; the legacy nullable `reward_credits_snapshot` column is ignored for totals and cleared by confirmation changes.
- Every future false-to-true confirmation atomically creates or reactivates one `participation_credited` row in `student_chest_events`. Its immutable trigger scope is the participation ID, and opening the chest only acknowledges an already-earned credit.
- Historical confirmations are not backfilled. Pending chest reads and idempotent opening are scoped exclusively to the authenticated student; opened and revoked rows remain as history.
- Unconfirming revokes an unopened chest, reconfirming reactivates that same unopened row, and an already-opened participation can never earn a second chest. Participation resets detach history and revoke only unopened events.
- Pending chests are shown FIFO through one shared student controller. An explicit login may open the first chest automatically; session restoration exposes only the navbar count so it does not unexpectedly move focus.
- Opening uses deterministic local gold chest art and a full pressure/burst/reveal/settle sequence. Reduced-motion users receive the same reveal and acknowledgement immediately, and acknowledgement failures remain retryable without replaying the effect.
- Participation and full reward credit remain possible after the course point target has been reached.
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
- Configure course audience, public availability window, participant maximum, course-independent numeric reward, and private notes.
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
- Review current credited rewards, dynamically computed course totals, and recent audit events.
- Reset participations and release access data when desired.

The current V2 staff API is intentionally centralized in `api/manage/actions.php`, with dashboard data from `api/manage/dashboard.php`. The staff UI is organized into an experiment overview, a dedicated global allowlist view, an experiment editing view, and an experiment-specific grading view. Experiment states are shown in a top workflow strip. In the overview, clicking an experiment row opens its editing view. The global allowlist is reached from the editable student-count badge in the navbar instead of being part of the experiment hierarchy, and the navbar brand returns to the experiment overview.

The grading view builds its table from the selected experiment configuration. It always shows who opened access information and when (`Zugang geöffnet`, backed by `participations.assigned_at`), and only shows condition, slot, compact access-field, and appointment columns when those features are configured for the experiment. Link access fields are shown as buttons labeled with the field name instead of raw URLs. Staff can filter and sort each data column in the grading table. The bulk-grading modal uses the same column dropdown presentation as an additive selection builder: searches and value checks add matching rows to the checked set, while row checkboxes remove individual selections. Bulk actions apply `Anrechnen`, `Anrechnung entfernen`, or `Reset` to explicit participation IDs. Bulk reset releases access pool rows and deletes related runtime data inside one transaction. A separate no-shows card lists registered or eligible students who have not clicked `Teilnehmen` and therefore have no participation row yet.

The Reports view is cross-experiment and read-only. It derives each student `Kürzel` from the local part of `allowed_students.student_email`, includes the student's course, dynamically computed credited total, and course target, includes every globally allowed student as one row, and includes every experiment as a `0`/`1` column. A value is `1` only when the matching participation has `confirmed_at IS NOT NULL`; opening access without staff confirmation remains `0`. Confirmed totals always use current experiment rewards. The visible rows can be filtered by `Kürzel` and course. The CSV download is generated from the currently displayed filtered/sorted table.

## Student UI Responsibilities

The student UI should:

- Ask for email and access code if no authenticated server session exists.
- Store only the email in local browser storage for convenience; never store the access code.
- Show a full-width navbar with `Experimente`, the active email, and a `Beenden` action once a student session is active.
- Load overview data dynamically from `api/student_overview.php`.
- Show visible experiments with columns for experiment, condition, assignment, assignment date, and `Angerechnet`.
- Show disabled buttons for closed experiments.
- Show a points card above the table with course name, earned points, course-specific target, true percentage, and an accessible progress bar whose visual width stops at 100%.
- Omit percentage and determinate progress for missing or zero targets while still showing earned points and the target state.
- Show the current reward for every experiment before and after confirmation without partial-credit wording.
- Show an authenticated navbar count for unopened chests and present accumulated chests sequentially through one semantic modal.
- Treat chest content as untrusted plain text, preload local art before enabling interaction, honor reduced motion in JavaScript and CSS, and cancel presentation timers on close or logout.
- Show effective availability and full-capacity state.
- Let students choose a condition only when the experiment uses `student_choice`.
- Claim/retrieve access through `api/claim.php`.
- Show access fields generically based on API payloads.
- Let students choose one slot through `api/choose_slot.php` when needed.
- Show staff-entered appointment text when available.

## Important Files

- `database/schema.sql`: canonical V4 schema.
- `database/migrations/2026-09-15-student-chests/migration.sql`: additive, phpMyAdmin-compatible V3-to-V4 migration with no historical chest backfill.
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
- `preflight/index.php`: disabled-by-default browser interface for the shared preflight, gated by `PREFLIGHT_ENABLED`, HTTPS, CSRF, and the administrator access code.
- `docs/PRODUCTION_CUTOVER.md`: canonical phpMyAdmin deployment, semester activation, acceptance, and rollback checklist.
- `api/_auth.php`: shared secure-session, authorization, CSRF, access-code validation, and throttling helpers.
- `api/_chests.php`: durable chest trigger, lifecycle, query, and acknowledgement helpers.
- `api/student_login.php`, `api/student_session.php`, `api/student_logout.php`: student authentication lifecycle.
- `api/student_chests.php`: authenticated pending/history chest read endpoint.
- `api/open_student_chest.php`: authenticated, CSRF-protected, idempotent chest acknowledgement endpoint.
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
- `assets/points.js`: pure student point formatting, target-state, progress-clamping, and experiment-reward display helpers.
- `assets/chests.js`: shared student chest queue, deterministic choreography, acknowledgement, retry, reduced-motion, and stale-callback controller.
- `assets/chests/`: local closed/open-gold RGBA art and generation/provenance notes.
- `manage/manage.js`: staff UI logic.
- `package.json`, `package-lock.json`: pinned Playwright, Chromium-test, and local Bootstrap test dependencies.
- `tests/browser/run.mjs`: isolated browser-test orchestrator that owns temporary SQLite, environment, server, and artifact resources.
- `tests/browser/student-points.spec.cjs`: desktop/mobile student progress DOM, accessibility, layout, and screenshot coverage.
- `tests/browser/student-chests.spec.cjs`: no/single/multiple event, full/reduced motion, duplicate activation, FIFO/reset, cancellation, slow/failing acknowledgement, multi-tab, keyboard/focus, local-asset, responsive, and screenshot coverage.
- `tests/chests_ui_test.js`: pure controller, timing, queue, cancellation, retry, reduced-motion, and local asset checks.
- `.agents/PROJECT.md`: milestone audit trail.

## Deployment And Configuration

Deploy the V4 schema into an empty database. For an existing V3 live installation, take a verified backup and apply the additive student-chest migration before deploying V4 application files. The migration does not backfill existing confirmations.

Database deployment settings are loaded from process environment variables or the ignored root `.env` file. `EXPERIMENT_DB_DSN` remains available as an optional override, mostly for tests. The database password that previously appeared in tracked configuration must be rotated before the next deployment.

The recommended clean cutover provisions a separate database and dedicated runtime account, imports `schema.sql` and the intentionally empty `seed.sql` in phpMyAdmin, then runs `php scripts/deployment_preflight.php --expect-empty`. The existing-V3 upgrade path instead applies the additive chest migration after a verified backup. The destructive fallback requires a verified backup followed by `drop_tables.sql` and a clean schema import; `reset_all_data.sql` is for an already-current schema, not a V2 migration.

Hosts without console access can temporarily set `PREFLIGHT_ENABLED=true` and use `/preflight/`. The browser endpoint runs the same checks after administrator-code and CSRF verification, must be accessed over HTTPS, and must be disabled again immediately after use.

## Tests And Local Limitations

Run:

- `php tests/config_test.php`
- `php tests/schema_test.php`
- `php tests/validation_test.php`
- `php tests/text_quality_test.php`
- `php tests/js_regression_test.php`
- `php tests/api_smoke_test.php`
- `node tests/points_ui_test.js`
- `npm run test:chests`
- `npm run test:browser`

`points_ui_test.js` covers fractional formatting, exact and above-target percentages, progress-width clamping, missing/zero targets, and reward display before and after confirmation.
`chests_ui_test.js` covers full/reduced timing branches, local asset mapping and decoding metadata, duplicate activation, queue reset, retry, and stale callback isolation.
`js_regression_test.php` catches management- and student-client regressions that JavaScript syntax checking would miss, including pool-rendering references to grading-only variables, progress accessibility/containment markup, and chest semantics/choreography guards.
The API smoke test uses a temporary SQLite database and skips when `pdo_sqlite` is unavailable. When SQLite support is available, it covers authentication, grouped rosters and access-code provisioning, course audiences, availability schedules, participant limits, readiness, private notes, undated slots, student claim/retrieval, slot capacity, management setup, eligibility guards, condition assignment, bundled pool import, staff-entered access values, dynamic uncapped reward confirmation, course-specific targets and percentages, audit events, participation reset, randomization, and the cross-experiment approval report.
The Playwright harness uses a temporary SQLite database and an explicitly selected temporary environment file. It replaces external CDN requests with the pinned local Bootstrap package and covers Course A below/above its 8-point target, Course B against its independent 10-point target, fractional rewards, missing/zero targets, ARIA progress values, and mobile layout. Chest cases cover empty/single/multiple queues, FIFO/reset, exact normal-motion classes/timing, immediate reduced motion, duplicate activation, stale-timer cancellation, slow/failing acknowledgement, retry, session restoration, multi-tab idempotency, keyboard focus, long mobile content, local image decoding, and stable screenshots.

On the current development machine as last observed:

- PHP syntax checks passed.
- `validation_test.php` passed.
- `text_quality_test.php` passed.
- `js_regression_test.php` passed.
- `api_smoke_test.php` passed after enabling `pdo_sqlite` in the active PHP `php.ini`.
- `node tests/points_ui_test.js` passed.
- Sixteen isolated Chromium scenarios passed: nine student-chest cases and seven student-points cases at desktop/mobile viewports.
- `node --check manage/manage.js` passed.
- Clean `schema.sql` plus `seed.sql` imports passed on MariaDB 10.6.28, MariaDB 11.4.13, and MySQL 8.4.10.
- Example-seed import followed by `reset_all_data.sql`, and full `drop_tables.sql` followed by rebuild, both passed.
- The deployment preflight passed against a dedicated MySQL account with only `SELECT`, `INSERT`, `UPDATE`, and `DELETE` privileges.
- A MySQL-backed HTTP acceptance flow previously passed administrator/student authentication, code generation, experiment opening, private-note isolation, participant-limit enforcement, the former capped reward behavior, reports, and audit-code secrecy; the dynamic reward semantics still require a fresh MySQL acceptance run.
- The repository owner's `.env.test` target passed a destructive local MySQL 8.0.34 cycle: clean import, example-seed/full-reset, full drop/rebuild, preflight, and comprehensive authenticated HTTP acceptance. It was returned to an empty schema-version-3 state afterward.

## Known Deferred Work

- Complete the clean MySQL/MariaDB production cutover and deployed-browser QA.
- Re-run the dynamic reward HTTP acceptance against a dedicated reset-approved local MySQL target when `.env.test` is available; the current isolated acceptance uses SQLite because no `.env.test` is present.
- Production access is not present in the repository: there is no deployment workflow, private `.env`, or production database/file-host credential in this workspace.
- More granular automated tests for management actions.
