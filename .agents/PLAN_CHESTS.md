# Student Participation Chests Plan

Date: 2026-09-15

Status: In progress - Milestone 1 complete; Milestone 2 next

## 1. Objective

Give a student exactly one durable, celebratory chest when an administrator credits that student's experiment participation.

The completed behavior must:

- Create the chest on the server when a participation changes from unconfirmed to confirmed.
- Count the experiment points immediately at confirmation time, independently of the chest UI.
- Persist unopened chests while the student is logged out.
- Allow several unopened chests to accumulate and present them one at a time on the student's next login.
- Prevent duplicate chests from retries, double-clicks, concurrent requests, and bulk-operation replays.
- Keep opened chests as history instead of deleting them.
- Scope every chest read and acknowledgement to the authenticated student.
- Provide the complete full-motion chest sequence and an immediate reduced-motion equivalent described in `.agents/skills/GAME_CHEST.md`.
- Remain safe to introduce on the live MySQL system through an additive phpMyAdmin migration.

## 2. Confirmed Product Decisions

### 2.1 Earning And Points

- A chest is earned only for a future participation confirmation after this feature is deployed.
- The migration must not backfill already-confirmed participations.
- The chest is a celebration of an existing credit decision; it does not award additional points.
- Points continue to become authoritative as soon as `participations.confirmed_at` is set.
- Opening, acknowledging, retrying, or failing to acknowledge a chest must never change the student's points.
- The course-specific point target has no role in chest creation.
- Experiment rewards remain course-independent and dynamically calculated from the experiment's current `reward_credits` value.

### 2.2 Exactly-Once Semantics

- The identity of the trigger is the participation, not an HTTP request or an audit event.
- One participation can have at most one `participation_credited` chest during its lifetime.
- A confirmation request for an already-confirmed participation creates nothing.
- A bulk confirmation creates one chest for each participation that actually changes to confirmed.
- Database uniqueness is the final duplicate-prevention boundary; browser and PHP guards are additional protections.

### 2.3 Unconfirmation And Reset

Use the following anti-farming behavior unless a later explicit product decision changes it:

- Unconfirming a participation revokes its unopened chest so the student cannot open a chest for a credit that no longer exists.
- An already-opened chest remains immutable history if the participation is later unconfirmed.
- Reconfirming the same participation reactivates its existing unopened chest, but never creates a second chest.
- Reconfirming a participation whose chest was already opened creates no new chest.
- Resetting or bulk-resetting a participation revokes any unopened chest before deleting the participation.
- A later, genuinely new participation row receives a new participation ID and may earn its own chest.

### 2.4 Presentation

- Pending chests do not expire and cannot be silently dismissed.
- Chests are ordered by `earned_at`, then `id`, and are opened sequentially.
- The initial release has one stable server-selected variant, `gold`; the data model and asset mapping may support more variants later.
- The chest reveal identifies the credited experiment but does not display a frozen `+N points` value. This avoids contradicting the authoritative total when an experiment's reward is edited later.
- The student overview's points card remains the authoritative representation of the current total.
- A visible unopened-chest count and action remain available until the queue is empty.
- Following an explicit successful login, the first pending chest may be presented automatically. Restoring an existing browser session must expose the count without unexpectedly moving focus; the student opens the queue explicitly.

## 3. Current Architecture And Integration Points

The current application already provides the required foundations:

- Student sessions and CSRF tokens are managed through `api/_auth.php`.
- `api/manage/actions.php` owns both single and bulk confirmation writes.
- `update_participation_confirmation()` locks a participation and reports whether its confirmation state actually changed.
- Single and bulk confirmation already run inside database transactions.
- `api/student_overview.php` and `assets/app.js` load the authenticated student overview after login or session restoration.
- `tests/api_smoke_test.php` provides authenticated SQLite-backed HTTP coverage.
- `tests/browser/run.mjs` and Playwright provide an isolated local browser harness.
- `scripts/deployment_preflight.php` verifies the required MySQL tables and schema version.

Generic `audit_events` are not the chest store. They do not provide one row per credited participation in bulk operations, student-scoped pending state, acknowledgement state, or a source-level uniqueness constraint.

## 4. Data Contract And Migration

### 4.1 New Table

Add `student_chest_events` with fields equivalent to:

