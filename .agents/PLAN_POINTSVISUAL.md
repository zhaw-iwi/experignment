# Student Points And Progress Plan

Date: 2026-09-15

Status: In progress — Milestones 0-2 complete; Milestone 3 next

## 1. Objective

Make experiment rewards and course progress clear to students while changing the grading calculation from capped reward snapshots to an uncapped, dynamically computed total.

The completed behavior must:

- Keep one course assignment per student.
- Allow different courses to have different point targets.
- Keep one course-independent reward value per experiment.
- Count an experiment's current reward when that student's participation is `Angerechnet`.
- Recalculate historical totals when an experiment reward changes.
- Allow earned points and the displayed percentage to exceed the course target and 100%.
- Cap only the visual width of the progress bar at 100%.
- Continue to show fractional rewards accurately to two decimal places.

## 2. Confirmed Product Decisions

### 2.1 Courses And Students

- Multiple courses can coexist in one installation.
- Each student belongs to exactly one course.
- Each course has its own point target.
- The existing `student_groups` and `allowed_students.group_id` model remains the course-membership model.

### 2.2 Experiments And Rewards

- An experiment has one reward that is the same for students in every course.
- The existing `experiments.reward_credits DECIMAL(8,2)` remains the source of that reward.
- Only confirmed participations contribute to earned points.
- Changing an experiment reward changes the computed totals of every student with a confirmed participation in that experiment.

### 2.3 Course Target

- The course value is a target, not a hard cap.
- A student may continue participating after reaching the target.
- A student may earn more points than the target.
- The textual percentage may exceed 100%.
- The progress bar itself must not render beyond 100% width.

### 2.4 Decimal Handling

- Persisted course targets and experiment rewards use fixed-precision `DECIMAL(8,2)` values.
- SQL performs the authoritative sum.
- API values are rounded to two decimal places.
- The UI omits unnecessary trailing zeroes while preserving meaningful fractional values such as `1.4` and `0.25`.

## 3. Current Implementation

The current V3 application already provides:

- `student_groups.max_credits` for the course-specific value.
- `allowed_students.group_id` for one course per student.
- `experiments.reward_credits` for the course-independent experiment reward.
- A `Punkte` column in the student experiment table.
- An absolute student summary such as `3 von 8 Punkten erreicht`.
- Course and point totals in the management report.

The current behavior that must change is:

- Confirmation stores a capped value in `participations.reward_credits_snapshot`.
- A reward that crosses the course value is partially credited.
- Rewards confirmed after the course value is reached are credited as zero.
- Student, dashboard, and report totals sum the stored snapshots.
- Lowering a course value below already-credited points is blocked.
- Student progress has no explicit percentage or progress bar.
- User-facing text describes the course value as a maximum instead of a target.

## 4. Database And Live-System Constraint

### 4.1 Planned Database Impact

No database schema or data migration is required for this feature.

The implementation will:

- Continue using `student_groups.max_credits` as the persisted course target for compatibility.
- Continue using `experiments.reward_credits` as the current reward.
- Leave `participations.reward_credits_snapshot` in the schema as a legacy nullable column.
- Stop using `reward_credits_snapshot` in authoritative runtime calculations.
- Stop populating a capped snapshot when a participation is confirmed.
- Ignore existing snapshot values without rewriting live rows.

Keeping the legacy column avoids an unnecessary and potentially destructive live-schema change. It can be removed in a separately approved cleanup migration later.

### 4.2 Migration Gate

If implementation reveals that any schema change is necessary:

1. Stop before changing `database/schema.sql` or relying on the new schema.
2. Document why the existing V3 schema is insufficient.
3. Create `database/migrations/2026-09-15-points-visual/migration.sql`.
4. Make the migration suitable for manual execution through phpMyAdmin.
5. Include explicit preconditions, post-migration verification queries, backup guidance, and rollback limitations.
6. Test the migration against a disposable MySQL database containing a copy of the relevant V3 structures and representative data.
7. Ask the repository owner to approve and execute the migration on the live server.

Application code requiring that migration must not be deployed before the live migration succeeds.

### 4.3 Live Schema Verification

Before deployment, verify read-only that the live database has:

- Schema version 3.
- `student_groups.max_credits`.
- `allowed_students.group_id`.
- `experiments.reward_credits`.
- `participations.confirmed_at`.

If the live database is not compatible with V3, stop and plan that upgrade separately. Do not silently combine a V2-to-V3 database migration with this feature.

## 5. Milestone 0: Green Baseline

### Goal

Start feature work from a reproducible green test baseline.

### Deliverables

- Make `tests/config_test.php` accept LF and CRLF environment-template line endings.
- Confirm `.env` and `.env.test` remain ignored.
- Confirm tests never print environment secrets.
- Record the baseline in `.agents/PROJECT.md` before requesting a commit.

### Verification

