-- ====================================================================
-- SEEDER — Proyecto Multimedios Backend PHP
-- Alineado con MediCode-Backend (Laravel seeders)
--
-- Contraseña de todos los usuarios: Password123!
-- Ejecutar DESPUÉS de schema.sql
-- ====================================================================

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE log_institution;
TRUNCATE TABLE log_roles;
TRUNCATE TABLE log_users;
TRUNCATE TABLE log_appointments;
TRUNCATE TABLE log_patients;
TRUNCATE TABLE notifications;
TRUNCATE TABLE appointments;
TRUNCATE TABLE patients;
TRUNCATE TABLE companions;
TRUNCATE TABLE institution_blocks;
TRUNCATE TABLE institution_availability;
TRUNCATE TABLE password_reset_tokens;
TRUNCATE TABLE tokens;
TRUNCATE TABLE user_has_permissions;
TRUNCATE TABLE user_has_roles;
TRUNCATE TABLE role_has_permissions;
TRUNCATE TABLE users;
TRUNCATE TABLE roles;
TRUNCATE TABLE permissions;

SET FOREIGN_KEY_CHECKS = 1;

-- ====================================================================
-- PERMISOS  (mirrors RolesAndPermissionsSeeder)
-- Módulos con CRUD completo
-- ====================================================================
INSERT INTO permissions (name, created_at) VALUES
('users.create',          NOW()), ('users.read',          NOW()),
('users.update',          NOW()), ('users.delete',         NOW()),
('roles.create',          NOW()), ('roles.read',           NOW()),
('roles.update',          NOW()), ('roles.delete',         NOW()),
('permissions.create',    NOW()), ('permissions.read',     NOW()),
('permissions.update',    NOW()), ('permissions.delete',   NOW()),
('patients.create',       NOW()), ('patients.read',        NOW()),
('patients.update',       NOW()), ('patients.delete',      NOW()),
('companions.create',     NOW()), ('companions.read',      NOW()),
('companions.update',     NOW()), ('companions.delete',    NOW()),
('appointments.create',   NOW()), ('appointments.read',    NOW()),
('appointments.update',   NOW()), ('appointments.delete',  NOW()),
('availability.create',   NOW()), ('availability.read',    NOW()),
('availability.update',   NOW()), ('availability.delete',  NOW()),
('blocks.create',         NOW()), ('blocks.read',          NOW()),
('blocks.update',         NOW()), ('blocks.delete',        NOW()),
('notifications.create',  NOW()), ('notifications.read',   NOW()),
('notifications.update',  NOW()), ('notifications.delete', NOW()),
-- Logs: solo lectura
('logs.users',       NOW()),
('logs.patients',    NOW()),
('logs.appointments',NOW()),
('logs.institution', NOW());

-- ====================================================================
-- ROLES  (mirrors RolesAndPermissionsSeeder)
-- NOTA: admin=1, super-admin=2 para que adminOwnRoleId=1 coincida con
--       el rol del usuario de pruebas (admin@ucr.ac.cr)
-- ====================================================================
INSERT INTO roles (name, created_at) VALUES
('admin',       NOW()),   -- id 1  ← rol del usuario de pruebas
('super-admin', NOW()),   -- id 2
('doctor',      NOW()),   -- id 3
('nurse',       NOW()),   -- id 4
('patient',     NOW());   -- id 5

-- ====================================================================
-- PERMISOS POR ROL
-- ====================================================================

-- admin (id=1) y super-admin (id=2): TODOS los permisos
INSERT INTO role_has_permissions (role_id, permission_id) SELECT 1, id FROM permissions;
INSERT INTO role_has_permissions (role_id, permission_id) SELECT 2, id FROM permissions;

-- doctor (id=3) y nurse (id=4): todo EXCEPTO users.*, roles.*, permissions.*, logs.users
INSERT INTO role_has_permissions (role_id, permission_id)
SELECT 3, id FROM permissions
WHERE name NOT LIKE 'users.%'
  AND name NOT LIKE 'roles.%'
  AND name NOT LIKE 'permissions.%'
  AND name != 'logs.users';

