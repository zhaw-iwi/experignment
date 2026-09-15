# Production Cutover

This is the operational checklist for replacing the previous-semester installation with a clean V4 database or upgrading an existing V3 installation through the additive student-chest migration. Old dumps are historical/rollback material only.

## 1. Prepare And Preserve Rollback

1. Schedule a short maintenance window and prevent roster or grading changes during it.
2. Export the current production database and archive the current deployed files outside the public web root.
3. Store that backup securely because it contains student and experiment-access data.
4. Record the currently deployed Git commit and database name so the old installation can be restored if needed.
5. Rotate the database password that appeared in repository history. Do not reuse it for V4.

Although previous-semester records are not needed operationally, keeping one restricted rollback backup until V4 acceptance is complete is safer than making the cutover immediately irreversible.

## 2. Provision Production Configuration

1. Prefer a new empty database and a new dedicated application account. This keeps rollback as a configuration switch rather than an in-place reconstruction.
2. Give the runtime account only the database permissions the application needs: `SELECT`, `INSERT`, `UPDATE`, and `DELETE`. Use a separate administrative/phpMyAdmin account to create the schema.
3. Copy `.env.example` to the private, ignored root `.env` and replace every placeholder.
4. Set `APP_TIMEZONE=Europe/Zurich` unless the semester should use a different explicit PHP timezone.
5. Run `php scripts/generate_admin_access_code.php` in a trusted command-line environment.
6. Put only the generated `ADMIN_ACCESS_CODE_HASH=...` line in `.env`. Store the one-time plaintext administrator code through a separate secure channel.
7. Set `APP_SESSION_SECURE=true` and serve the site exclusively over HTTPS.
8. Do not upload a live dump, student roster, generated-code CSV, or any other file under `temp/` to the web root.

The tracked Apache rules deny HTTP access to `.env`, `config/`, `database/`, `scripts/`, `tests/`, `temp/`, `.agents/`, and `.git/`. Confirm equivalent protection manually if the host does not honor `.htaccess`.

## 3. Build The Clean Database In phpMyAdmin

Recommended path:

1. Select the newly created empty database in phpMyAdmin.
2. Open **Import** and import `database/schema.sql`.
3. Confirm that the import completed without warnings or failed statements.
4. Optionally import `database/seed.sql`; it is intentionally empty and inserts no groups, students, experiments, or runtime records.
5. Do not import `database/seed_examples.sql` in production.
6. Do not import any `e93ud_*.sql` or `live_database*.sql` dump into V4.

Fallback when a new database cannot be provisioned:

1. Confirm the rollback export from section 1 can be opened and is stored outside the host.
2. Put the application into maintenance mode.
3. Import `database/drop_tables.sql` into the old application database. This permanently drops the current application tables.
4. Import `database/schema.sql`, followed optionally by the empty `database/seed.sql`.

`database/reset_all_data.sql` is for clearing an existing V4 schema. It is not a V2-to-V4 migration and should not replace the drop/rebuild sequence during this cutover.

## 4. Run The Deployment Preflight

If the host provides a console, run from the deployed application directory with the production `.env` active:

```bash
php scripts/deployment_preflight.php --expect-empty
```

If the host does not provide a console:

1. Temporarily set `PREFLIGHT_ENABLED=true` in the private production `.env`.
2. Open `https://YOUR-APP/preflight/` in a browser.
3. Enter the administrator access code. It is submitted only by `POST` and is not included in the URL or preflight output.
4. Keep **Require all semester and runtime tables to be empty** selected for the initial clean deployment.
5. Run the check and require zero errors.
6. Immediately restore `PREFLIGHT_ENABLED=false` and confirm `/preflight/` returns `404`.

The browser endpoint is disabled by default, refuses administrator-code submission over non-HTTPS connections except on localhost, uses CSRF protection and secure session cookies, limits repeated failures per session, sends no-cache/no-index headers, and invokes the same check implementation as the command-line tool.

Expected result: zero errors. A root-account warning must be resolved by switching to a dedicated runtime account. Both interfaces check:

- a valid administrator password hash, positive session timeouts, secure cookies, and application timezone;
- MySQL/MariaDB connectivity, schema version 4, all 21 required tables and key columns, and InnoDB storage;
- an empty semester/runtime state;
- runtime `SELECT`/`INSERT`/`UPDATE`/`DELETE` permissions using a transaction that is rolled back.

Also verify these HTTP boundaries before importing student data:

- `api/bootstrap.php` returns a version-4 JSON response;
- `manage/index.html` shows the administrator login;
- an unauthenticated request to `api/manage/dashboard.php` is rejected;
- direct browser access to `.env`, `config/`, `database/`, `scripts/`, and `tests/` is rejected;
- the production certificate is valid and session cookies are marked `Secure`, `HttpOnly`, and `SameSite=Strict`.

## 5. Activate The Semester

1. Sign in to the management UI with the new administrator code.
2. Create courses and set the point target for each, or let roster import create the course labels and then complete their targets.
3. Import the roster using `email;group` headers. Re-importing is an upsert; omitted students are retained.
4. Compare the displayed per-course and total student counts with the source roster.
5. Select **Fehlende Codes erstellen und CSV laden** once. Store the downloaded CSV securely and distribute each code only to its matching email address.
6. Confirm that running generation again reports that no codes are missing; existing plaintext codes cannot be re-exported.
7. Create experiments and configure public description, private notes, course audience, individual eligibility, conditions, access information, availability, participant maximum, reward, and slots as applicable.
8. Resolve every blocking ready-to-open indicator. Treat schedule and participant-limit warnings as an explicit operational choice.
9. Open experiments only after reviewing the effective audience count and access-data/slot capacity.

