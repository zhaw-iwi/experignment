-- Reset script for experiment web app test cycles.
-- Keeps assignment_items rows intact and only resets runtime state.

START TRANSACTION;

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE browser_tokens;
TRUNCATE TABLE participant_assignment_links;
TRUNCATE TABLE participant_credits;

UPDATE assignment_items
SET is_assigned = 0,
    assigned_at = NULL;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;
