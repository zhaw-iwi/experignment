CREATE TABLE schema_versions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    version_number INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_schema_versions_version_number (version_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO schema_versions (version_number, description)
VALUES (2, 'Greenfield multi-experiment schema');

CREATE TABLE allowed_students (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_allowed_students_student_email (student_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE experiments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_open TINYINT(1) NOT NULL DEFAULT 0,
    eligibility_mode ENUM('all_allowed', 'selected') NOT NULL DEFAULT 'selected',
    condition_mode ENUM('none', 'student_choice', 'assigned') NOT NULL DEFAULT 'none',
    requires_time_slot TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_experiments_sort_order (sort_order, id),
    KEY idx_experiments_is_open (is_open)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE experiment_conditions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    experiment_id BIGINT UNSIGNED NOT NULL,
    public_name VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_experiment_conditions_name (experiment_id, public_name),
    KEY idx_experiment_conditions_experiment (experiment_id, sort_order, id),
    CONSTRAINT fk_experiment_conditions_experiment
        FOREIGN KEY (experiment_id) REFERENCES experiments (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE experiment_eligibilities (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    experiment_id BIGINT UNSIGNED NOT NULL,
    student_email VARCHAR(255) NOT NULL,
    condition_id BIGINT UNSIGNED NULL,
    source ENUM('manual', 'random', 'system') NOT NULL DEFAULT 'manual',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_experiment_eligibilities_student (experiment_id, student_email),
    KEY idx_experiment_eligibilities_student_email (student_email),
    KEY idx_experiment_eligibilities_condition (condition_id),
    CONSTRAINT fk_experiment_eligibilities_experiment
        FOREIGN KEY (experiment_id) REFERENCES experiments (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT,
    CONSTRAINT fk_experiment_eligibilities_condition
        FOREIGN KEY (condition_id) REFERENCES experiment_conditions (id)
        ON DELETE SET NULL
        ON UPDATE RESTRICT,
    CONSTRAINT fk_experiment_eligibilities_allowed_student
        FOREIGN KEY (student_email) REFERENCES allowed_students (student_email)
        ON DELETE CASCADE
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE access_fields (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    experiment_id BIGINT UNSIGNED NOT NULL,
    condition_id BIGINT UNSIGNED NULL,
    field_key VARCHAR(64) NOT NULL,
    label VARCHAR(255) NOT NULL,
    value_type ENUM('text', 'url', 'pid', 'appointment') NOT NULL DEFAULT 'text',
    value_source ENUM('shared', 'pool', 'staff_entry') NOT NULL DEFAULT 'shared',
    shared_value TEXT NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_access_fields_key (experiment_id, condition_id, field_key),
    KEY idx_access_fields_experiment (experiment_id, condition_id, sort_order, id),
    CONSTRAINT fk_access_fields_experiment
        FOREIGN KEY (experiment_id) REFERENCES experiments (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT,
    CONSTRAINT fk_access_fields_condition
        FOREIGN KEY (condition_id) REFERENCES experiment_conditions (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE eligibility_field_values (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    eligibility_id BIGINT UNSIGNED NOT NULL,
    field_id BIGINT UNSIGNED NOT NULL,
    field_value TEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_eligibility_field_values (eligibility_id, field_id),
    KEY idx_eligibility_field_values_field (field_id),
    CONSTRAINT fk_eligibility_field_values_eligibility
        FOREIGN KEY (eligibility_id) REFERENCES experiment_eligibilities (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT,
    CONSTRAINT fk_eligibility_field_values_field
        FOREIGN KEY (field_id) REFERENCES access_fields (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE access_pool_rows (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    experiment_id BIGINT UNSIGNED NOT NULL,
    condition_id BIGINT UNSIGNED NULL,
    is_assigned TINYINT(1) NOT NULL DEFAULT 0,
    assigned_participation_id BIGINT UNSIGNED NULL,
    assigned_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_access_pool_rows_participation (assigned_participation_id),
    KEY idx_access_pool_rows_available (experiment_id, condition_id, is_assigned),
    CONSTRAINT fk_access_pool_rows_experiment
        FOREIGN KEY (experiment_id) REFERENCES experiments (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT,
    CONSTRAINT fk_access_pool_rows_condition
        FOREIGN KEY (condition_id) REFERENCES experiment_conditions (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE access_pool_values (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pool_row_id BIGINT UNSIGNED NOT NULL,
    field_id BIGINT UNSIGNED NOT NULL,
    field_value TEXT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_access_pool_values_row_field (pool_row_id, field_id),
    KEY idx_access_pool_values_field (field_id),
    CONSTRAINT fk_access_pool_values_row
        FOREIGN KEY (pool_row_id) REFERENCES access_pool_rows (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT,
    CONSTRAINT fk_access_pool_values_field
        FOREIGN KEY (field_id) REFERENCES access_fields (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE participations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    experiment_id BIGINT UNSIGNED NOT NULL,
    condition_id BIGINT UNSIGNED NULL,
    student_email VARCHAR(255) NOT NULL,
    access_pool_row_id BIGINT UNSIGNED NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_participations_experiment_student (experiment_id, student_email),
    UNIQUE KEY uq_participations_access_pool_row (access_pool_row_id),
    KEY idx_participations_student_email (student_email),
    KEY idx_participations_condition (condition_id),
    CONSTRAINT fk_participations_experiment
        FOREIGN KEY (experiment_id) REFERENCES experiments (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT,
    CONSTRAINT fk_participations_condition
        FOREIGN KEY (condition_id) REFERENCES experiment_conditions (id)
        ON DELETE SET NULL
        ON UPDATE RESTRICT,
    CONSTRAINT fk_participations_allowed_student
        FOREIGN KEY (student_email) REFERENCES allowed_students (student_email)
        ON DELETE CASCADE
        ON UPDATE RESTRICT,
    CONSTRAINT fk_participations_access_pool_row
        FOREIGN KEY (access_pool_row_id) REFERENCES access_pool_rows (id)
        ON DELETE SET NULL
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE participation_field_values (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    participation_id BIGINT UNSIGNED NOT NULL,
    field_id BIGINT UNSIGNED NOT NULL,
    field_value TEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_participation_field_values (participation_id, field_id),
    KEY idx_participation_field_values_field (field_id),
    CONSTRAINT fk_participation_field_values_participation
        FOREIGN KEY (participation_id) REFERENCES participations (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT,
    CONSTRAINT fk_participation_field_values_field
        FOREIGN KEY (field_id) REFERENCES access_fields (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE time_slots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    experiment_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(255) NOT NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_time_slots_experiment (experiment_id, is_active, sort_order, id),
    CONSTRAINT fk_time_slots_experiment
        FOREIGN KEY (experiment_id) REFERENCES experiments (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE slot_choices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    participation_id BIGINT UNSIGNED NOT NULL,
    time_slot_id BIGINT UNSIGNED NOT NULL,
    chosen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_slot_choices_participation (participation_id),
    KEY idx_slot_choices_slot (time_slot_id),
    CONSTRAINT fk_slot_choices_participation
        FOREIGN KEY (participation_id) REFERENCES participations (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT,
    CONSTRAINT fk_slot_choices_slot
        FOREIGN KEY (time_slot_id) REFERENCES time_slots (id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE appointments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    participation_id BIGINT UNSIGNED NOT NULL,
    appointment_text VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_appointments_participation (participation_id),
    CONSTRAINT fk_appointments_participation
        FOREIGN KEY (participation_id) REFERENCES participations (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE randomization_runs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    experiment_id BIGINT UNSIGNED NOT NULL,
    seed VARCHAR(128) NOT NULL,
    total_students INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_randomization_runs_experiment (experiment_id, created_at),
    CONSTRAINT fk_randomization_runs_experiment
        FOREIGN KEY (experiment_id) REFERENCES experiments (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE randomization_run_allocations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    run_id BIGINT UNSIGNED NOT NULL,
    condition_id BIGINT UNSIGNED NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    assigned_count INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_randomization_run_allocations_run (run_id),
    CONSTRAINT fk_randomization_run_allocations_run
        FOREIGN KEY (run_id) REFERENCES randomization_runs (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT,
    CONSTRAINT fk_randomization_run_allocations_condition
        FOREIGN KEY (condition_id) REFERENCES experiment_conditions (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
