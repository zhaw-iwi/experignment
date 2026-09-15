# Experiment Assignment App

PHP/MySQL web app for assigning experiment access information to ZHAW students and tracking whether participation is counted for grading.

## Structure

- `index.html`: student UI
- `assets/`: student CSS and JavaScript
- `manage/`: staff UI
- `api/`: student and staff JSON endpoints
- `config/config.php`: deployment database configuration
- `.env.test.example`: tracked template for an ignored local database test configuration
- `scripts/generate_admin_access_code.php`: one-time administrator code/hash generator
- `scripts/deployment_preflight.php`: production configuration and clean-database verifier
- `preflight/index.php`: temporarily enabled, administrator-protected browser preflight
- `docs/PRODUCTION_CUTOVER.md`: phpMyAdmin deployment and semester activation checklist
- `database/schema.sql`: current V4 schema
- `database/migrations/2026-09-15-student-chests/migration.sql`: additive V3-to-V4 live migration
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

To upgrade an existing V3 installation in place, first take a verified backup and then import `database/migrations/2026-09-15-student-chests/migration.sql` through phpMyAdmin. The migration adds the durable `student_chest_events` store and advances the schema to V4. It deliberately creates zero chest rows for existing confirmations; only approvals made after the completed feature is deployed will earn chests. Run the commented precondition and verification queries in the migration file exactly as documented.

Use `database/reset.sql` when you want to remove all experiment configuration and runtime data from a deployment while keeping student groups, students, and their login-code state.

Use `database/reset_all_data.sql` for a semester rollover that should also remove all student groups, students, login-code state, authentication throttles, and audit events.

Use `database/drop_tables.sql` only when you want to remove the full application schema before rebuilding it from `schema.sql`.

For the production sequence, rollback precautions, and acceptance checks, follow [docs/PRODUCTION_CUTOVER.md](docs/PRODUCTION_CUTOVER.md). A separate new database is preferred over modifying the previous-semester database in place.

## Configuration

Copy `.env.example` to `.env` and configure the database there. Process environment variables take precedence over values loaded from `.env`.

Required MySQL settings are `EXPERIMENT_DB_HOST`, `EXPERIMENT_DB_NAME`, `EXPERIMENT_DB_USER`, and `EXPERIMENT_DB_PASSWORD`. `EXPERIMENT_DB_PORT` defaults to `3306`, `EXPERIMENT_DB_CHARSET` defaults to `utf8mb4`, and `APP_TIMEZONE` defaults to `Europe/Zurich`. `EXPERIMENT_DB_DSN` remains available as an optional complete override for tests, especially the SQLite smoke test.

Generate a new administrator access code and its password hash with:

```bash
php scripts/generate_admin_access_code.php
```

Store the displayed `ADMIN_ACCESS_CODE_HASH` line in the private `.env` file and deliver the plaintext code through an appropriate separate channel. The helper displays the plaintext only once. Session names, idle timeouts, and the secure-cookie setting are also configurable through `.env.example`; production must use HTTPS with `APP_SESSION_SECURE=true`.

After importing the clean production schema and configuring `.env`, run:

```bash
php scripts/deployment_preflight.php --expect-empty
```

The preflight verifies production authentication/session settings, database connectivity, schema version and key columns, InnoDB/UTF-8 configuration, an empty semester state, and runtime database permissions. Its permission probe is fully rolled back.

When the host has no console, temporarily set `PREFLIGHT_ENABLED=true` in the production `.env`, open `https://YOUR-APP/preflight/`, enter the administrator access code, and keep the empty-database check selected for a fresh deployment. After receiving zero errors, immediately restore `PREFLIGHT_ENABLED=false` and confirm the URL returns `404`. The page refuses non-HTTPS credential submission except from localhost and uses the same checks as the CLI command.

For local database QA, copy `.env.test.example` to the ignored `.env.test`, fill in credentials for a dedicated disposable database, and explicitly select it in PowerShell:

```powershell
Copy-Item .env.test.example .env.test
$env:EXPERIMENT_ENV_FILE = '.env.test'
```

Set `EXPERIMENT_TEST_DATABASE_RESET_ALLOWED=true` only if that database may be dropped and rebuilt during the remaining integration checks. The application continues to load `.env` by default; `.env.test` is used only when `EXPERIMENT_ENV_FILE` explicitly selects it.

The V4 runtime schema adds durable student chest events to the V3 course, authentication, experiment, confirmation, and audit foundations. The nullable participation reward-snapshot column remains only for schema compatibility and is not used to calculate points. The first chest milestone introduces persistence only; chest creation and student presentation are delivered by the subsequent milestones in `.agents/PLAN_CHESTS.md`.

## Student Flow

1. Student enters a `@students.zhaw.ch` email address and their individual access code.
2. The app loads experiments whose course audience and individual eligibility rules include that student.
3. Manually closed, not-yet-open, expired, and full experiments stay visible but cannot be claimed.
4. Available experiments can be claimed once, even after the student has reached the course point target.
5. Existing claims are retrieved through the authenticated session and show the same access information again.
6. Slot-based experiments require one slot choice with capacity checks.
7. Staff-entered appointment text appears in the access information when available.
8. `Angerechnet` and the experiment's current reward are shown after staff confirms the participation. Every confirmed experiment contributes its full current reward, including above the course target; editing a reward recalculates existing confirmed totals.
9. A points card above the experiment table shows the student's course, earned points, course target, percentage, and an accessible progress bar. Percentages can exceed 100%, while the bar stops visually at 100%. Missing and zero targets omit the percentage and determinate bar. The navbar shows the authenticated email, and the server-side session can be ended with `Beenden`.

