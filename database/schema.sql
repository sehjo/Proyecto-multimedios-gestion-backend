-- Esquema de base de datos para el backend en PHP puro.
-- Orden de creación respeta las dependencias de llave foránea.

CREATE TABLE IF NOT EXISTS users_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    lastname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_users_user_type FOREIGN KEY (user_type_id) REFERENCES users_types (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS patient (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    lastname VARCHAR(255) NOT NULL,
    nick VARCHAR(255) NOT NULL,
    suffering VARCHAR(500) NULL,
    register_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_patient_register_by FOREIGN KEY (register_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS priority (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disease (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    technincal_name VARCHAR(255) NULL,
    description TEXT NULL,
    priority_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_disease_priority FOREIGN KEY (priority_id) REFERENCES priority (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS drugs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS diagnoses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    disease_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    diagnoses_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_diagnoses_disease FOREIGN KEY (disease_id) REFERENCES disease (id),
    CONSTRAINT fk_diagnoses_patient FOREIGN KEY (patient_id) REFERENCES patient (id),
    CONSTRAINT fk_diagnoses_user FOREIGN KEY (diagnoses_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disease_has_treatments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    disease_id BIGINT UNSIGNED NOT NULL,
    drugs BIGINT UNSIGNED NOT NULL,
    descriptions VARCHAR(500) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_disease_treatments_disease FOREIGN KEY (disease_id) REFERENCES disease (id),
    CONSTRAINT fk_disease_treatments_drug FOREIGN KEY (drugs) REFERENCES drugs (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS diagnoses_has_treatments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    diagnoses_id BIGINT UNSIGNED NOT NULL,
    drugs BIGINT UNSIGNED NOT NULL,
    descriptions VARCHAR(500) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_diagnoses_treatments_diagnoses FOREIGN KEY (diagnoses_id) REFERENCES diagnoses (id),
    CONSTRAINT fk_diagnoses_treatments_drug FOREIGN KEY (drugs) REFERENCES drugs (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS personal_access_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT UNSIGNED NOT NULL,
    name TEXT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_personal_access_tokens_tokenable (tokenable_type, tokenable_id),
    INDEX idx_personal_access_tokens_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email VARCHAR(255) NOT NULL PRIMARY KEY,
    token VARCHAR(64) NOT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_password_reset_tokens_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Roles & permissions (ported from the Laravel Spatie module).
-- Roles ARE the existing users_types rows; permissions hang off them via the
-- usertype_has_permissions pivot. A user's role is users.user_type_id, so a
-- user holds exactly one role (matching how dev already works).
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usertype_has_permissions (
    user_type_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (user_type_id, permission_id),
    CONSTRAINT fk_uthp_user_type FOREIGN KEY (user_type_id) REFERENCES users_types (id) ON DELETE CASCADE,
    CONSTRAINT fk_uthp_permission FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Audit logs (read-only): one row per relevant action on users / roles.
-- `changes` stores a JSON blob with {field: {old, new}} like the Laravel logger.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS log_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    actor_id BIGINT UNSIGNED NULL,
    target_id BIGINT UNSIGNED NULL,
    changes TEXT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_log_users_target (target_id),
    INDEX idx_log_users_actor (actor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS log_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    actor_id BIGINT UNSIGNED NULL,
    target_id BIGINT UNSIGNED NULL,
    changes TEXT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_log_roles_target (target_id),
    INDEX idx_log_roles_actor (actor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