- `id BIGINT UNSIGNED` primary key.
- `student_email VARCHAR(255)` identifying the owner.
- `source_participation_id BIGINT UNSIGNED NULL` for operational lookup while the participation exists.
- `event_type VARCHAR(64)` with initial value `participation_credited`.
- `trigger_scope VARCHAR(191)` with an immutable value such as `participation:1234`.
- `variant VARCHAR(32)` with initial value `gold`.
- `experiment_name_snapshot VARCHAR(255)` so history stays meaningful after experiment edits or deletion.
- `earned_at DATETIME` recording when the confirmation earned the chest.
- `opened_at DATETIME NULL` recording the idempotent acknowledgement.
- `revoked_at DATETIME NULL` recording removal of an unopened entitlement after unconfirmation or reset.

Required keys and constraints:

- Unique key on `(event_type, trigger_scope)` for one lifetime chest per participation trigger.
- Pending-query index beginning with `(student_email, opened_at, revoked_at, earned_at, id)` or an equivalent index justified by `EXPLAIN`.
- Foreign key from `student_email` to `allowed_students.student_email`, using the repository's intended student-deletion behavior.
- Nullable foreign key from `source_participation_id` to `participations.id` with `ON DELETE SET NULL`, while `trigger_scope` preserves immutable source identity.
- Non-empty or enumerated values must be enforced in application validation where MySQL and SQLite constraints differ.

Pending means `opened_at IS NULL AND revoked_at IS NULL`. Opened and revoked rows remain stored for auditability.

### 4.2 Schema Version

- Advance the canonical schema from version 3 to version 4.
- Update `api/bootstrap.php` and deployment checks to report the required application/schema generation consistently.
- Update the preflight required-table count from 20 to 21 and verify the chest table's critical columns and InnoDB engine.
- Do not rename the existing session cookie merely because the database schema version changes; an unnecessary forced logout is outside this feature.

### 4.3 phpMyAdmin Migration

Create:

`database/migrations/2026-09-15-student-chests/migration.sql`

The migration must:

- State that it upgrades a V3 installation to V4.
- Be directly executable in phpMyAdmin without shell-only commands or placeholders.
- Create only the new table, keys, and schema-version row.
- Contain no `INSERT ... SELECT` from existing participations and therefore perform no historical backfill.
- Include commented precondition and post-migration verification queries.
- Explain that MySQL DDL auto-commits and that restoring the pre-migration backup is the reliable rollback for a failed migration.
- Avoid destructive cleanup of an existing table if the migration is accidentally rerun; fail clearly or use a deliberately documented idempotency strategy.

Update these canonical/reset consumers in the same milestone:

- `database/schema.sql`
- `database/reset.sql`
- `database/reset_all_data.sql`
- `database/drop_tables.sql`
- `tests/schema_test.php`
- `tests/api_smoke_test.php` fixture schema
- `tests/browser/create_fixture.php`
- `scripts/deployment_preflight.php`

Experiment resets must clear chest events before participations so a new semester cannot inherit unopened chests. Full resets must also clear them. Drop order must respect both student and participation foreign keys.

## 5. Server Behavior Contract

### 5.1 Chest Creation

Extend the confirmation domain helper rather than duplicating behavior in individual routes:

1. Lock and load the participation, student, experiment name, and confirmation state.
2. If it is already confirmed, return unchanged and do not create or reactivate anything.
3. Set `confirmed_at` as it does today.
4. Insert the `participation_credited` chest in the same transaction.
5. If the unique trigger already exists and is unopened/revoked, reactivate that row rather than inserting another.
6. If the existing chest was opened, leave it unchanged and create nothing.
7. Commit the participation and chest changes together.

The implementation must use parameterized statements and handle a uniqueness race as an expected idempotency outcome. A chest insertion error other than the understood uniqueness case must roll back the confirmation.

### 5.2 Revocation

When a confirmed participation becomes unconfirmed, set `revoked_at` only on its unopened chest in the same transaction. Do not alter `opened_at` or delete history.

Before single reset, bulk reset, student reset, or any other path deletes participations, revoke their unopened chest events in the same transaction. The nullable source foreign key may then become `NULL`; the immutable trigger and experiment snapshot remain available.

### 5.3 Student API

Add authenticated student endpoints with the repository's standard JSON error shape:

- `GET api/student_chests.php`
  - Derive the owner exclusively from the current student session.
  - Return pending chests in stable FIFO order, plus a total pending count.
  - Optionally accept a bounded `status=pending|history` query if history is exposed in the first release.
  - Return plain data only; never return trusted HTML.
- `POST api/open_student_chest.php`
  - Require student authentication and the existing CSRF header.
  - Accept only a validated positive chest ID.
  - Scope the update by both chest ID and authenticated student email.
  - Set `opened_at` with `COALESCE(opened_at, CURRENT_TIMESTAMP)` so duplicate acknowledgement succeeds safely.
  - Return the authoritative current event state if another tab already opened it.
  - Return the same not-found response for missing and foreign-owned IDs so ownership is not disclosed.
  - Reject revoked events as no longer openable.