INSERT INTO role_has_permissions (role_id, permission_id)
SELECT 4, id FROM permissions
WHERE name NOT LIKE 'users.%'
  AND name NOT LIKE 'roles.%'
  AND name NOT LIKE 'permissions.%'
  AND name != 'logs.users';

-- patient (id=5): solo appointments.read
INSERT INTO role_has_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE name = 'appointments.read';

-- ====================================================================
-- USUARIOS  (mirrors UserSeeder)
-- Contraseña: Password123!
-- Hash bcrypt cost=12
--
-- IMPORTANTE: admin@ucr.ac.cr es user_id=1 con role_id=1 (admin)
-- Esto coincide con adminSelfId=1, adminOwnRoleId=1, lastAdminId=1
-- de la colección Postman. Es el ÚNICO usuario con rol admin.
-- ====================================================================
SET @pw = '$2y$12$JkLh0ugQcrElphhEp9IjIOlyEg.4eV9nKAAOBZwIVSrgx96K5/G3S';

INSERT INTO users (name, identifier, email, email_verified_at, password, status, is_guest, created_at, updated_at) VALUES
-- id=1: usuario de pruebas (adminSelfId=1 en Postman)
('System Administrator',       'ADMIN-001', 'admin@ucr.ac.cr',            NOW(), @pw, 'active',   0, NOW(), NOW()),
-- id=2: super-admin del sistema
('System Super Administrator', 'SUPER-001', 'superadmin@ucr.ac.cr',       NOW(), @pw, 'active',   0, NOW(), NOW()),
-- id=3..5: personal médico
('General Doctor',             'DOC-001',   'doctor@ucr.ac.cr',           NOW(), @pw, 'active',   0, NOW(), NOW()),
('General Nurse',              'NUR-001',   'nurse@ucr.ac.cr',            NOW(), @pw, 'active',   0, NOW(), NOW()),
('Juan Pérez',                 'AUX-001',   'juan.perez@ucr.ac.cr',       NOW(), @pw, 'active',   0, NOW(), NOW()),
-- id=6..7: usuarios paciente para tests
('María García López',         'PAC-001',   'maria.garcia@ucr.ac.cr',     NOW(), @pw, 'active',   0, NOW(), NOW()),
('Carlos Rodríguez Mora',      'PAC-002',   'carlos.rodriguez@ucr.ac.cr', NOW(), @pw, 'active',   0, NOW(), NOW()),
-- id=8: usuario inactivo para test de login
('Diego Torres Alfaro',        'PAC-003',   'diego.torres@ucr.ac.cr',     NULL,  @pw, 'inactive', 0, NOW(), NOW());

-- Asignación de roles
-- NOTA: admin (role_id=1) solo asignado a user_id=1 (último admin activo → 409 en tests)
INSERT INTO user_has_roles (user_id, role_id) VALUES
(1, 1),  -- admin@ucr.ac.cr  → admin       (adminOwnRoleId=1)
(2, 2),  -- superadmin       → super-admin
(3, 3),  -- doctor           → doctor
(4, 4),  -- nurse            → nurse
(5, 3),  -- juan.perez       → doctor (ya no es admin)
(6, 5),  -- maria.garcia     → patient
(7, 5),  -- carlos           → patient
(8, 5);  -- diego            → patient

-- ====================================================================
-- ACOMPAÑANTES  (mirrors CompanionSeeder — 5 registros)
-- ====================================================================
INSERT INTO companions (full_name, identifier, phone, created_at) VALUES
('Rosa Jiménez Blanco',   '1-0234-5678', '8812-3456', NOW()),
('Marco Solano Vega',     '2-1234-5678', '7723-4567', NOW()),
('Elena Brenes Araya',    '3-2345-6789', '6634-5678', NOW()),
('Fernando Ulate Mora',   '4-3456-7890', '8845-6789', NOW()),
('Alicia Vargas Castro',  '1-4567-8901', '7756-7890', NOW());

