# Experiment Assignment App

PHP/MySQL web app for assigning experiment access information to ZHAW students and tracking whether participation is counted for grading.

## Structure

- `index.html`: student UI
- `assets/`: student CSS and JavaScript
- `manage/`: staff UI
- `api/`: student and staff JSON endpoints
- `config/config.php`: deployment database configuration
- `scripts/generate_admin_access_code.php`: one-time administrator code/hash generator
- `database/schema.sql`: V3 schema
- `database/seed.sql`: intentionally empty production seed
- `database/seed_examples.sql`: optional representative demo experiments and access data
- `database/reset.sql`: remove experiment setup/runtime data while preserving groups and students
- `database/reset_all_data.sql`: remove all semester and student data while preserving the schema
- `database/drop_tables.sql`: drop all application tables in dependency order
- `.agents/CONTEXT.md`: current domain decisions
- `.agents/PROJECT.md`: milestone audit trail
- `tests/`: PHP checks

## Database

Import the schema into an empty MySQL database:

```bash
mysql -u USER -p DATABASE < database/schema.sql
mysql -u USER -p DATABASE < database/seed.sql
```

`seed.sql` intentionally contains no semester-specific records. Import course groups and students through the management workflow after deployment. Use `database/seed_examples.sql` only for a throwaway/demo database because it creates representative example groups, students, experiments, conditions, selected participants, staff-prepared values, access pools, and slots. Example students intentionally start without access codes; create and download them from the management roster before testing student login.

Use `database/reset.sql` when you want to remove all experiment configuration and runtime data from a deployment while keeping student groups, students, and their login-code state.

Use `database/reset_all_data.sql` for a semester rollover that should also remove all student groups, students, login-code state, authentication throttles, and audit events.

Use `database/drop_tables.sql` only when you want to remove the full application schema before rebuilding it from `schema.sql`.

## Configuration

Copy `.env.example` to `.env` and configure the database there. Process environment variables take precedence over values loaded from `.env`.

Required MySQL settings are `EXPERIMENT_DB_HOST`, `EXPERIMENT_DB_NAME`, `EXPERIMENT_DB_USER`, and `EXPERIMENT_DB_PASSWORD`. `EXPERIMENT_DB_PORT` defaults to `3306`, and `EXPERIMENT_DB_CHARSET` defaults to `utf8mb4`. `EXPERIMENT_DB_DSN` remains available as an optional complete override for tests, especially the SQLite smoke test.

Generate a new administrator access code and its password hash with:

```bash
php scripts/generate_admin_access_code.php
```

Store the displayed `ADMIN_ACCESS_CODE_HASH` line in the private `.env` file and deliver the plaintext code through an appropriate separate channel. The helper displays the plaintext only once. Session names, idle timeouts, and the secure-cookie setting are also configurable through `.env.example`; production must use HTTPS with `APP_SESSION_SECURE=true`.

The V3 schema foundation includes course groups, student login-code metadata, authentication throttling, experiment schedules/capacities/rewards, group eligibility, undated slots, and audit events. Student and administrator authentication now use these foundations; course-group and experiment-operation behavior is activated by subsequent milestones.

## Student Flow

1. Student enters a `@students.zhaw.ch` email address and their individual access code.
2. The app loads all experiments visible to that student.
3. Closed experiments stay visible but cannot be opened.
4. Open experiments can be claimed once.
5. Existing claims are retrieved through the authenticated session and show the same access information again.
6. Slot-based experiments require one slot choice with capacity checks.
7. Staff-entered appointment text appears in the access information when available.
8. `Angerechnet` is shown after staff confirms the participation.
9. The current authenticated email is shown in the top navbar and the server-side session can be ended with `Beenden`.

Student codes are never stored in browser storage. The server stores password hashes only, throttles repeated login failures, and invalidates an active student session when that student's code version changes.