The API payload should expose only stable UI data such as `id`, `eventType`, `variant`, `experimentName`, `earnedAt`, and `openedAt`. It must not accept variant, title, message, student identity, or reward values from the browser.

## 6. Student Experience Contract

### 6.1 Queue And State Machine

Implement one shared chest controller with explicit phases:

`closed -> charging -> bursting -> revealed -> open -> next/reset`

The controller must:

- Fetch authoritative pending events after the overview is available.
- Maintain one FIFO queue and render only the current event.
- Display a persistent unopened count and queue-opening control.
- Start every event with the closed image and an enabled native button.
- Disable and guard the opening action synchronously on pointer, keyboard, touch, and programmatic activation.
- Send exactly one acknowledgement when the content is revealed, without waiting for the network before settling the visual.
- Advance only after the current chest is revealed and the student activates Continue/Next.
- Reset all classes, labels, busy state, images, announcements, retry state, and guards before the next event.
- Use cancellable delays plus a generation token so closing, logout, rerender, or queue advance invalidates stale callbacks.
- Treat an already-opened result from another tab as a safe authoritative state.
- Keep revealed content visible when acknowledgement fails and offer a retry that does not replay the animation.
- Refetch pending state after the queue empties rather than caching a permanent empty flag.

### 6.2 Content

Initial German copy should communicate the real domain event, for example:

- Title: `Teilnahme angerechnet!`
- Body: `Ihre Teilnahme an „{experimentName}“ wurde angerechnet.`
- Queue count: `1 neue Truhe` / `{n} neue Truhen`

Use text nodes or equivalent escaping for the experiment-name snapshot. Do not describe the chest as separately granting points and do not show an approval-time point snapshot.

### 6.3 Full-Motion Choreography

Follow `.agents/skills/GAME_CHEST.md` rather than reducing the effect to an image swap:

- Closed chest arrival and idle float.
- 700 ms pressure/charging phase while the chest remains closed.
- Open-image swap, spring burst, shockwave, stage recoil, and 14 deterministic particles.
- Reveal after another 120 ms.
- Stable open state after a further 360 ms.
- Particle tail cleanup after the specified additional 430 ms.
- No looping or replay once open.
- Deterministic particle positions, shapes, colors, and timing for reproducible tests.

Keep acknowledgement off the visual critical path. Network latency must not change choreography timing.

### 6.4 Reduced Motion And Accessibility

- Check `prefers-reduced-motion: reduce` in JavaScript at activation time.
- In reduced motion, skip charging, burst classes, particles, and every choreography delay; reveal and acknowledge immediately.
- Add component-scoped reduced-motion CSS as a defensive second layer.
- Use a native button with a visible focus state and a suitable touch target.
- Keep accessible labels current: unopened, opening, then opened.
- Use `aria-busy` only during active full-motion opening.
- Announce the semantic result once when revealed, not every decorative phase.
- Give decorative images empty alternative text and hide particles from assistive technology.
- Use the host modal's focus trap and restore focus to the queue trigger when it closes.
- Move focus deliberately to the revealed heading or Continue action after opening, as validated by keyboard tests.
- Ensure long experiment names, acknowledgement errors, and actions do not overflow on mobile.

### 6.5 Assets

- Store one approved closed image and one approved open-gold image under `assets/chests/`.
- Do not hotlink or copy an unreviewed third-party asset collection.
- Add source/ownership, license or owner approval, version, and optimization notes beside the assets.
- Give closed and open images matching intrinsic dimensions and aspect ratios.
- Preload or decode both images before enabling the interaction.
- Centralize variant-to-image mapping with a safe `gold` fallback.

## 7. Milestone 1: V4 Persistence And Live Migration

### Goal

Introduce the durable chest-event model without changing confirmation or student behavior yet.

### Deliverables

- Add the phpMyAdmin-compatible V3-to-V4 migration with no backfill.
- Add `student_chest_events` to the canonical MySQL schema.
- Update experiment reset, full reset, and drop scripts.
- Update the SQLite smoke and browser schemas.
- Update schema tests, bootstrap version reporting, and production preflight for V4 and 21 tables.
- Document exact migration preconditions, verification queries, backup requirements, and rollback limitations.
- Update `.agents/PROJECT.md` before the milestone commit.

### Automated Verification

- PHP syntax checks.
- `php tests/schema_test.php`.
- Existing configuration, validation, text-quality, JavaScript regression, and API smoke tests.
- Disposable MySQL migration test from a representative V3 schema when an explicitly reset-approved local target is available.
- Verify a newly migrated table contains zero rows even when V3 already has confirmed participations.
- Verify rerunning the migration has the documented safe failure or idempotent outcome and cannot silently duplicate the schema-version row.

