INSERT INTO allowed_students (student_email) VALUES
    ('alice@students.zhaw.ch'),
    ('bob@students.zhaw.ch'),
    ('charlie@students.zhaw.ch'),
    ('dana@students.zhaw.ch');

INSERT INTO experiments
    (id, public_name, description, is_open, eligibility_mode, condition_mode, requires_time_slot, sort_order)
VALUES
    (1, 'Experiment 1', 'Beispiel mit zwei wählbaren Bedingungen.', 1, 'all_allowed', 'student_choice', 0, 10),
    (2, 'Experiment 1b', 'Beispiel mit PID und gemeinsamem Link.', 0, 'selected', 'none', 0, 20),
    (3, 'Experiment 3', 'Beispiel mit Zeitslot-Auswahl.', 1, 'all_allowed', 'none', 1, 30);

INSERT INTO experiment_conditions (id, experiment_id, public_name, sort_order) VALUES
    (1, 1, 'Text', 10),
    (2, 1, 'Tablet', 20);

INSERT INTO experiment_eligibilities (experiment_id, student_email, source) VALUES
    (2, 'alice@students.zhaw.ch', 'manual'),
    (2, 'bob@students.zhaw.ch', 'manual');

INSERT INTO access_fields
    (id, experiment_id, condition_id, field_key, label, value_type, value_source, shared_value, sort_order)
VALUES
    (1, 1, 1, 'pid', 'Participant ID', 'pid', 'pool', NULL, 10),
    (2, 1, 1, 'survey', 'Umfrage', 'url', 'pool', NULL, 20),
    (3, 1, 1, 'chatbot', 'Chatbot', 'url', 'pool', NULL, 30),
    (4, 1, 2, 'pid', 'Participant ID', 'pid', 'pool', NULL, 10),
    (5, 1, 2, 'survey', 'Umfrage', 'url', 'pool', NULL, 20),
    (6, 2, NULL, 'pid', 'Participant ID', 'pid', 'pool', NULL, 10),
    (7, 2, NULL, 'link', 'Teilnahmelink', 'url', 'shared', 'https://example.test/experiment-1b', 20),
    (8, 3, NULL, 'pid', 'Participant ID', 'pid', 'pool', NULL, 10),
    (9, 3, NULL, 'appointment', 'Zugewiesene Uhrzeit', 'appointment', 'staff_entry', NULL, 30);

INSERT INTO access_pool_rows (id, experiment_id, condition_id) VALUES
    (1, 1, 1),
    (2, 1, 1),
    (3, 1, 2),
    (4, 2, NULL),
    (5, 2, NULL),
    (6, 3, NULL),
    (7, 3, NULL);

INSERT INTO access_pool_values (pool_row_id, field_id, field_value) VALUES
    (1, 1, 'T001'),
    (1, 2, 'https://example.test/survey/text-1'),
    (1, 3, 'https://example.test/chatbot/text-1'),
    (2, 1, 'T002'),
    (2, 2, 'https://example.test/survey/text-2'),
    (2, 3, 'https://example.test/chatbot/text-2'),
    (3, 4, 'TAB001'),
    (3, 5, 'https://example.test/survey/tablet-1'),
    (4, 6, 'B001'),
    (5, 6, 'B002'),
    (6, 8, 'S001'),
    (7, 8, 'S002');

INSERT INTO time_slots
    (experiment_id, label, starts_at, ends_at, capacity, is_active, sort_order)
VALUES
    (3, 'Montag Vormittag', '2026-06-01 08:00:00', '2026-06-01 12:00:00', 2, 1, 10),
    (3, 'Dienstag Nachmittag', '2026-06-02 13:00:00', '2026-06-02 17:00:00', 2, 1, 20);