-- ====================================================================
-- PACIENTES  (mirrors PatientSeeder — 10 registros)
-- ====================================================================
INSERT INTO patients (full_name, identifier, email, birth_date, user_type, phone, created_at) VALUES
('Roberto Méndez Fallas',   '1-0112-3456', 'roberto.mendez@ucr.ac.cr',    '1990-05-14', 'Estudiante',    '8801-2345', NOW()),
('Sofía Vargas Ulate',      '2-0223-4567', 'sofia.vargas@ucr.ac.cr',      '1985-09-22', 'Funcionario',   '7702-3456', NOW()),
('Pablo Herrera Mora',      '3-0334-5678', 'pablo.herrera@ucr.ac.cr',     '2001-02-08', 'Estudiante',    '6603-4567', NOW()),
('Carmen Arias Rodríguez',  '4-0445-6789', 'carmen.arias@ucr.ac.cr',      '1978-11-30', 'Funcionario',   '8804-5678', NOW()),
('Diego López Castro',      '1-0556-7890', 'diego.lopez@ucr.ac.cr',       '1995-07-19', 'Estudiante',    '7705-6789', NOW()),
('Ana Jiménez Solís',       '2-0667-8901', 'ana.jimenez@ucr.ac.cr',       '1988-03-25', 'Administrativo','6606-7890', NOW()),
('Luis Fernández Núñez',    '3-0778-9012', 'luis.fernandez@ucr.ac.cr',    '1992-12-03', 'Estudiante',    '8807-8901', NOW()),
('Patricia Mora Alfaro',    '4-0889-0123', 'patricia.mora@ucr.ac.cr',     '1983-08-17', 'Funcionario',   '7708-9012', NOW()),
('Andrés Rojas Quesada',    '1-0990-1234', 'andres.rojas@ucr.ac.cr',      '1999-04-29', 'Estudiante',    '6609-0123', NOW()),
('Valentina Cruz Benavides','2-1001-2345', 'valentina.cruz@ucr.ac.cr',    '1975-01-11', 'Administrativo','8810-1234', NOW());

-- ====================================================================
-- DISPONIBILIDAD INSTITUCIONAL
-- NOTA: Tabla vacía al inicio para que el test "Crear slot (201)"
--       pueda crear monday 08:00-17:00 sin conflicto de solapamiento.
--       El test "Crear slot - solapamiento (422)" creará el segundo
--       slot monday repetido para verificar el 422.
-- ====================================================================
-- (sin datos iniciales)

-- ====================================================================
-- BLOQUEOS INSTITUCIONALES
-- NOTA: Solo feriados fijos (pasados en el año actual ya ocurrieron,
--       el test usa +30 días que no colisiona con estas fechas fijas).
--       NO se insertan bloqueos en +20/+35/+50 días para que el test
--       "Crear bloqueo día completo" con blockDate=+30 días no colisione.
-- ====================================================================
SET @yr = YEAR(CURDATE());

INSERT INTO institution_blocks (date, reason, full_day, start_time, end_time, created_by_id, created_at, updated_at) VALUES
-- Feriados nacionales pasados (no colisionan con los tests futuros)
(CONCAT(@yr, '-01-01'), 'New Year''s Day',    1, NULL, NULL, 1, NOW(), NOW()),
(CONCAT(@yr, '-05-01'), 'Labour Day',         1, NULL, NULL, 1, NOW(), NOW()),
(CONCAT(@yr, '-09-15'), 'Independence Day',   1, NULL, NULL, 1, NOW(), NOW()),
(CONCAT(@yr, '-12-25'), 'Christmas Day',      1, NULL, NULL, 1, NOW(), NOW());

-- ====================================================================
-- CITAS  (mirrors AppointmentSeeder)
-- 5 pendientes + 1 aprobada para el doctor + 2 con estados terminales
-- admin@ucr.ac.cr = user_id=1, doctor = user_id=3
-- ====================================================================
INSERT INTO appointments
    (accepted_by_id, patient_id, companion_id, attention_area, reason, appointment_datetime, status, cancellation_reason, is_guest, created_at, updated_at)