### Acceptance Criteria

- A V3 database can be upgraded through phpMyAdmin without changing existing participations.
- Schema version 4 and all 21 tables pass preflight.
- The migration does not create any chest event for historical confirmations.
- All reset/drop paths handle the new foreign-key dependencies correctly.
- No application path depends on the new table before this milestone's deployment instructions say the migration must be applied.

## 8. Milestone 2: Exactly-Once Earning And Student API

### Goal

Create, retain, revoke, query, and acknowledge chest events correctly without relying on a browser animation.

### Deliverables

- Add small persistence/domain helpers for chest creation, reactivation, revocation, pending/history reads, and acknowledgement.
- Integrate creation into the shared confirmation helper so single and bulk grading behave identically.
- Integrate revocation into unconfirmation and every participation-reset path.
- Add the authenticated pending/history read endpoint.
- Add the authenticated, CSRF-protected acknowledgement endpoint.
- Add concise structured audit/error information where it helps diagnose event failures without logging sensitive payloads.
- Update `README.md`, `.agents/CONTEXT.md`, and `.agents/PROJECT.md`.

### Automated API And Persistence Tests

At minimum, prove:

1. A new unconfirmed-to-confirmed transition creates one pending chest.
2. The chest and confirmation commit atomically.
3. Confirming an already-confirmed participation creates no second row.
4. Duplicate and concurrent trigger attempts still leave one row.
5. Bulk confirmation creates one chest for each actually changed participation.
6. Several confirmations while the student is logged out remain pending in stable order.
7. Students with no events receive an empty list, and an event earned after that check is discoverable.
8. A student cannot list or acknowledge another student's event.
9. Missing authentication, invalid IDs, invalid methods, and invalid CSRF tokens are rejected consistently.
10. Opening one event removes only that event from pending results.
11. Repeated acknowledgement succeeds without creating another effect or timestamp transition.
12. Opened rows remain available to an authorized history query or direct persistence assertion.
13. Unconfirming revokes an unopened event.
14. Reconfirming reactivates that unopened event without inserting a second row.
15. Unconfirming and reconfirming after opening leaves the original history and creates no new event.
16. Single reset, bulk reset, and student reset revoke unopened events before deleting participations.
17. A fresh participation created after reset can earn one new chest.
18. Changing an experiment's reward never mutates chest history and current point totals remain authoritative.
19. No pre-deployment confirmed fixture is backfilled by the migration.

### Acceptance Criteria

- Exactly one database row exists for each eligible participation trigger.
- Pending events survive logout, process restart, and later login.
- Confirmation, unconfirmation, and reset cannot leave an invalid unopened entitlement.
- No chest API trusts browser-provided identity, reward, copy, or variant data.
- Points do not depend on chest delivery or acknowledgement.

## 9. Milestone 3: Student Queue, Chest Visual, And Accessibility

### Goal

Turn pending events into a responsive, accessible, and deterministic student experience.

### Deliverables

- Add semantic modal/queue markup and a persistent unopened-count trigger to `index.html`.
- Add a focused chest controller module, keeping queue and animation logic out of unrelated overview rendering.
- Integrate it with successful login, restored sessions, overview rendering, and logout cleanup in `assets/app.js`.
- Add component-scoped styles and deterministic full-motion choreography to `assets/app.css` or a dedicated chest stylesheet.
- Add local closed/open-gold assets and their provenance/license record.
- Add full JavaScript and CSS reduced-motion paths.
- Add retry-without-replay behavior for acknowledgement failures.
- Add focused unit/regression tests for formatting, state transitions, timing constants, asset mapping, cancellation, and queue reset.
- Update `README.md`, `.agents/CONTEXT.md`, and `.agents/PROJECT.md`.

### Automated Verification

- JavaScript syntax checks for every changed/new module.
- Pure Node tests for the queue/state helpers and variant mapping.
- Regression tests for semantic markup, local asset references, deterministic particles, reduced-motion coverage, and no unsafe HTML insertion.
- Existing PHP, API smoke, points UI, and text-quality tests.
- Asset decode checks proving no runtime request needs an external chest source.

### Acceptance Criteria

- A student with several pending events sees the correct count and can open every chest sequentially.
- Each queue item starts closed and can trigger only one acknowledgement.
- Normal motion follows the documented pressure, burst, reveal, settle, and cleanup sequence.
- Reduced motion reveals immediately with no artificial waiting.
- Closing, logout, rerender, and advancing cannot allow stale timers to alter a later event.
- A failed acknowledgement leaves the reveal readable and retryable without replaying the chest.
- Keyboard, screen-reader, focus-restoration, desktop, and mobile behavior are usable.