Generated student codes are exactly five lowercase alphanumeric characters with at least one letter and one digit. A management user may manually set a longer mixed-case alphanumeric code that meets the same minimum letter-and-digit requirement.

## Staff Flow

The staff UI is at `manage/index.html`.

It requires the administrator access code configured as `ADMIN_ACCESS_CODE_HASH`. Management data endpoints require the administrator session, and write endpoints additionally require a session-bound CSRF token.

It supports:

- adding allowed students
- creating and editing course groups with an optional point maximum during setup
- assigning exactly one course group to every student
- repeatedly importing grouped rosters from `email;group` CSV, including safe membership updates and automatic creation of new course labels
- filtering the roster by course and email
- generating codes only for students whose code is missing and downloading their plaintext values in a one-time CSV response
- manually setting or rotating an individual student code at any time
- opening the dedicated global allowlist view from the editable student-count badge
- viewing and removing allowed students without participations
- creating and renaming experiments
- deleting setup experiments
- opening and closing experiments
- adding conditions
- deleting unused conditions
- selecting experiment participants from the global allowlist by all, seeded random subset, or manual email search
- clearing an experiment-specific participant selection before participations exist
- assigning selected participants to conditions manually or by seeded percentage randomization
- clearing condition assignments before participations exist
- defining access fields
- deleting access fields
- preparing bundled access pool rows through a guided CSV modal
- clearing unassigned access pool data
- preparing student-specific values for access fields sourced from staff entry before access is revealed
- viewing and deleting unassigned access pool rows
- manually assigning students
- viewing and removing experiment-specific eligibilities
- deterministic randomization across conditions
- creating time slots with capacity
- showing time-slot setup only for experiments marked as requiring time slots
- viewing slot choices by slot
- deleting unused time slots
- opening experiment-specific editing by clicking an experiment in the overview
- opening experiment-specific grading from the overview
- opening the Reports view from the navbar
- viewing one report row per globally allowed student with `Kürzel`, course, and one `0`/`1` approval column per experiment
- sorting report columns, filtering by `Kürzel` or course, and downloading the displayed report as CSV
- showing the access reveal time and compact access values in grading, with link fields rendered as labeled buttons
- filtering and sorting the grading table by each data column
- building a checked participation selection in the grading modal and applying bulk grading actions
- showing a navbar status indicator while backend requests are running
- listing registered students who have not opened access yet in the grading view
- setting appointment text
- resetting participations
- toggling `Angerechnet`
- navigating overview, experiment setup, and grading through the top workflow strip
- returning to the experiment overview through the navbar brand

Access fields that already back assigned runtime values cannot be deleted or structurally changed. Existing slot capacity cannot be reduced below the number of submitted slot choices.

For experiments with configured condition rows, access-pool imports are condition-scoped. Choose the target condition in the pool modal; the CSV for that condition includes both experiment-wide pool fields and fields specific to that condition. The experiment-wide pool option is used while no conditions exist.

## Tests

```bash
php tests/config_test.php
php tests/schema_test.php
php tests/validation_test.php
php tests/text_quality_test.php
php tests/js_regression_test.php
php tests/api_smoke_test.php
```

`js_regression_test.php` catches focused management-client regressions that are not covered by JavaScript syntax checking alone.
`api_smoke_test.php` uses a temporary SQLite database and skips when `pdo_sqlite` is unavailable.
When SQLite support is available, it covers student and administrator authentication, session-bound identity, CSRF enforcement, grouped roster upserts, course guards, one-time generated-code CSV delivery, hash-only persistence, manual code rotation, login-code session revocation, the student claim/retrieval flow, slot capacity enforcement, management setup actions, allowlist removal guards, participant selection and clearing, condition assignment and clearing, access-pool import, staff-entered access values, confirmation, bulk grading operations, appointment retrieval, reset, randomization, and the management approval report endpoint.
The report coverage includes the distinction between opened access and confirmed `Angerechnet` approval.
