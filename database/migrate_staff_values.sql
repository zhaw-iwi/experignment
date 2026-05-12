-- Adds pre-participation storage for access fields sourced from staff entry.
-- Run once on existing V2 databases before deploying this version.

CREATE TABLE IF NOT EXISTS eligibility_field_values (
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
