# Experiment Assignment App Context

This file is the project-specific briefing for future Codex sessions. Read it together with `.agents/CODEX.md` and `.agents/PROJECT.md` before changing code.

## Purpose

This repository contains a deployable PHP/MySQL web application for assigning experiment participation opportunities and access information to ZHAW students. Students receive points for confirmed experiment participation; those confirmations affect course grading.

The app has two browser UIs:

- `index.html`: student-facing page.
- `manage/index.html`: staff-facing management page.

The implementation is V2, a greenfield continuation of an older one-experiment app. Backward compatibility with the old schema is intentionally not preserved.

## Current Product Decisions

- Students identify by `@students.zhaw.ch` email address.
- Email-only retrieval of previous access information is accepted for now.
- Staff authentication is not implemented yet. The management UI is protected only by a hidden URL for now.
- The global allowlist is `allowed_students`.
- Individual experiments can be visible to all globally allowed students or only to explicitly eligible students.
- Students can claim at most one participation per visible experiment.
- Closed experiments remain visible to eligible students, but the action button is disabled and access data is not shown.
- `Zugewiesen` means the student has claimed or received access information for that experiment.
- `Angerechnet` means staff confirmed the participation for grading.
- Claiming access does not automatically mean `Angerechnet`.
- Staff can reset a participation and optionally release its access bundle for reuse.

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

- Staff creates `time_slots` with labels, optional start/end datetimes, active state, sort order, and capacity.
- The management UI shows time-slot setup only for saved experiments with `requires_time_slot = 1`.
- A student chooses exactly one slot.
- Capacity is enforced server-side.
- Staff may later enter `appointments.appointment_text`, usually a specific time within the chosen half-day slot.
- The chosen slot and assigned appointment are both shown to the student when the experiment is open.

## Randomization Semantics

Randomization assigns all globally allowed students to conditions for one experiment.

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
- View slot choices grouped by slot.
- Delete unused time slots.
- View participations, selected slots, compact access values, and appointment text.
- Filter and sort the grading table by each data column.
- Apply bulk grading actions to checked participations.
- Show a navbar status indicator while backend requests are running.
- View registered students who have not opened access yet.
- Enter appointment text per participation.
- Toggle `Angerechnet`.
- Reset participations and release access data when desired.

The current V2 staff API is intentionally centralized in `api/manage/actions.php`, with dashboard data from `api/manage/dashboard.php`. The staff UI is organized into an experiment overview, a dedicated global allowlist view, an experiment editing view, and an experiment-specific grading view. Experiment states are shown in a top workflow strip. In the overview, clicking an experiment row opens its editing view. The global allowlist is reached from the editable student-count badge in the navbar instead of being part of the experiment hierarchy, and the navbar brand returns to the experiment overview.

The grading view builds its table from the selected experiment configuration. It always shows who opened access information and when (`Zugang geöffnet`, backed by `participations.assigned_at`), and only shows condition, slot, compact access-field, and appointment columns when those features are configured for the experiment. Link access fields are shown as buttons labeled with the field name instead of raw URLs. Staff can filter and sort each data column in the grading table. The bulk-grading modal uses the same column dropdown presentation as an additive selection builder: searches and value checks add matching rows to the checked set, while row checkboxes remove individual selections. Bulk actions apply `Anrechnen`, `Anrechnung entfernen`, or `Reset` to explicit participation IDs. Bulk reset releases access pool rows and deletes related runtime data inside one transaction. A separate no-shows card lists registered or eligible students who have not clicked `Teilnehmen` and therefore have no participation row yet.

## Student UI Responsibilities

The student UI should:

- Ask for email if no local email is stored.
- Store the email in local browser storage for convenience.
- Show a full-width navbar with `Experimente`, the active email, and a `Beenden` action once a student session is active.
- Load overview data dynamically from `api/student_overview.php`.
- Show visible experiments with columns for experiment, condition, assignment, assignment date, and `Angerechnet`.
- Show disabled buttons for closed experiments.
- Let students choose a condition only when the experiment uses `student_choice`.
- Claim/retrieve access through `api/claim.php`.
- Show access fields generically based on API payloads.
- Let students choose one slot through `api/choose_slot.php` when needed.
- Show staff-entered appointment text when available.

## Important Files

- `database/schema.sql`: canonical V2 schema.
- `database/seed.sql`: real course allowlist only; no experiments or access data.
- `database/seed_examples.sql`: self-contained demo/dev data with sample students, representative experiments, access fields, staff-prepared values, access pools, and slots.
- `database/reset.sql`: removes experiment setup and runtime data while preserving `allowed_students` and schema metadata.
- `database/drop_tables.sql`: drops all application tables in dependency order for full teardown/rebuild cycles.
- `database/live_database.sql`: historical live dump from the V1 app. Treat it as migration context only; do not edit it unless the user explicitly asks for migration work.
- `config/config.php`: deployment DB configuration plus `EXPERIMENT_DB_DSN` test override.
- `api/_bootstrap.php`: shared API helpers and domain read helpers.
- `api/student_overview.php`: student overview endpoint.
- `api/claim.php`: claim or retrieve participation access.
- `api/choose_slot.php`: slot choice endpoint.
- `api/manage/dashboard.php`: staff dashboard payload.
- `api/manage/actions.php`: staff write actions.
- `assets/app.js`: student UI logic.
- `manage/manage.js`: staff UI logic.
- `.agents/PROJECT.md`: milestone audit trail.

## Deployment And Configuration

Deploy V2 into an empty database unless doing explicit migration work.

Database deployment settings are currently stored in `config/config.php` by explicit project choice. `EXPERIMENT_DB_DSN` remains available as an optional override, mostly for tests.

## Tests And Local Limitations

Run:

- `php tests/validation_test.php`
- `php tests/text_quality_test.php`
- `php tests/js_regression_test.php`
- `php tests/api_smoke_test.php`

`js_regression_test.php` currently catches management-client regressions that JavaScript syntax checking would miss, including pool-rendering references to grading-only variables.
The API smoke test uses a temporary SQLite database and skips when `pdo_sqlite` is unavailable. When SQLite support is available, it covers student claim/retrieval, slot choice capacity, management setup, allowlist removal guards, participant selection and clearing, condition assignment and clearing, bundled pool import, staff-entered access values, confirmation, appointment retrieval, participation reset, and randomization.

On the current development machine as last observed:

- PHP syntax checks passed.
- `validation_test.php` passed.
- `text_quality_test.php` passed.
- `js_regression_test.php` passed.
- `api_smoke_test.php` passed after enabling `pdo_sqlite` in the active PHP `php.ini`.
- `node --check manage/manage.js` passed.

## Known Deferred Work

- Migration from `database/live_database.sql` into the V2 schema.
- Staff authentication.
- Browser/manual QA against a MySQL-backed local or deployed environment.
- More granular automated tests for management actions.