VALUES
-- 5 pendientes (mirrors AppointmentSeeder loop i=1..5)
(NULL, 1, NULL,    'general_medicine', 'Dolor de cabeza frecuente y fatiga generalizada.',
    DATE_ADD(NOW(), INTERVAL 5 DAY),  'pending', NULL, 0, NOW(), NOW()),
(NULL, 2, 1,       'cardiology',       'Palpitaciones irregulares desde hace dos semanas.',
    DATE_ADD(NOW(), INTERVAL 8 DAY),  'pending', NULL, 0, NOW(), NOW()),
(NULL, 3, NULL,    'pediatrics',       'Control de crecimiento anual y revisión de vacunas.',
    DATE_ADD(NOW(), INTERVAL 10 DAY), 'pending', NULL, 0, NOW(), NOW()),
(NULL, 4, 2,       'dermatology',      'Revisión de manchas en piel lado derecho del cuello.',
    DATE_ADD(NOW(), INTERVAL 12 DAY), 'pending', NULL, 0, NOW(), NOW()),
(NULL, 5, NULL,    'psychology',       'Sesión de seguimiento por cuadro de ansiedad.',
    DATE_ADD(NOW(), INTERVAL 15 DAY), 'pending', NULL, 0, NOW(), NOW()),
-- 1 aprobada asignada al doctor (doctor = user_id=3)
(3,    6, NULL,    'cardiology',       'Evaluación cardiológica de rutina post-tratamiento.',
    DATE_ADD(NOW(), INTERVAL 3 DAY),  'approved', NULL, 0, NOW(), NOW()),
-- Adicionales con estados terminales para tests de flujo completo
(1,    7, 3,       'ophthalmology',    'Control anual de visión, posible miopía progresiva.',
    DATE_ADD(NOW(), INTERVAL 2 DAY),  'confirmed', NULL, 0, NOW(), NOW()),
(1,    8, NULL,    'orthopedics',      'Dolor persistente en rodilla izquierda post-lesión.',
    DATE_ADD(NOW(), INTERVAL -5 DAY), 'rejected',
    'No se adjuntaron los estudios de imagen solicitados.', 0, DATE_ADD(NOW(), INTERVAL -8 DAY), NOW()),
(1,    9, NULL,    'neurology',        'Mareos frecuentes al levantarse, posible vértigo.',
    DATE_ADD(NOW(), INTERVAL -3 DAY), 'canceled',
    'El paciente solicitó cancelación por motivos personales.', 0, DATE_ADD(NOW(), INTERVAL -6 DAY), NOW()),
(1,   10, 4,       'dentistry',        'Revisión dental semestral y limpieza profesional.',
    DATE_ADD(NOW(), INTERVAL 7 DAY),  'pending', NULL, 0, NOW(), NOW());

-- ====================================================================
-- NOTIFICACIONES  (mirrors NotificationSeeder)
-- user_id=1 = admin, user_id=6 = maria.garcia, user_id=7 = carlos
-- ====================================================================
INSERT INTO notifications (user_id, appointment_id, message, ui_status, sent_at, read_at, created_at) VALUES
-- Pacientes notificados de sus citas
(6, 6, 'Tu cita de cardiología fue aprobada. Recuerda llegar 10 minutos antes.',     'unread', NOW(), NULL,  NOW()),
(6, 7, 'Tu cita de oftalmología está confirmada para el próximo lunes.',              'read',   NOW(), NOW(), NOW()),
(7, 8, 'Tu cita de ortopedia fue rechazada. Adjunta los estudios de imagen requeridos.','unread',NOW(),NULL, NOW()),
(7, 9, 'Tu cita de neurología fue cancelada a tu solicitud.',                         'read',   NOW(), NOW(), NOW()),
-- Admin notificado de nuevas citas pendientes
(1, 1, 'Nueva cita pendiente — Roberto Méndez (medicina general).',                  'unread', NOW(), NULL,  NOW()),
(1, 2, 'Nueva cita pendiente — Sofía Vargas (cardiología).',                         'unread', NOW(), NULL,  NOW()),
(1, 3, 'Nueva cita pendiente de aprobación — Pablo Herrera (pediatría).',            'read',   NOW(), NOW(), NOW());