Student codes are never stored in browser storage. The server stores password hashes only, throttles repeated login failures, and invalidates an active student session when that student's code version changes.

Generated student codes are exactly five lowercase alphanumeric characters with at least one letter and one digit. A management user may manually set a longer mixed-case alphanumeric code that meets the same minimum letter-and-digit requirement.

## Staff Flow

The staff UI is at `manage/index.html`.

It requires the administrator access code configured as `ADMIN_ACCESS_CODE_HASH`. Management data endpoints require the administrator session, and write endpoints additionally require a session-bound CSRF token.

It supports:

- adding allowed students
- creating and editing course groups with an optional point target during setup
- assigning exactly one course group to every student
- repeatedly importing grouped rosters from `email;group` CSV, including safe membership updates and automatic creation of new course labels
- filtering the roster by course and email
- generating codes only for students whose code is missing and downloading their plaintext values in a one-time CSV response
- manually setting or rotating an individual student code at any time
- opening the dedicated global allowlist view from the editable student-count badge
- viewing and removing allowed students without participations
- creating and renaming experiments with public descriptions and private administrator notes
- targeting an experiment to all courses or one or more selected courses, intersected with its individual eligibility mode
- setting optional opening/closing datetimes, an optional participant maximum, and a numeric reward
- checking ready-to-open indicators for audience, course point targets, student codes, conditions, access data, and slots
- blocking manual opening until every required readiness check is complete while keeping schedule/capacity recommendations advisory
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
- creating dated time slots or explicit `Ohne Termin` alternatives with capacity
- showing time-slot setup only for experiments marked as requiring time slots
- viewing slot choices by slot
- deleting unused time slots
- opening experiment-specific editing by clicking an experiment in the overview
- opening experiment-specific grading from the overview
- opening the Reports view from the navbar
- viewing one report row per globally allowed student with `Kürzel`, course, earned points, course target, and one `0`/`1` approval column per experiment
- sorting report columns, filtering by `Kürzel` or course, and downloading the displayed report as CSV
- showing the access reveal time and compact access values in grading, with link fields rendered as labeled buttons
- filtering and sorting the grading table by each data column
- building a checked participation selection in the grading modal and applying bulk grading actions
- showing a navbar status indicator while backend requests are running
- listing registered students who have not opened access yet in the grading view
- setting appointment text
- resetting participations
- toggling `Angerechnet`
- calculating totals from the current rewards of all `Angerechnet` experiments and clearing any legacy snapshot when confirmation is removed
- reviewing the 100 most recent authentication, participation, provisioning, and management audit events
- navigating overview, experiment setup, and grading through the top workflow strip
- returning to the experiment overview through the navbar brand

Access fields that already back assigned runtime values cannot be deleted or structurally changed. Existing slot capacity cannot be reduced below the number of submitted slot choices. A student's course cannot be changed after their first experiment participation, and experiment course audiences cannot exclude existing participants. Course targets may be changed above or below earned totals without changing the earned points.

For experiments with configured condition rows, access-pool imports are condition-scoped. Choose the target condition in the pool modal; the CSV for that condition includes both experiment-wide pool fields and fields specific to that condition. The experiment-wide pool option is used while no conditions exist.

## Tests

```bash
php tests/config_test.php
php tests/schema_test.php
php tests/validation_test.php
php tests/text_quality_test.php
php tests/js_regression_test.php
php tests/api_smoke_test.php
node tests/points_ui_test.js
npm run test:browser
```

`points_ui_test.js` covers decimal point formatting, target states, visible percentages, visual-width clamping, and reward display before and after confirmation.
`js_regression_test.php` catches focused management- and student-client regressions that are not covered by JavaScript syntax checking alone, including progress-bar accessibility and visual containment guards.
`api_smoke_test.php` uses a temporary SQLite database and skips when `pdo_sqlite` is unavailable.
When SQLite support is available, it covers student and administrator authentication, session-bound identity, CSRF enforcement, grouped roster upserts, course guards, one-time generated-code CSV delivery, hash-only persistence, manual code rotation, login-code session revocation, course-targeted experiment visibility, opening schedules, participant maxima, ready-to-open validation, private notes, explicit undated slots, the student claim/retrieval flow, slot capacity enforcement, management setup actions, allowlist removal guards, participant selection and clearing, condition assignment and clearing, access-pool import, staff-entered access values, dynamic uncapped rewards, course-specific targets and percentages, confirmation, bulk grading operations, appointment retrieval, reset, randomization, audit events, and the management approval report endpoint.
The report coverage includes the distinction between opened access and confirmed `Angerechnet` approval as well as dynamically calculated totals and course targets.
The smoke test also verifies browser-preflight enablement, CSRF and administrator-code protection, direct-web rejection of the CLI script, and execution of the shared check implementation.

Install browser-test dependencies once with `npm install` and `npx playwright install chromium`. `npm run test:browser` creates a temporary SQLite fixture and a temporary explicit environment file, removes inherited application-database settings, serves the app locally, replaces CDN requests with the pinned local Bootstrap package, runs desktop/mobile Chromium assertions, and removes its database, environment, session, server, and default screenshot artifacts afterward. It never selects the root `.env`. Set `POINTS_TEST_ARTIFACT_DIR` to an external temporary directory only when screenshots or failure traces need manual review.