- PHP syntax check for every PHP file.
- `node --check assets/app.js`.
- `node --check manage/manage.js`.
- `php tests/config_test.php`.
- `php tests/schema_test.php`.
- `php tests/validation_test.php`.
- `php tests/text_quality_test.php`.
- `php tests/js_regression_test.php`.
- `php tests/api_smoke_test.php`.

### Acceptance Criteria

- All checks pass on the current Windows checkout.
- No application behavior or database state changes.
- No tracked or logged secret is introduced.

## 6. Milestone 1: Dynamic Uncapped Reward Semantics

### Goal

Make all authoritative point totals use current experiment rewards for confirmed participations, with the course value treated only as a target.

### Implementation Areas

- `api/_bootstrap.php`
  - Replace snapshot summation in `student_credit_summary()` with a join from confirmed participations to current experiment rewards.
  - Add a server-calculated percentage when a positive course target exists.
  - Keep `remaining` as points still needed to reach the target, floored at zero.
  - Return the current experiment reward as `creditedReward` for confirmed participation payloads.
- `api/manage/actions.php`
  - Remove remaining-course-allowance and partial-credit calculations.
  - Confirmation sets `confirmed_at` without creating an authoritative reward snapshot.
  - Unconfirmation continues to clear confirmation state and any legacy snapshot value.
  - Permit course-target changes below current earned totals.
  - Ensure single and bulk confirmation use identical rules.
- `api/manage/dashboard.php`
  - Calculate student and participation point information from current experiment rewards.
- `api/manage/report.php`
  - Calculate total points from confirmed participations joined to current experiment rewards.
- Audit events
  - Do not represent a stored snapshot as the authoritative awarded total.
  - Event metadata may record the experiment reward observed at confirmation, but runtime totals must not use audit data.

### API Compatibility

- Preserve existing `credits.earned`, `credits.maximum`, and `credits.remaining` fields.
- Add `credits.percentage`; use `null` when the target is null or not positive.
- Preserve `rewardCredits` on experiment rows.
- Preserve `creditedReward` for confirmed rows, but define it as the experiment's current reward rather than a capped snapshot.
- Document the semantic change in `README.md` and `.agents/CONTEXT.md`.

### Automated Tests

Extend the SQLite-backed HTTP smoke test with at least these cases:

1. Course A has target 8 and Course B has target 10.
2. One experiment gives the same reward to confirmed students in both courses.
3. An unconfirmed participation contributes zero points.
4. A student earns less than the target and receives the correct total, remaining value, and percentage.
5. A student earns more than the target and receives the full total and a percentage above 100.
6. A reward crossing the target is counted in full, not partially.
7. A confirmation after reaching the target is counted in full, not as zero.
8. Editing a confirmed experiment's reward immediately changes the student overview total.
9. The same edit immediately changes management dashboard and report totals.
10. Lowering a course target below an earned total succeeds.
11. Null and zero targets do not cause division errors and return `percentage: null`.
12. Unconfirming removes the current experiment reward from every computed total.
13. Bulk confirm and unconfirm follow the same dynamic rules as single confirmation.

Add focused pure-function tests if percentage or decimal normalization is extracted into a public helper.

### Acceptance Criteria

- No runtime point total reads `reward_credits_snapshot`.
- No confirmation is partially credited or reduced to zero because of the course target.
- Reward edits are reflected without re-confirming participations.
- Existing authentication, eligibility, claim, slot, and grading behavior remains intact.
- No database migration is introduced.
- Update `.agents/PROJECT.md` before requesting a milestone commit.

## 7. Milestone 2: Student Points Visualization

### Goal

Give students an immediately understandable and accessible view of their course progress.

### Deliverables

- Add a progress summary above the experiment table.
- Show:
  - course name;
  - earned points;
  - course target;
  - percentage of target.
- Retain the per-experiment `Punkte` column.
- For confirmed experiments, display the current reward without partial-credit wording.
- Replace student-facing `Maximum` terminology with `Punkteziel` or equivalent target wording.
- Display the real percentage above 100 while clamping bar width to 100.
- Provide an accessible progress label and appropriate ARIA values.
- Define clear states for a missing or zero target without showing a misleading percentage.
- Keep the table and summary usable on desktop and mobile widths.

### Example States

- Below target: `Kurs A · 5.4 von 8 Punkten · 67.5%`.
- At target: `Kurs A · 8 von 8 Punkten · 100%`.
- Above target: `Kurs A · 9 von 8 Punkten · 112.5%`, with a bar rendered at 100% width.
- Missing target: show earned points and `Punkteziel noch nicht festgelegt`; omit percentage and determinate progress.
- Zero target: show earned points and the configured zero target; omit percentage and determinate progress.

### Automated Tests

- Assert percentage formatting and progress-width clamping.
- Assert fractional point formatting.
- Assert above-target text is not capped to 100%.
- Assert null/zero-target states.
- Assert the per-experiment reward remains visible before and after confirmation.
- Keep text-quality and JavaScript regression tests green.