-- ====================================================================
-- LOGS DE AUDITORÍA (muestra mínima para que los endpoints devuelvan datos)
-- ====================================================================
INSERT INTO log_patients (performed_by_id, target_patient_id, action, changes, timestamp) VALUES
(1, 1, 'Create', '{"full_name":{"from":null,"to":"Roberto Méndez Fallas"}}', NOW()),
(1, 2, 'Create', '{"full_name":{"from":null,"to":"Sofía Vargas Ulate"}}',    NOW()),
(1, 3, 'Create', '{"full_name":{"from":null,"to":"Pablo Herrera Mora"}}',    NOW()),
(1, 4, 'Create', '{"full_name":{"from":null,"to":"Carmen Arias Rodríguez"}}',NOW()),
(1, 5, 'Create', '{"full_name":{"from":null,"to":"Diego López Castro"}}',    NOW());

INSERT INTO log_appointments (performed_by_id, target_appointment_id, action, changes, timestamp) VALUES
(1, 1,  'Create',       NULL,                                                          NOW()),
(3, 6,  'ChangeStatus', '{"status":{"from":"pending","to":"approved"}}',               NOW()),
(1, 7,  'ChangeStatus', '{"status":{"from":"approved","to":"confirmed"}}',             NOW()),
(1, 8,  'ChangeStatus', '{"status":{"from":"pending","to":"rejected"}}',               NOW()),
(1, 9,  'ChangeStatus', '{"status":{"from":"approved","to":"canceled"}}',              NOW()),
(1, 10, 'Create',       NULL,                                                          NOW());

INSERT INTO log_users (performed_by_id, target_user_id, action, changes, timestamp) VALUES
(1, 2, 'Create',     '{"email":{"from":null,"to":"superadmin@ucr.ac.cr"}}',   NOW()),
(1, 3, 'Create',     '{"email":{"from":null,"to":"doctor@ucr.ac.cr"}}',       NOW()),
(1, 4, 'Create',     '{"email":{"from":null,"to":"nurse@ucr.ac.cr"}}',        NOW()),
(1, 5, 'Create',     '{"email":{"from":null,"to":"juan.perez@ucr.ac.cr"}}',   NOW()),
(1, 8, 'Deactivate', '{"status":{"from":"active","to":"inactive"}}',          NOW());

INSERT INTO log_roles (performed_by_id, target_role_id, action, changes, timestamp) VALUES
(1, 3, 'Update', '{"permissions":{"added":["appointments.create","appointments.update"]}}', NOW()),
(1, 5, 'Update', '{"permissions":{"set":["appointments.read"]}}',                          NOW());

INSERT INTO log_institution (performed_by_id, target_type, target_id, action, changes, timestamp) VALUES
(1, 'block', 1, 'Create', '{"date":"New Year''s Day","full_day":true}',  NOW()),
(1, 'block', 4, 'Create', '{"date":"Christmas Day","full_day":true}',    NOW());

-- ====================================================================
-- FIN DEL SEEDER
--
-- Usuarios disponibles:
--   admin@ucr.ac.cr       → admin      (id=1) ← usuario de pruebas Postman
--   superadmin@ucr.ac.cr  → super-admin (id=2)
--   doctor@ucr.ac.cr      → doctor     (id=3)
--   nurse@ucr.ac.cr       → nurse      (id=4)
--   juan.perez@ucr.ac.cr  → doctor     (id=5)
--   maria.garcia@ucr.ac.cr→ patient    (id=6) solo appointments.read
--   diego.torres@ucr.ac.cr→ patient    (INACTIVE — para test de login)
--
-- Roles:
--   id=1: admin      ← adminOwnRoleId=1 en Postman
--   id=2: super-admin
--   id=3: doctor
--   id=4: nurse
--   id=5: patient
--
-- Variables Postman cumplidas:
--   adminSelfId=1    → admin@ucr.ac.cr es user_id=1
--   adminOwnRoleId=1 → su rol es admin (role_id=1)
--   lastAdminId=1    → es el ÚNICO usuario con rol admin
--
--   Contraseña de todos: Password123!
-- ====================================================================
