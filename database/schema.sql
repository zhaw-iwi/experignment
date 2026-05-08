CREATE TABLE allowed_students (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_allowed_students_student_email (student_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE participant_credits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_participant_credits_student_email (student_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE assignment_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pool ENUM('text', 'tablet') NOT NULL,
    survey_url TEXT NOT NULL,
    pin VARCHAR(32) NOT NULL,
    agent_url TEXT NULL,
    is_assigned TINYINT(1) NOT NULL DEFAULT 0,
    assigned_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_assignment_items_pool_pin (pool, pin),
    KEY idx_assignment_items_pool_available (pool, is_assigned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE participant_assignment_links (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_email VARCHAR(255) NOT NULL,
    assignment_item_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_participant_assignment_links_student_email (student_email),
    UNIQUE KEY uq_participant_assignment_links_assignment_item_id (assignment_item_id),
    CONSTRAINT fk_participant_assignment_links_assignment_item
        FOREIGN KEY (assignment_item_id) REFERENCES assignment_items (id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE browser_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    token_hash CHAR(64) NOT NULL,
    assignment_item_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_browser_tokens_token_hash (token_hash),
    UNIQUE KEY uq_browser_tokens_assignment_item_id (assignment_item_id),
    CONSTRAINT fk_browser_tokens_assignment_item
        FOREIGN KEY (assignment_item_id) REFERENCES assignment_items (id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
