-- Schema completo del backend PHP (estilo Multimedios-2026).
-- Ejecutar en orden; las FK respetan las dependencias.

-- -------------------------------------------------------------------
-- Permisos
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS permissions (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------
-- Roles
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pivot rol ↔ permiso
CREATE TABLE IF NOT EXISTS role_has_permissions (
    role_id       BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rhp_role       FOREIGN KEY (role_id)       REFERENCES roles (id)       ON DELETE CASCADE,
    CONSTRAINT fk_rhp_permission FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------
-- Usuarios
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(255) NOT NULL,
    identifier        VARCHAR(50)  NOT NULL,
    email             VARCHAR(254) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP    NULL,
    password          VARCHAR(255) NOT NULL,
    status            VARCHAR(20)  NOT NULL DEFAULT 'active',
    is_guest          TINYINT(1)   NOT NULL DEFAULT 0,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    deleted_at        TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pivot usuario ↔ rol
CREATE TABLE IF NOT EXISTS user_has_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_uhr_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_uhr_role FOREIGN KEY (role_id) REFERENCES roles  (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permisos directos de usuario (sin pasar por rol)
CREATE TABLE IF NOT EXISTS user_has_permissions (
    user_id       BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, permission_id),
    CONSTRAINT fk_uhp_user       FOREIGN KEY (user_id)       REFERENCES users       (id) ON DELETE CASCADE,
    CONSTRAINT fk_uhp_permission FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------
-- Tokens de acceso
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tokens (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    BIGINT UNSIGNED NOT NULL,
    token      VARCHAR(64)  NOT NULL UNIQUE,
    expires_at TIMESTAMP    NOT NULL,
    revoked    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NULL,
    CONSTRAINT fk_tokens_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------
-- Tokens de reset de contraseña
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email      VARCHAR(254) NOT NULL PRIMARY KEY,
    token      VARCHAR(64)  NOT NULL,
    created_at TIMESTAMP    NULL,
    INDEX idx_prt_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------
-- Acompañantes
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS companions (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(255) NOT NULL,
    identifier VARCHAR(50)  NOT NULL UNIQUE,
    phone      VARCHAR(20)  NOT NULL,
    created_at TIMESTAMP    NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------
-- Pacientes
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS patients (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(255) NOT NULL,
    identifier VARCHAR(50)  NOT NULL UNIQUE,
    email      VARCHAR(254) NOT NULL,
    birth_date DATE         NOT NULL,
    user_type  VARCHAR(100) NOT NULL,
    phone      VARCHAR(20)  NOT NULL,
    created_at TIMESTAMP    NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------
-- Disponibilidad institucional
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS institution_availability (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    day_of_week VARCHAR(20)  NOT NULL,
    start_time  TIME         NULL,
    end_time    TIME         NULL,
    enabled     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NULL,
    updated_at  TIMESTAMP    NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------
-- Bloqueos institucionales
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS institution_blocks (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date          DATE         NOT NULL,
    reason        VARCHAR(255) NULL,
    full_day      TINYINT(1)   NOT NULL DEFAULT 1,
    start_time    TIME         NULL,
    end_time      TIME         NULL,
    created_by_id BIGINT UNSIGNED NULL,
    created_at    TIMESTAMP    NULL,
    updated_at    TIMESTAMP    NULL,
    CONSTRAINT fk_blocks_user FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------
-- Citas médicas
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS appointments (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accepted_by_id       BIGINT UNSIGNED NULL,
    patient_id           BIGINT UNSIGNED NOT NULL,
    companion_id         BIGINT UNSIGNED NULL,
    attention_area       VARCHAR(50)  NOT NULL,
    reason               TEXT         NULL,
    appointment_datetime DATETIME     NOT NULL,
    status               VARCHAR(20)  NOT NULL DEFAULT 'pending',
    cancellation_reason  TEXT         NULL,
    is_guest             TINYINT(1)   NOT NULL DEFAULT 0,
    confirmation_token_hash       CHAR(64)  NULL,
    confirmation_token_expires_at TIMESTAMP NULL,
    created_at           TIMESTAMP    NULL,
    updated_at           TIMESTAMP    NULL,
    CONSTRAINT fk_appt_patient   FOREIGN KEY (patient_id)    REFERENCES patients   (id),
    CONSTRAINT fk_appt_accepted  FOREIGN KEY (accepted_by_id)REFERENCES users      (id) ON DELETE SET NULL,
    CONSTRAINT fk_appt_companion FOREIGN KEY (companion_id)  REFERENCES companions (id) ON DELETE SET NULL,
    INDEX idx_appt_confirmation_token (confirmation_token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------
-- Notificaciones
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        BIGINT UNSIGNED NOT NULL,
    appointment_id BIGINT UNSIGNED NULL,
    message        TEXT         NOT NULL,
    ui_status      VARCHAR(20)  NOT NULL DEFAULT 'unread',
    sent_at        TIMESTAMP    NULL,
    read_at        TIMESTAMP    NULL,
    created_at     TIMESTAMP    NULL,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id)        REFERENCES users        (id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_appt FOREIGN KEY (appointment_id) REFERENCES appointments (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------
-- Logs de auditoría
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS log_patients (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    performed_by_id   BIGINT UNSIGNED NULL,
    target_patient_id BIGINT UNSIGNED NULL,
    action            VARCHAR(30) NOT NULL,
    changes           TEXT        NULL,
    timestamp         TIMESTAMP   NULL,
    INDEX idx_lp_target (target_patient_id),
    INDEX idx_lp_actor  (performed_by_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS log_appointments (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    performed_by_id       BIGINT UNSIGNED NULL,
    target_appointment_id BIGINT UNSIGNED NULL,
    action                VARCHAR(30) NOT NULL,
    changes               TEXT        NULL,
    timestamp             TIMESTAMP   NULL,
    INDEX idx_la_target (target_appointment_id),
    INDEX idx_la_actor  (performed_by_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS log_users (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    performed_by_id BIGINT UNSIGNED NULL,
    target_user_id  BIGINT UNSIGNED NULL,
    action          VARCHAR(30) NOT NULL,
    changes         TEXT        NULL,
    timestamp       TIMESTAMP   NULL,
    INDEX idx_lu_target (target_user_id),
    INDEX idx_lu_actor  (performed_by_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS log_roles (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    performed_by_id BIGINT UNSIGNED NULL,
    target_role_id  BIGINT UNSIGNED NULL,
    action          VARCHAR(30) NOT NULL,
    changes         TEXT        NULL,
    timestamp       TIMESTAMP   NULL,
    INDEX idx_lr_target (target_role_id),
    INDEX idx_lr_actor  (performed_by_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log institucional (polimórfico: disponibilidad y bloqueos)
CREATE TABLE IF NOT EXISTS log_institution (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    performed_by_id BIGINT UNSIGNED NULL,
    target_type     VARCHAR(50)  NOT NULL,
    target_id       BIGINT UNSIGNED NOT NULL,
    action          VARCHAR(30)  NOT NULL,
    changes         TEXT         NULL,
    timestamp       TIMESTAMP    NULL,
    INDEX idx_li_target (target_type, target_id),
    INDEX idx_li_actor  (performed_by_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------
-- Seed de permisos del sistema
-- -------------------------------------------------------------------
INSERT IGNORE INTO permissions (name, created_at) VALUES
('users.read',             NOW()),('users.create',           NOW()),
('users.update',           NOW()),('users.delete',           NOW()),
('roles.read',             NOW()),('roles.create',           NOW()),
('roles.update',           NOW()),('roles.delete',           NOW()),
('permissions.read',       NOW()),('permissions.update',     NOW()),
('patients.read',          NOW()),('patients.create',        NOW()),
('patients.update',        NOW()),('patients.delete',        NOW()),
('companions.read',        NOW()),('companions.create',      NOW()),
('companions.update',      NOW()),('companions.delete',      NOW()),
('appointments.read',      NOW()),('appointments.create',    NOW()),
('appointments.update',    NOW()),('appointments.delete',    NOW()),
('availability.create',    NOW()),('availability.update',    NOW()),
('availability.delete',    NOW()),
('blocks.create',          NOW()),('blocks.update',          NOW()),
('blocks.delete',          NOW()),
('notifications.read',     NOW()),('notifications.update',   NOW()),
('notifications.delete',   NOW()),
('logs.users',             NOW()),('logs.patients',          NOW()),
('logs.appointments',      NOW()),('logs.institution',       NOW());

-- -------------------------------------------------------------------
-- Seed de roles del sistema
-- -------------------------------------------------------------------
INSERT IGNORE INTO roles (name, created_at) VALUES
('super-admin', NOW()),
('admin',       NOW()),
('doctor',      NOW()),
('patient',     NOW());
