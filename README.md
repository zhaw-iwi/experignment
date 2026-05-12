# Experiment Assignment App

PHP/MySQL web app for assigning experiment access information to ZHAW students and tracking whether participation is counted for grading.

## Structure

- `index.html`: student UI
- `assets/`: student CSS and JavaScript
- `manage/`: staff UI
- `api/`: student and staff JSON endpoints
- `config/config.php`: deployment database configuration
- `database/schema.sql`: V2 schema
- `database/migrate_staff_values.sql`: V2 migration for staff-prepared access values
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

Existing V2 databases need `database/migrate_staff_values.sql` once before deploying versions with staff-prepared access values.

## Configuration

Database deployment settings are stored in `config/config.php`. `EXPERIMENT_DB_DSN` remains available as an optional override for tests, especially the SQLite smoke test.

## Student Flow

1. Student enters a `@students.zhaw.ch` email address.
2. The app loads all experiments visible to that student.
3. Closed experiments stay visible but cannot be opened.
4. Open experiments can be claimed once.
5. Existing claims are retrieved by email and show the same access information again.
6. Slot-based experiments require one slot choice with capacity checks.
7. Staff-entered appointment text appears in the access information when available.
8. `Angerechnet` is shown after staff confirms the participation.
9. The current email session is shown in the top navbar and can be ended with `Beenden`.

## Staff Flow

The staff UI is at `manage/index.html`.

It supports:

- adding allowed students
- bulk-importing allowed students
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
- opening experiment-specific editing from the overview
- opening experiment-specific grading from the overview
- showing the access reveal time and prepared staff-entry values in grading while limiting columns to configured experiment features
- setting appointment text
- resetting participations
- toggling `Angerechnet`
- navigating overview, experiment setup, and grading through the top workflow strip
- returning to the experiment overview through the navbar brand

Access fields that already back assigned runtime values cannot be deleted or structurally changed. Existing slot capacity cannot be reduced below the number of submitted slot choices.

Staff authentication is intentionally not implemented yet; the page relies on a hidden URL.

## Tests

```bash
php tests/validation_test.php
php tests/text_quality_test.php
php tests/api_smoke_test.php
```

`api_smoke_test.php` uses a temporary SQLite database and skips when `pdo_sqlite` is unavailable.
When SQLite support is available, it covers the student claim/retrieval flow, slot capacity enforcement, management setup actions, allowlist removal guards, participant selection and clearing, condition assignment and clearing, access-pool import, staff-entered access values, confirmation, appointment retrieval, reset, and randomization.