## 10. Milestone 4: Browser Acceptance And Production Handoff

### Goal

Prove the real multi-chest experience and provide a safe migration-first deployment procedure for the live system.

### Playwright Scenarios

Extend the isolated browser harness with deterministic fixtures and at least these cases:

1. No pending chest: overview renders normally and the chest action is absent or reports zero.
2. One future approval: explicit login exposes one closed chest and the correct experiment name after opening.
3. Multiple approvals while logged out: the exact count is shown and events open oldest first.
4. Each event starts with the closed image and produces one acknowledgement.
5. Full motion uses the charging, burst, reveal, open, and particle-cleanup phases at the specified times.
6. Duplicate pointer/programmatic activation during charging cannot send a second acknowledgement.
7. Reduced motion reaches the open state immediately without charging, bursting, shockwave, or particles.
8. Closing during charging, waiting beyond all old timers, and reopening a different event produces no stale state.
9. Slow acknowledgement does not delay reveal or settlement.
10. Failed acknowledgement keeps content visible; retry succeeds without replay.
11. Session restoration shows the pending count without unsolicited focus movement.
12. A second tab acknowledging the same event is handled safely.
13. Mobile and desktop layouts contain long German experiment names and error/Continue controls without horizontal overflow.
14. Keyboard activation, modal focus trap, announced result, and focus restoration work.
15. Static screenshots cover the stable closed and settled-open states; class, timing, and animation-name assertions cover motion that screenshots suppress.

The browser runner must keep using a temporary SQLite database and explicit temporary environment file, must never load the root `.env`, and must clean up the server, database, sessions, and default artifacts.

### Full Verification

- Syntax-check every PHP and JavaScript file.
- Run all existing PHP and Node tests.
- Run the expanded SQLite HTTP smoke test.
- Run the complete Playwright suite in Chromium with explicit normal- and reduced-motion emulation.
- Review closed/open screenshots at original resolution.
- Run `git diff --check` and verify that no test artifacts, credentials, generated sessions, or external asset URLs remain.
- When a dedicated disposable MySQL target is available, apply the exact migration file and run the V4 deployment preflight.

### Production Handoff

Update `PRODUCTION_CUTOVER.md` with this exact order:

1. Put the normal application backup and database backup in place.
2. Run read-only V3 precondition queries and confirm the chest table does not already exist.
3. Execute `database/migrations/2026-09-15-student-chests/migration.sql` through phpMyAdmin.
4. Run the migration's verification queries: schema version 4, required columns/indexes, and zero chest rows.
5. Deploy the V4 application files immediately after the migration.
6. Run `scripts/deployment_preflight.php` against the live configuration.
7. Confirm one disposable/test participation and verify exactly one pending chest for that student.
8. Log in as that student, open the chest, and verify the current points overview remains correct.
9. Repeat the confirmation request and verify no second chest appears.
10. Remove or reset only the explicitly designated test data according to the documented cleanup path.

Applying the additive migration while the V3 application is still serving is safe because V3 ignores the extra table. Deploying V4 application files before the migration is not safe because confirmation and chest endpoints require the table.

If application rollback is needed after chest events exist, restore the previous application files and leave the V4 table intact. Do not drop it and lose student history merely to report schema version 3. A database rollback should use the pre-migration backup only when loss of all post-migration activity is explicitly acceptable.

### Acceptance Criteria

- All automated checks and both motion modes pass from a clean checkout.
- The live migration procedure is executable through phpMyAdmin and explicitly performs no backfill.
- A future live confirmation yields exactly one durable chest and the student's points immediately remain correct.
- Multiple approvals made between student logins are presented as multiple independent chests.
- Operational rollback does not require deleting earned/opened chest history.

## 11. Global Definition Of Done

The feature is complete only when:

- The V4 schema and migration are tested and documented.
- All confirmation and reset paths preserve the exactly-once lifecycle.
- Authentication, ownership, CSRF, validation, and multi-tab behavior are covered.
- The student queue is deterministic, accessible, responsive, and safe under failure.
- Full and reduced motion both provide the same semantic result.
- Assets are local and have documented redistribution rights or owner approval.
- Existing experiment access, confirmation, points, reporting, authentication, and management tests remain green.
- `README.md`, `.agents/CONTEXT.md`, `.agents/PROJECT.md`, and `PRODUCTION_CUTOVER.md` match the delivered behavior.
- Every completed milestone has its own reviewed, test-backed commit before work proceeds to the next milestone.
