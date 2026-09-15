# Production Cutover

This is the operational checklist for replacing the previous-semester installation with a clean V3 database. The old dump is historical/rollback material only; it is not an input to the V3 database.

## 1. Prepare And Preserve Rollback

1. Schedule a short maintenance window and prevent roster or grading changes during it.
2. Export the current production database and archive the current deployed files outside the public web root.
3. Store that backup securely because it contains student and experiment-access data.
4. Record the currently deployed Git commit and database name so the old installation can be restored if needed.
5. Rotate the database password that appeared in repository history. Do not reuse it for V3.

Although previous-semester records are not needed operationally, keeping one restricted rollback backup until V3 acceptance is complete is safer than making the cutover immediately irreversible.

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
6. Do not import any `e93ud_*.sql` or `live_database*.sql` dump into V3.

Fallback when a new database cannot be provisioned:

1. Confirm the rollback export from section 1 can be opened and is stored outside the host.
2. Put the application into maintenance mode.
3. Import `database/drop_tables.sql` into the old application database. This permanently drops both V2 and V3 application tables.
4. Import `database/schema.sql`, followed optionally by the empty `database/seed.sql`.

`database/reset_all_data.sql` is for clearing an existing V3 schema. It is not a V2-to-V3 migration and should not replace the drop/rebuild sequence during this cutover.

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
- MySQL/MariaDB connectivity, schema version 3, all 20 required tables and key columns, and InnoDB storage;
- an empty semester/runtime state;
- runtime `SELECT`/`INSERT`/`UPDATE`/`DELETE` permissions using a transaction that is rolled back.

Also verify these HTTP boundaries before importing student data:

- `api/bootstrap.php` returns a version-3 JSON response;
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
- the audit card records the successful login, claim, slot, provisioning, and management actions without plaintext access codes;
- logout works for both roles and a manually rotated student code revokes the old student session.

Remove or close temporary acceptance experiments after testing. Retain the old rollback backup until the administrator has signed off the roster counts, one-time code delivery, audience rules, a real claim, grading totals, and audit visibility.

### Student Points Visualization Release

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

Require schema version `3` and all five listed columns. If those checks fail, stop and plan the V3 upgrade separately. Do not apply a database change as part of this feature.

After deploying files, verify one test student below target and one above target without placing student credentials in screenshots or logs. Confirm the report totals match the student cards. If acceptance fails, restore the previous application files; this feature requires no database rollback.

## 7. Rollback

With the recommended separate-database approach, put the site in maintenance mode, restore the previous deployed files and previous private configuration, and point them back to the archived database. With an in-place rebuild, restore the database export before restoring the previous files. Never mix V2 application files with the V3 schema.