## 6. Acceptance And Sign-Off

Use a dedicated test student in each relevant course and verify:

- wrong and correct student codes behave as expected;
- each course sees only its intended experiments;
- future, expired, manually closed, and full experiments cannot be claimed;
- a claim reveals the intended bundled/shared/staff-entered access values;
- dated and explicit `Ohne Termin` slots can be selected and capacity is enforced;
- administrator confirmation awards the experiment's full current reward even when the course target is reached or exceeded;
- changing an experiment reward immediately updates totals for its existing confirmed participations;
- Reports show course, dynamically calculated earned points, point target, and per-experiment `0`/`1` confirmation values;
- the student overview shows earned points, the course target, and the true percentage; above-target percentages may exceed 100% while the visual bar remains contained;
- one future confirmation creates one closed student chest, several confirmations while logged out create the same number of FIFO chests, and opening them does not change the already-effective points total;
- full-motion and reduced-motion students receive the same credited-participation message, and a restored session shows the pending count without opening a modal automatically;
- the audit card records the successful login, claim, slot, provisioning, and management actions without plaintext access codes;
- logout works for both roles and a manually rotated student code revokes the old student session.

Remove or close temporary acceptance experiments after testing. Retain the old rollback backup until the administrator has signed off the roster counts, one-time code delivery, audience rules, a real claim, grading totals, and audit visibility.

### Earlier Student Points Visualization Release

Database migration required: **no**. This update continues using the V3 `student_groups.max_credits`, `allowed_students.group_id`, `experiments.reward_credits`, and `participations.confirmed_at` columns. The nullable reward-snapshot column remains for compatibility but is not used for totals.

Before deploying the application files, take a verified live backup and run these read-only checks in phpMyAdmin:

```sql
SELECT MAX(version_number) AS schema_version
FROM schema_versions;

SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, NUMERIC_PRECISION, NUMERIC_SCALE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND (
      (TABLE_NAME = 'student_groups' AND COLUMN_NAME = 'max_credits')
      OR (TABLE_NAME = 'allowed_students' AND COLUMN_NAME = 'group_id')
      OR (TABLE_NAME = 'experiments' AND COLUMN_NAME = 'reward_credits')
      OR (TABLE_NAME = 'participations' AND COLUMN_NAME IN ('confirmed_at', 'reward_credits_snapshot'))
  )
ORDER BY TABLE_NAME, COLUMN_NAME;
```

For that earlier points-only deployment, schema version `3` and all five listed columns were required. The current application now requires the V4 migration described below; do not use this historical points-only check as the current deployment gate.

After deploying files, verify one test student below target and one above target without placing student credentials in screenshots or logs. Confirm the report totals match the student cards. If acceptance fails, restore the previous application files; this feature requires no database rollback.

### Student Chest Persistence V4 Migration

Database migration required: **yes** when upgrading an existing V3 installation. The complete V4 application creates chest events only for future confirmations and deliberately does not backfill historical confirmations.

Use this migration-first order for the live in-place upgrade:

1. Put the normal application-file backup and a verified full database backup in place.
2. In phpMyAdmin, run the read-only V3 precondition queries at the top of `database/migrations/2026-09-15-student-chests/migration.sql`; require schema version `3` and confirm that `student_chest_events` does not already exist.
3. Execute that exact `migration.sql` file through phpMyAdmin.
4. Run the migration's verification queries and require schema version `4`, the documented columns and indexes, and `chest_event_count = 0`.
5. Deploy the matching V4 application files immediately after the migration. Do not deploy the V4 confirmation or chest files before the table exists.
6. Run `php scripts/deployment_preflight.php` against the live configuration without `--expect-empty` for an active installation. If the host has no console, use the protected browser preflight as described in section 4 and disable it again immediately.
7. Using only an explicitly designated disposable/test participation, confirm it once and verify exactly one pending chest for that student.
8. Sign in as that student, open the chest, and verify the current points overview is still correct; chest opening must not change the points that became effective at confirmation.
9. Repeat the same confirmation request and verify that no second chest appears.
10. Remove or reset only the explicitly designated test data using the normal management reset path; do not run a broad reset against the live semester.

Applying the additive migration while V3 is still serving is safe because V3 ignores the extra table. Deploying V4 application files before the migration is not safe because confirmation and chest endpoints require the table. Keep the interval between steps 3 and 5 short and prevent grading changes during the maintenance window.

The migration uses MySQL DDL, which auto-commits. It intentionally fails on a rerun rather than silently accepting an incompatible table. If it fails partway through, inspect the error and restore the verified pre-migration backup before retrying.

If application rollback is needed after chest events exist, restore the previous application files and leave the V4 table and schema-version record intact. V3 ignores the additional table, and retaining it preserves student history. Do not drop chest rows merely to report schema version 3. Restore the database backup only when losing every roster, participation, confirmation, and chest change made after that backup is explicitly acceptable.

## 7. Rollback

With the recommended separate-database approach, put the site in maintenance mode, restore the previous deployed files and previous private configuration, and point them back to the archived database. With an in-place rebuild or failed V3-to-V4 migration, restore the database export before restoring the previous files. After a successful V4 migration that has begun receiving chest events, prefer an application-only rollback that leaves the additive table intact. Never deploy application files that require V4 against a V3 schema.
