# Experiment Assignment App

PHP/MySQL web app for assigning experiment access information to ZHAW students and tracking whether participation is counted for grading.

## Structure

- `index.html`: student UI
- `assets/`: student CSS and JavaScript
- `manage/`: staff UI
- `api/`: student and staff JSON endpoints
- `config/config.php`: database configuration from environment variables
- `database/schema.sql`: V2 schema
- `database/seed.sql`: real course allowlist only
- `database/seed_examples.sql`: optional sample V2 experiments and access data
- `database/reset.sql`: reset runtime V2 state
- `.agents/CONTEXT.md`: current domain decisions
- `.agents/PROJECT.md`: milestone audit trail
- `tests/`: PHP checks

## Database

Import the schema into an empty MySQL database:

```bash
mysql -u USER -p DATABASE < database/schema.sql
mysql -u USER -p DATABASE < database/seed.sql
```

`seed.sql` contains only the current real `allowed_students` list. Use `database/seed_examples.sql` only for a throwaway/demo database because it creates example experiments, fields, access pools, and slots.

## Configuration

Set these environment variables on the web host:

- `EXPERIMENT_DB_HOST`
- `EXPERIMENT_DB_PORT`
- `EXPERIMENT_DB_NAME`
- `EXPERIMENT_DB_USER`
- `EXPERIMENT_DB_PASSWORD`
- `EXPERIMENT_DB_CHARSET` optional, default `utf8mb4`
- `EXPERIMENT_DB_DSN` optional override, useful for tests

No production credentials are stored in the repository.

## Student Flow

1. Student enters a `@students.zhaw.ch` email address.
2. The app loads all experiments visible to that student.
3. Closed experiments stay visible but cannot be opened.
4. Open experiments can be claimed once.
5. Existing claims are retrieved by email and show the same access information again.
6. Slot-based experiments require one slot choice with capacity checks.
7. Staff-entered appointment text appears in the access information when available.
8. `Angerechnet` is shown after staff confirms the participation.

## Staff Flow

The staff UI is at `manage/index.html`.

It supports:

- adding allowed students
- bulk-importing allowed students
- creating and renaming experiments
- deleting setup experiments
- opening and closing experiments
- adding conditions
- deleting unused conditions
- defining access fields
- deleting access fields
- importing bundled access pool rows
- viewing and deleting unassigned access pool rows
- manually assigning students
- viewing and removing experiment-specific eligibilities
- deterministic randomization across conditions
- creating time slots with capacity
- viewing slot choices by slot
- deleting unused time slots
- setting appointment text
- resetting participations
- toggling `Angerechnet`

Staff authentication is intentionally not implemented yet; the page relies on a hidden URL.

## Tests

```bash
php tests/validation_test.php
php tests/text_quality_test.php
php tests/api_smoke_test.php
```

`api_smoke_test.php` uses a temporary SQLite database and skips when `pdo_sqlite` is unavailable.
