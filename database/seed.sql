-- Datos de ejemplo equivalentes a los seeders originales de Laravel.
-- El orden de inserción preserva los IDs auto-incrementales referenciados en los comentarios.

-- users_types: 1=Administrador, 2=Medico, 3=Enfermero, 4=Paciente
INSERT INTO users_types (name, created_at, updated_at) VALUES
    ('Administrador', NOW(), NOW()),
    ('Medico', NOW(), NOW()),
    ('Enfermero', NOW(), NOW()),
    ('Paciente', NOW(), NOW());

-- users: 1=Carlos(Admin), 2=Laura(Medico), 3=Andres(Medico), 4=Maria(Enfermero)
-- Contraseñas en texto plano (antes de hash): Admin1234!, Doctor1234!, Doctor1234!, Nurse1234!
INSERT INTO users (name, lastname, email, password, user_type_id, created_at, updated_at) VALUES
    ('Carlos', 'Ramírez', 'admin@ccss.cr', '$2y$12$zX2tnpWM6YRRChHi320D9eP9l4VcFyAVad3OPYnIFonnRLfuCmU6e', 1, NOW(), NOW()),
    ('Laura', 'Soto', 'doctor1@ccss.cr', '$2y$12$GN2Ot1/jViOJwdX.HKX0KOXGF5LN8GoVxMAO/AJX68.2BHVBvLRXO', 2, NOW(), NOW()),
    ('Andrés', 'Mora', 'doctor2@ccss.cr', '$2y$12$lNwWi36qKwc2mT.yCgCcKewbBRLiJuSXlbWiXR8MJiBq/v/8N3Tsu', 2, NOW(), NOW()),
    ('María', 'González', 'nurse1@ccss.cr', '$2y$12$MmOjUoEzP8/4voVmouasmO7Jf1ybXmpnScxwOkjSlNgFPaxb3GlA6', 3, NOW(), NOW());

-- priority: 1=Baja, 2=Media, 3=Alta, 4=Critica
INSERT INTO priority (name, created_at, updated_at) VALUES
    ('Baja', NOW(), NOW()),
    ('Media', NOW(), NOW()),
    ('Alta', NOW(), NOW()),
    ('Critica', NOW(), NOW());

-- disease: 1=Resfriado comun, 2=Influenza, 3=Diabetes tipo 2, 4=Hipertension, 5=Asma, 6=Infarto de miocardio
INSERT INTO disease (name, technincal_name, description, priority_id, created_at, updated_at) VALUES
    ('Resfriado comun', 'Infeccion por rinovirus', 'Infeccion viral del tracto respiratorio superior.', 1, NOW(), NOW()),
    ('Influenza', 'Virus de la influenza', 'Enfermedad respiratoria altamente contagiosa causada por virus de influenza.', 2, NOW(), NOW()),
    ('Diabetes tipo 2', 'Diabetes mellitus tipo 2', 'Condicion cronica que afecta el metabolismo de la glucosa.', 3, NOW(), NOW()),
    ('Hipertension', 'Hipertension arterial', 'Presion arterial persistentemente elevada en las arterias.', 3, NOW(), NOW()),
    ('Asthma', 'Asma bronquial', 'Condicion inflamatoria cronica de las vias respiratorias.', 2, NOW(), NOW()),
    ('Infarto de miocardio', 'IAM agudo', 'Bloqueo del flujo sanguineo hacia una parte del musculo cardiaco.', 4, NOW(), NOW());

