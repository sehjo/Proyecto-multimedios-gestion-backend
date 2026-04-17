# Data Dictionary - CCSS Consultory

Database for the medical consultory system. It manages users, patients, diseases, diagnoses, and treatments.

---

## Table: `users_types`

System user type catalog (e.g., administrator, doctor, nurse).

| Column | Type | Max chars | Null | Key | Description |
|--------|------|-----------|------|-----|-------------|
| id | BIGINT UNSIGNED | 20 digits | No | PK | Unique identifier |
| name | VARCHAR(255) | 255 | No |  | User type name |

---

## Table: `users`

Registered system users who can create diagnoses and register patients.

| Column | Type | Max chars | Null | Key | Description |
|--------|------|-----------|------|-----|-------------|
| id | BIGINT UNSIGNED | 20 digits | No | PK | Unique identifier |
| name | VARCHAR(255) | 255 | No |  | User first name |
| lastname | VARCHAR(255) | 255 | No |  | User last name |
| email | VARCHAR(255) | 255 | No | UQ | Unique email address |
| password | VARCHAR(255) | 255 | No |  | Encrypted password |
| user_type_id | BIGINT UNSIGNED | 20 digits | No | FK | Reference to `users_types.id` |

**Relationships:**
- `user_type_id` -> `users_types.id`

---

## Table: `priority`

Catalog of priority levels that can be assigned to diseases.

| Column | Type | Max chars | Null | Key | Description |
|--------|------|-----------|------|-----|-------------|
| id | BIGINT UNSIGNED | 20 digits | No | PK | Unique identifier |
| name | VARCHAR(255) | 255 | No |  | Priority level name (e.g., High, Medium, Low) |

---

## Table: `drugs`

Catalog of drugs available in the system.

| Column | Type | Max chars | Null | Key | Description |
|--------|------|-----------|------|-----|-------------|
| id | BIGINT UNSIGNED | 20 digits | No | PK | Unique identifier |
| name | VARCHAR(255) | 255 | No |  | Drug name |
| description | VARCHAR(255) | 255 | No |  | Drug description |
| type | ENUM | 9 | No |  | Drug type (`tablet`, `capsule`, `syrup`, `injection`, `topical`, `other`) |

---

## Table: `disease`

Catalog of diseases registered in the system.

| Column | Type | Max chars | Null | Key | Description |
|--------|------|-----------|------|-----|-------------|
| id | BIGINT UNSIGNED | 20 digits | No | PK | Unique identifier |
| name | VARCHAR(255) | 255 | No |  | Common disease name |
| technincal_name | VARCHAR(255) | 255 | No |  | Technical/scientific disease name |
| description | VARCHAR(255) | 255 | No |  | Disease description |
| priority_id | BIGINT UNSIGNED | 20 digits | No | FK | Reference to `priority.id` |

**Relationships:**
- `priority_id` -> `priority.id`

---

## Table: `patient`

Patients registered in the system.

| Column | Type | Max chars | Null | Key | Description |
|--------|------|-----------|------|-----|-------------|
| id | BIGINT UNSIGNED | 20 digits | No | PK | Unique identifier |
| name | VARCHAR(255) | 255 | No |  | Patient first name |
| lastname | VARCHAR(255) | 255 | No |  | Patient last name |
| nick | VARCHAR(255) | 255 | No |  | Patient nickname or alias |
| suffering | BIGINT UNSIGNED | 20 digits | Yes | FK | Primary disease suffered (`disease.id`) |
| register_by | BIGINT UNSIGNED | 20 digits | Yes | FK | User who registered the patient (`users.id`) |

**Relationships:**
- `suffering` -> `disease.id`
- `register_by` -> `users.id`

---

## Table: `diagnoses`

Diagnoses performed on patients by system users.

| Column | Type | Max chars | Null | Key | Description |
|--------|------|-----------|------|-----|-------------|
| id | BIGINT UNSIGNED | 20 digits | No | PK | Unique identifier |
| name | VARCHAR(255) | 255 | No |  | Diagnosis name or title |
| disease_id | BIGINT UNSIGNED | 20 digits | Yes | FK | Diagnosed disease (`disease.id`) |
| patient_id | BIGINT UNSIGNED | 20 digits | No | FK | Diagnosed patient (`patient.id`) |
| diagnoses_by | BIGINT UNSIGNED | 20 digits | No | FK | User who performed the diagnosis (`users.id`) |

**Relationships:**
- `disease_id` -> `disease.id`
- `patient_id` -> `patient.id`
- `diagnoses_by` -> `users.id`

---

## Table: `diagnoses_has_treatments`

Treatments (drugs) assigned to a specific diagnosis.

| Column | Type | Max chars | Null | Key | Description |
|--------|------|-----------|------|-----|-------------|
| id | BIGINT UNSIGNED | 20 digits | No | PK | Unique identifier |
| diagnoses_id | BIGINT UNSIGNED | 20 digits | No | FK | Parent diagnosis (`diagnoses.id`) |
| drugs | BIGINT UNSIGNED | 20 digits | No | FK | Assigned drug (`drugs.id`) |
| descriptions | VARCHAR(255) | 255 | No |  | Treatment instructions or description |

**Relationships:**
- `diagnoses_id` -> `diagnoses.id`
- `drugs` -> `drugs.id`

---

## Table: `disease_has_treatments`

General recommended treatments for a disease.

| Column | Type | Max chars | Null | Key | Description |
|--------|------|-----------|------|-----|-------------|
| id | BIGINT UNSIGNED | 20 digits | No | PK | Unique identifier |
| descriptions | VARCHAR(255) | 255 | No |  | Recommended treatment description |
| disease_id | BIGINT UNSIGNED | 20 digits | Yes | FK | Related disease (`disease.id`) |
| drugs | BIGINT UNSIGNED | 20 digits | Yes | FK | Recommended drug (`drugs.id`) |

**Relationships:**
- `disease_id` -> `disease.id`
- `drugs` -> `drugs.id`

---

## Relationship Diagram (summary)

```
users_types <- users -> patient -> diagnoses -> diagnoses_has_treatments
                ^         ^          ^                    |
                |_________|       disease <- disease_has_treatments
                                     ^               |
                                  priority         drugs
```

---

## Notes

- All tables use an auto-increment `id` primary key (`BIGINT UNSIGNED`).
- Laravel `string()` columns default to `VARCHAR(255)` when no length is specified.
- The `drugs.type` field is an `ENUM` with values: `tablet`, `capsule`, `syrup`, `injection`, `topical`, `other`.
- `Max chars` for `ENUM` is shown as the longest allowed literal (`injection` = 9).
- `users`, `patient`, and `diagnoses` include traceability fields (`register_by`, `diagnoses_by`).