### Acceptance Criteria

- A student can distinguish earned points, target points, and percentage without opening experiment details.
- Above-target students see their true total and percentage.
- The visual bar never overflows its container.
- Keyboard, screen-reader, desktop, and mobile behavior is acceptable.
- Update `README.md`, `.agents/CONTEXT.md`, and `.agents/PROJECT.md` before requesting a milestone commit.

## 8. Milestone 3: Browser And Deployment Acceptance

### Goal

Provide repeatable browser coverage and a safe release handoff for the live system.

### Playwright Harness

Add a minimal Playwright setup only for browser behavior that the PHP smoke test cannot verify.

The harness must:

- Use a temporary SQLite database or another disposable fixture database.
- Start a local PHP server with an explicit test environment file.
- Never load the root `.env` or contact the live database.
- Use deterministic course, student, experiment, reward, and confirmation fixtures.
- Avoid network-dependent assertions.
- Clean up temporary database, environment, session, and server resources.

Suggested scenarios:

1. Course A below target at a desktop viewport.
2. Course A above target, including real percentage and clamped bar width.
3. Course B with a different target.
4. Fractional experiment rewards and total formatting.
5. Missing and zero target states.
6. The same core states at a mobile viewport.

Use DOM, accessibility, and computed-layout assertions as the authoritative checks. Capture screenshots as review artifacts and on failure. Do not introduce brittle committed pixel snapshots unless browser versions and all visual assets are locally pinned.

### Local MySQL Acceptance

- Use only a dedicated local database selected through `.env.test`.
- Require `EXPERIMENT_TEST_DATABASE_RESET_ALLOWED=true` before any destructive lifecycle test.
- Verify the resolved database target without printing credentials.
- Never run destructive checks against the root `.env` or the live host.
- Exercise both course targets, dynamic reward changes, above-target totals, reports, and authentication over HTTP.

### Full Verification

- PHP syntax checks for all PHP files.
- JavaScript syntax checks for both browser applications.
- All PHP test scripts.
- SQLite-backed authenticated API smoke test.
- Playwright student overview tests at desktop and mobile sizes.
- Dedicated local MySQL HTTP acceptance flow when a reset-approved local target is available.
- Manual review of generated screenshots.
- `git diff --check`.
- Confirm the worktree contains no `.env`, `.env.test`, database dumps, generated access codes, sessions, or test artifacts.

### Live Deployment Handoff

- Take a verified live backup before deployment even though no schema change is planned.
- Run the read-only live schema verification from Section 4.3.
- Deploy application files only after all required checks pass.
- Verify one below-target and one above-target student scenario without exposing student credentials in logs or screenshots.
- Roll back by restoring the previous application files if acceptance fails; no database rollback should be required.
- Record observed verification results and remaining risks in `.agents/PROJECT.md`.

### Acceptance Criteria

- Browser coverage is reproducible and isolated from live data.
- Both SQLite and local MySQL exercise the new calculation semantics.
- The deployment handoff explicitly states `Database migration required: no` unless the migration gate was triggered.
- Documentation and behavior agree.

## 9. Cross-Cutting Safety Rules

- Never display, log, commit, or copy values from `.env` or `.env.test` into reports or test fixtures.
- Never point automated tests at the live database.
- Never run reset, drop, seed, or migration scripts against an unresolved database target.
- Preserve authentication, CSRF, throttling, and session behavior.
- Preserve experiment eligibility, course audience, capacity, scheduling, access-pool, slot, appointment, and reset behavior.
- Treat `confirmed_at` as the sole grading-status gate.
- Keep dynamic point computation server-side; the browser must not derive authoritative totals by summing visible rows.
- Keep changes compatibility-preserving and avoid unrelated refactors.

## 10. Definition Of Done

The feature is complete when:

- Every confirmed participation contributes its experiment's current full reward.
- No course target caps an earned total.
- Reward edits retroactively update student, dashboard, and report totals.
- Students see current experiment rewards and a clear course progress visualization.
- Percentages may exceed 100%, while progress bars remain visually capped.
- Multiple course targets are covered by automated tests.
- Null, zero, fractional, exact-target, and above-target cases are covered.
- The complete automated suite passes.
- Browser checks pass at desktop and mobile sizes.
- Local MySQL acceptance passes when a reset-approved local test database is available.
- No live database migration is required, or an explicitly approved phpMyAdmin migration exists if the migration gate was triggered.
- `README.md`, `.agents/CONTEXT.md`, and `.agents/PROJECT.md` accurately describe the final behavior and verification results.
- The repository owner is asked to review and confirm each milestone before commit.

## 11. Planned Commit Boundaries

1. Green cross-platform test baseline.
2. Dynamic uncapped reward semantics and API/report coverage.
3. Student progress visualization and browser coverage.
4. Final documentation and deployment-verification adjustments, if they are not already included in the preceding milestones.

No commits are created without repository-owner confirmation.