-- drugs: 1=Paracetamol, 2=Ibuprofen, 3=Amoxicillin, 4=Salbutamol, 5=Omeprazole, 6=Metformin, 7=Diphenhydramine, 8=Insulin
INSERT INTO drugs (name, description, type, created_at, updated_at) VALUES
    ('Paracetamol', 'Analgesico y antipiretico utilizado para tratar dolor y fiebre.', 'tablet', NOW(), NOW()),
    ('Ibuprofen', 'AINE utilizado para dolor, fiebre e inflamacion.', 'tablet', NOW(), NOW()),
    ('Amoxicillin', 'Antibiotico de amplio espectro para infecciones bacterianas.', 'capsule', NOW(), NOW()),
    ('Salbutamol', 'Broncodilatador utilizado para tratar asma y EPOC.', 'other', NOW(), NOW()),
    ('Omeprazole', 'Inhibidor de bomba de protones para reducir acido gastrico.', 'capsule', NOW(), NOW()),
    ('Metformin', 'Antidiabetico oral para el manejo de diabetes tipo 2.', 'tablet', NOW(), NOW()),
    ('Diphenhydramine', 'Antihistaminico utilizado para alergias y ayuda para dormir.', 'syrup', NOW(), NOW()),
    ('Insulin', 'Hormona utilizada para controlar la glucosa en sangre en diabetes.', 'injection', NOW(), NOW());

-- patient: 1=Juan, 2=Ana, 3=Roberto, 4=Sofia, 5=Luis
-- 'suffering' guarda el id de disease como texto (columna heredada del proyecto original).
INSERT INTO patient (name, lastname, nick, suffering, register_by, created_at, updated_at) VALUES
    ('Juan', 'Pérez', 'juanp', '3', 2, NOW(), NOW()),
    ('Ana', 'Vargas', 'anav', '5', 2, NOW(), NOW()),
    ('Roberto', 'Jiménez', 'robj', '4', 3, NOW(), NOW()),
    ('Sofía', 'Brenes', 'sofb', '2', 3, NOW(), NOW()),
    ('Luis', 'Castro', 'luisc', '6', 2, NOW(), NOW());

-- diagnoses: 1=eval diabetes, 2=seguimiento asma, 3=control hipertension, 4=influenza, 5=post infarto
INSERT INTO diagnoses (name, disease_id, patient_id, diagnoses_by, created_at, updated_at) VALUES
    ('Evaluacion inicial de diabetes', 3, 1, 2, NOW(), NOW()),
    ('Seguimiento de asma', 5, 2, 2, NOW(), NOW()),
    ('Control de hipertension', 4, 3, 3, NOW(), NOW()),
    ('Episodio agudo de influenza', 2, 4, 3, NOW(), NOW()),
    ('Valoracion cardiaca post infarto', 6, 5, 2, NOW(), NOW());

-- disease_has_treatments
INSERT INTO disease_has_treatments (disease_id, drugs, descriptions, created_at, updated_at) VALUES
    (1, 1, 'Paracetamol 500mg cada 8h para reducir fiebre y dolor.', NOW(), NOW()),
    (1, 7, 'Diphenhydramine para aliviar congestion nasal y sintomas alergicos.', NOW(), NOW()),
    (2, 1, 'Paracetamol 500mg cada 6h para manejo de fiebre.', NOW(), NOW()),
    (2, 2, 'Ibuprofen 400mg cada 8h para dolor corporal.', NOW(), NOW()),
    (3, 6, 'Metformin 850mg dos veces al dia con comidas.', NOW(), NOW()),
    (3, 8, 'Insulin segun necesidad con base en monitoreo de glucosa.', NOW(), NOW()),
    (4, 2, 'Ibuprofen evitado; documentar como tratamiento contraindicado.', NOW(), NOW()),
    (5, 4, 'Inhalador de Salbutamol 2 puffs segun necesidad durante crisis.', NOW(), NOW()),
    (6, 2, 'Aspirina en dosis baja (grupo Ibuprofen) para terapia antiplaquetaria.', NOW(), NOW()),
    (6, 5, 'Omeprazole 20mg para proteger mucosa gastrica con antiplaquetarios.', NOW(), NOW());

-- diagnoses_has_treatments
INSERT INTO diagnoses_has_treatments (diagnoses_id, drugs, descriptions, created_at, updated_at) VALUES
    (1, 6, 'Metformin 850mg dos veces al dia con comidas para control glucemico.', NOW(), NOW()),
    (1, 8, 'Insulin 10 UI subcutanea al acostarse.', NOW(), NOW()),
    (2, 4, 'Salbutamol 2 puffs cada 4-6h segun necesidad por broncoespasmo.', NOW(), NOW()),
    (3, 5, 'Omeprazole 20mg diario como proteccion gastrica.', NOW(), NOW()),
    (4, 1, 'Paracetamol 500mg cada 6h para fiebre y malestar.', NOW(), NOW()),
    (4, 2, 'Ibuprofen 400mg cada 8h para aliviar dolor muscular.', NOW(), NOW()),
    (5, 2, 'Ibuprofen dosis baja 100mg diario como terapia antiplaquetaria.', NOW(), NOW()),
    (5, 5, 'Omeprazole 20mg diario para proteger mucosa gastrica.', NOW(), NOW());

-- ---------------------------------------------------------------------------
-- Permissions catalog (ported from App\Support\PermissionCatalog).
-- Modules x actions (read/create/update/delete) + read-only log permissions.
-- ---------------------------------------------------------------------------
INSERT INTO permissions (name, created_at, updated_at) VALUES
    ('users.read', NOW(), NOW()),       ('users.create', NOW(), NOW()),       ('users.update', NOW(), NOW()),       ('users.delete', NOW(), NOW()),
    ('roles.read', NOW(), NOW()),       ('roles.create', NOW(), NOW()),       ('roles.update', NOW(), NOW()),       ('roles.delete', NOW(), NOW()),
    ('patients.read', NOW(), NOW()),    ('patients.create', NOW(), NOW()),    ('patients.update', NOW(), NOW()),    ('patients.delete', NOW(), NOW()),
    ('diagnoses.read', NOW(), NOW()),   ('diagnoses.create', NOW(), NOW()),   ('diagnoses.update', NOW(), NOW()),   ('diagnoses.delete', NOW(), NOW()),
    ('diseases.read', NOW(), NOW()),    ('diseases.create', NOW(), NOW()),    ('diseases.update', NOW(), NOW()),    ('diseases.delete', NOW(), NOW()),
    ('drugs.read', NOW(), NOW()),       ('drugs.create', NOW(), NOW()),       ('drugs.update', NOW(), NOW()),       ('drugs.delete', NOW(), NOW()),
    ('priorities.read', NOW(), NOW()),  ('priorities.create', NOW(), NOW()),  ('priorities.update', NOW(), NOW()),  ('priorities.delete', NOW(), NOW()),
    ('treatments.read', NOW(), NOW()),  ('treatments.create', NOW(), NOW()),  ('treatments.update', NOW(), NOW()),  ('treatments.delete', NOW(), NOW()),
    ('logs_users.read', NOW(), NOW()),  ('logs_roles.read', NOW(), NOW());

-- ---------------------------------------------------------------------------
-- Role -> permission assignments (users_types = roles). Built by pattern to
-- mirror the Laravel RolePermissionSeeder; the read/write cascade is implicit
-- because every module already has its .read inserted above.
-- ---------------------------------------------------------------------------

-- Administrador (id 1): ALL permissions.
INSERT INTO usertype_has_permissions (user_type_id, permission_id)
    SELECT 1, id FROM permissions;

-- Medico (id 2): everything EXCEPT users.*, roles.* and logs_*.
INSERT INTO usertype_has_permissions (user_type_id, permission_id)
    SELECT 2, id FROM permissions
    WHERE name NOT LIKE 'users.%'
      AND name NOT LIKE 'roles.%'
      AND name NOT LIKE 'logs\_%';

-- Enfermero (id 3): patients.read; diagnoses.read+create; diseases.read.
INSERT INTO usertype_has_permissions (user_type_id, permission_id)
    SELECT 3, id FROM permissions
    WHERE name IN ('patients.read', 'diagnoses.read', 'diagnoses.create', 'diseases.read');

-- Paciente (id 4): read-only on patients, diagnoses, diseases.
INSERT INTO usertype_has_permissions (user_type_id, permission_id)
    SELECT 4, id FROM permissions
    WHERE name IN ('patients.read', 'diagnoses.read', 'diseases.read');

-- ---------------------------------------------------------------------------
-- user_has_roles: seed each user with their current user_type_id as the only
-- role (the primary one). Multi-role assignments are added via the API.
-- ---------------------------------------------------------------------------
INSERT INTO user_has_roles (user_id, user_type_id)
    SELECT id, user_type_id FROM users;
