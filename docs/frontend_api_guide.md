# CCSS Consultory — Frontend API Guide

This document describes how to consume the **CCSS Consultory** REST API from a React (JavaScript) frontend application.

---

## Table of Contents

1. [Overview](#overview)
2. [Base URL](#base-url)
3. [Authentication](#authentication)
4. [Pagination](#pagination)
5. [HTTP Methods & Status Codes](#http-methods--status-codes)
6. [Axios Setup](#axios-setup)
7. [Resources](#resources)
   - [User Types](#1-user-types)
   - [Users](#2-users)
   - [Priorities](#3-priorities)
   - [Drugs](#4-drugs)
   - [Diseases](#5-diseases)
   - [Patients](#6-patients)
   - [Diagnoses](#7-diagnoses)
   - [Diagnoses Has Treatments](#8-diagnoses-has-treatments)
   - [Disease Has Treatments](#9-disease-has-treatments)
8. [Data Dictionary](#data-dictionary)

---

## Overview

The backend is built with **Laravel** and exposes a fully RESTful JSON API. Every resource follows the same CRUD pattern:

| Action  | Method   | Path              |
|---------|----------|-------------------|
| List    | `GET`    | `/api/{resource}` |
| Create  | `POST`   | `/api/{resource}` |
| Show    | `GET`    | `/api/{resource}/{id}` |
| Update  | `PUT`    | `/api/{resource}/{id}` |
| Delete  | `DELETE` | `/api/{resource}/{id}` |

---

## Base URL

```
http://localhost:8000/api
```

> Replace `localhost:8000` with the actual server host/port in production.

---

## Authentication

The `/api/user` route is protected by **Laravel Sanctum** and requires a Bearer token. The remaining resource endpoints are currently open.

To include a token in requests:

```js
// Set the Authorization header globally in your axios instance
axiosInstance.defaults.headers.common['Authorization'] = `Bearer ${token}`;
```

> Store the token in memory or `sessionStorage`. Avoid `localStorage` for sensitive tokens.

---

## Pagination

All `index` (list) endpoints return paginated results using Laravel's default pagination.

**Response shape:**

```json
{
  "data": [ ...array of resource objects... ],
  "links": {
    "first": "http://localhost:8000/api/users?page=1",
    "last":  "http://localhost:8000/api/users?page=5",
    "prev":  null,
    "next":  "http://localhost:8000/api/users?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 72
  }
}
```

**Navigating pages:**

```js
const response = await api.get('/users', { params: { page: 2 } });
const { data, meta } = response.data;
```

---

## HTTP Methods & Status Codes

| Status | Meaning                            |
|--------|------------------------------------|
| `200`  | OK — successful GET / PUT          |
| `201`  | Created — successful POST (note: this API returns `200` on POST) |
| `204`  | No Content — successful DELETE     |
| `422`  | Unprocessable Entity — validation errors |
| `401`  | Unauthorized — missing/invalid token |
| `404`  | Not Found — resource does not exist |

**Validation error shape (`422`):**

```json
{
  "message": "The name field is required.",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

---

## Axios Setup

Install axios if not already present:

```bash
npm install axios
```

Create a reusable `api.js` instance:

```js
// src/api/api.js
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Attach token from storage on every request
api.interceptors.request.use((config) => {
  const token = sessionStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default api;
```

---

## Resources

---

### 1. User Types

**Base path:** `/api/user-types`

Catalog of user roles (e.g. Administrator, Doctor, Nurse).

#### Endpoints

##### GET /api/user-types
List all user types (paginated).

```js
import api from '../api/api';

const getUserTypes = async (page = 1) => {
  const response = await api.get('/user-types', { params: { page } });
  return response.data; // { data, links, meta }
};
```

##### GET /api/user-types/:id
Get a single user type.

```js
const getUserType = async (id) => {
  const response = await api.get(`/user-types/${id}`);
  return response.data; // { data: { id, name, created_at, updated_at } }
};
```

##### POST /api/user-types
Create a new user type.

| Field  | Type   | Required | Rules          |
|--------|--------|----------|----------------|
| `name` | string | Yes      | max 255 chars  |

```js
const createUserType = async (payload) => {
  // payload: { name: "Administrator" }
  const response = await api.post('/user-types', payload);
  return response.data;
};
```

##### PUT /api/user-types/:id
Update an existing user type.

```js
const updateUserType = async (id, payload) => {
  const response = await api.put(`/user-types/${id}`, payload);
  return response.data;
};
```

##### DELETE /api/user-types/:id
Delete a user type. Returns `204 No Content`.

```js
const deleteUserType = async (id) => {
  await api.delete(`/user-types/${id}`);
};
```

**Response object shape:**
```json
{
  "id": 1,
  "name": "Administrator",
  "created_at": "2026-01-01T00:00:00.000000Z",
  "updated_at": "2026-01-01T00:00:00.000000Z"
}
```

---

### 2. Users

**Base path:** `/api/users`

System users who can create patients and record diagnoses.

#### Endpoints

##### GET /api/users
List all users (paginated).

```js
const getUsers = async (page = 1) => {
  const response = await api.get('/users', { params: { page } });
  return response.data;
};
```

##### GET /api/users/:id
Get a single user.

```js
const getUser = async (id) => {
  const response = await api.get(`/users/${id}`);
  return response.data;
};
```

##### POST /api/users
Create a new user.

| Field          | Type   | Required | Rules                                      |
|----------------|--------|----------|--------------------------------------------|
| `name`         | string | Yes      | max 255 chars                              |
| `lastname`     | string | Yes      | max 255 chars                              |
| `email`        | string | Yes      | valid email, unique, max 255 chars         |
| `password`     | string | Yes      | min 8 chars                                |
| `user_type_id` | int    | Yes      | must exist in `users_types`                |

```js
const createUser = async (payload) => {
  /*
    payload: {
      name: "John",
      lastname: "Doe",
      email: "john@example.com",
      password: "secret123",
      user_type_id: 1
    }
  */
  const response = await api.post('/users', payload);
  return response.data;
};
```

##### PUT /api/users/:id
Update a user. `password` is optional on update.

```js
const updateUser = async (id, payload) => {
  const response = await api.put(`/users/${id}`, payload);
  return response.data;
};
```

##### DELETE /api/users/:id
Delete a user. Returns `204 No Content`.

```js
const deleteUser = async (id) => {
  await api.delete(`/users/${id}`);
};
```

**Response object shape:**
```json
{
  "id": 1,
  "name": "John",
  "lastname": "Doe",
  "email": "john@example.com",
  "user_type_id": 1,
  "created_at": "2026-01-01T00:00:00.000000Z",
  "updated_at": "2026-01-01T00:00:00.000000Z"
}
```

> Note: `password` is never returned in responses.

---

### 3. Priorities

**Base path:** `/api/priorities`

Catalog of priority levels assignable to diseases (e.g. High, Medium, Low).

##### POST /api/priorities — Request body

| Field  | Type   | Required | Rules         |
|--------|--------|----------|---------------|
| `name` | string | Yes      | max 255 chars |

```js
// List
const getPriorities = async (page = 1) => {
  const response = await api.get('/priorities', { params: { page } });
  return response.data;
};

// Create
const createPriority = async (name) => {
  const response = await api.post('/priorities', { name });
  return response.data;
};

// Update
const updatePriority = async (id, name) => {
  const response = await api.put(`/priorities/${id}`, { name });
  return response.data;
};

// Delete
const deletePriority = async (id) => {
  await api.delete(`/priorities/${id}`);
};
```

**Response object shape:**
```json
{
  "id": 1,
  "name": "High",
  "created_at": "2026-01-01T00:00:00.000000Z",
  "updated_at": "2026-01-01T00:00:00.000000Z"
}
```

---

### 4. Drugs

**Base path:** `/api/drugs`

Catalog of available medications.

##### POST /api/drugs — Request body

| Field         | Type   | Required | Rules                                                       |
|---------------|--------|----------|-------------------------------------------------------------|
| `name`        | string | Yes      | max 255 chars                                               |
| `description` | string | Yes      | max 255 chars                                               |
| `type`        | string | Yes      | one of: `tablet`, `capsule`, `syrup`, `injection`, `topical`, `other` |

```js
// List
const getDrugs = async (page = 1) => {
  const response = await api.get('/drugs', { params: { page } });
  return response.data;
};

// Create
const createDrug = async (payload) => {
  /*
    payload: {
      name: "Ibuprofen",
      description: "Anti-inflammatory pain reliever",
      type: "tablet"
    }
  */
  const response = await api.post('/drugs', payload);
  return response.data;
};

// Update
const updateDrug = async (id, payload) => {
  const response = await api.put(`/drugs/${id}`, payload);
  return response.data;
};

// Delete
const deleteDrug = async (id) => {
  await api.delete(`/drugs/${id}`);
};
```

**Response object shape:**
```json
{
  "id": 1,
  "name": "Ibuprofen",
  "description": "Anti-inflammatory pain reliever",
  "type": "tablet",
  "created_at": "2026-01-01T00:00:00.000000Z",
  "updated_at": "2026-01-01T00:00:00.000000Z"
}
```

---

### 5. Diseases

**Base path:** `/api/diseases`

Catalog of registered diseases.

##### POST /api/diseases — Request body

| Field            | Type   | Required | Rules                           |
|------------------|--------|----------|---------------------------------|
| `name`           | string | Yes      | max 255 chars                   |
| `technincal_name`| string | Yes      | max 255 chars (technical/scientific name) |
| `description`    | string | Yes      | max 255 chars                   |
| `priority_id`    | int    | Yes      | must exist in `priority`        |

```js
// List
const getDiseases = async (page = 1) => {
  const response = await api.get('/diseases', { params: { page } });
  return response.data;
};

// Get one
const getDisease = async (id) => {
  const response = await api.get(`/diseases/${id}`);
  return response.data;
};

// Create
const createDisease = async (payload) => {
  /*
    payload: {
      name: "Diabetes",
      technincal_name: "Diabetes mellitus",
      description: "Chronic metabolic disease",
      priority_id: 1
    }
  */
  const response = await api.post('/diseases', payload);
  return response.data;
};

// Update
const updateDisease = async (id, payload) => {
  const response = await api.put(`/diseases/${id}`, payload);
  return response.data;
};

// Delete
const deleteDisease = async (id) => {
  await api.delete(`/diseases/${id}`);
};
```

**Response object shape:**
```json
{
  "id": 1,
  "name": "Diabetes",
  "technincal_name": "Diabetes mellitus",
  "description": "Chronic metabolic disease",
  "priority_id": 1,
  "priority": {
    "id": 1,
    "name": "High",
    "created_at": "2026-01-01T00:00:00.000000Z",
    "updated_at": "2026-01-01T00:00:00.000000Z"
  },
  "created_at": "2026-01-01T00:00:00.000000Z",
  "updated_at": "2026-01-01T00:00:00.000000Z"
}
```

> `priority` is always embedded — no need to make a separate request to `/api/priorities/:id`.

---

### 6. Patients

**Base path:** `/api/patients`

Patients registered in the system.

##### POST /api/patients — Request body

| Field         | Type | Required | Rules                                |
|---------------|------|----------|--------------------------------------|
| `name`        | string | Yes    | max 255 chars                        |
| `lastname`    | string | Yes    | max 255 chars                        |
| `nick`        | string | Yes    | max 255 chars — alias or nickname    |
| `suffering`   | int    | No     | must exist in `disease` if provided  |
| `register_by` | int    | No     | must exist in `users` if provided    |

```js
// List
const getPatients = async (page = 1) => {
  const response = await api.get('/patients', { params: { page } });
  return response.data;
};

// Get one
const getPatient = async (id) => {
  const response = await api.get(`/patients/${id}`);
  return response.data;
};

// Create
const createPatient = async (payload) => {
  /*
    payload: {
      name: "Maria",
      lastname: "Garcia",
      nick: "Mari",
      suffering: 1,    // optional — disease id
      register_by: 2   // optional — user id
    }
  */
  const response = await api.post('/patients', payload);
  return response.data;
};

// Update
const updatePatient = async (id, payload) => {
  const response = await api.put(`/patients/${id}`, payload);
  return response.data;
};

// Delete
const deletePatient = async (id) => {
  await api.delete(`/patients/${id}`);
};
```

**Response object shape:**
```json
{
  "id": 1,
  "name": "Maria",
  "lastname": "Garcia",
  "nick": "Mari",
  "suffering": 1,
  "disease": {
    "id": 1,
    "name": "Diabetes",
    "technincal_name": "Diabetes mellitus",
    "description": "Chronic metabolic disease",
    "priority_id": 1,
    "priority": { "id": 1, "name": "High" }
  },
  "register_by": 2,
  "user": {
    "id": 2,
    "name": "John",
    "lastname": "Doe",
    "email": "john@example.com",
    "user_type_id": 1
  },
  "created_at": "2026-01-01T00:00:00.000000Z",
  "updated_at": "2026-01-01T00:00:00.000000Z"
}
```

> `disease` (with nested `priority`) and `user` are always embedded — no need to make separate requests.

---

### 7. Diagnoses

**Base path:** `/api/diagnoses`

Diagnoses made for patients by system users.

##### POST /api/diagnoses — Request body

| Field          | Type   | Required | Rules                               |
|----------------|--------|----------|-------------------------------------|
| `name`         | string | Yes      | max 255 chars — title / label       |
| `disease_id`   | int    | No       | must exist in `disease` if provided |
| `patient_id`   | int    | Yes      | must exist in `patient`             |
| `diagnoses_by` | int    | Yes      | must exist in `users`               |

```js
// List
const getDiagnoses = async (page = 1) => {
  const response = await api.get('/diagnoses', { params: { page } });
  return response.data;
};

// Get one
const getDiagnosis = async (id) => {
  const response = await api.get(`/diagnoses/${id}`);
  return response.data;
};

// Create
const createDiagnosis = async (payload) => {
  /*
    payload: {
      name: "Initial consultation",
      disease_id: 1,    // optional
      patient_id: 3,
      diagnoses_by: 2
    }
  */
  const response = await api.post('/diagnoses', payload);
  return response.data;
};

// Update
const updateDiagnosis = async (id, payload) => {
  const response = await api.put(`/diagnoses/${id}`, payload);
  return response.data;
};

// Delete
const deleteDiagnosis = async (id) => {
  await api.delete(`/diagnoses/${id}`);
};
```

**Response object shape:**
```json
{
  "id": 1,
  "name": "Initial consultation",
  "disease_id": 1,
  "disease": {
    "id": 1,
    "name": "Diabetes",
    "technincal_name": "Diabetes mellitus",
    "description": "Chronic metabolic disease",
    "priority_id": 1
  },
  "patient_id": 3,
  "patient": {
    "id": 3,
    "name": "Maria",
    "lastname": "Garcia",
    "nick": "Mari",
    "suffering": 1,
    "register_by": 2
  },
  "diagnoses_by": 2,
  "user": {
    "id": 2,
    "name": "John",
    "lastname": "Doe",
    "email": "john@example.com",
    "user_type_id": 1
  },
  "created_at": "2026-01-01T00:00:00.000000Z",
  "updated_at": "2026-01-01T00:00:00.000000Z"
}
```

> `disease`, `patient`, and `user` are always embedded — no need to make separate requests.

---

### 8. Diagnoses Has Treatments

**Base path:** `/api/diagnoses-has-treatments`

Medications (treatments) assigned to a specific diagnosis.

##### POST /api/diagnoses-has-treatments — Request body

| Field          | Type   | Required | Rules                           |
|----------------|--------|----------|---------------------------------|
| `diagnoses_id` | int    | Yes      | must exist in `diagnoses`       |
| `drugs`        | int    | Yes      | must exist in `drugs`           |
| `descriptions` | string | Yes      | treatment instructions, max 255 |

```js
// List
const getDiagnosesHasTreatments = async (page = 1) => {
  const response = await api.get('/diagnoses-has-treatments', { params: { page } });
  return response.data;
};

// Get one
const getDiagnosisHasTreatment = async (id) => {
  const response = await api.get(`/diagnoses-has-treatments/${id}`);
  return response.data;
};

// Create
const createDiagnosisHasTreatment = async (payload) => {
  /*
    payload: {
      diagnoses_id: 1,
      drugs: 3,
      descriptions: "Take 1 tablet every 8 hours after meals"
    }
  */
  const response = await api.post('/diagnoses-has-treatments', payload);
  return response.data;
};

// Update
const updateDiagnosisHasTreatment = async (id, payload) => {
  const response = await api.put(`/diagnoses-has-treatments/${id}`, payload);
  return response.data;
};

// Delete
const deleteDiagnosisHasTreatment = async (id) => {
  await api.delete(`/diagnoses-has-treatments/${id}`);
};
```

**Response object shape:**
```json
{
  "id": 1,
  "diagnoses_id": 1,
  "diagnosis": {
    "id": 1,
    "name": "Initial consultation",
    "disease_id": 1,
    "patient_id": 3,
    "diagnoses_by": 2
  },
  "drugs": 3,
  "drug": {
    "id": 3,
    "name": "Ibuprofen",
    "description": "Anti-inflammatory pain reliever",
    "type": "tablet"
  },
  "descriptions": "Take 1 tablet every 8 hours after meals",
  "created_at": "2026-01-01T00:00:00.000000Z",
  "updated_at": "2026-01-01T00:00:00.000000Z"
}
```

> `drug` and `diagnosis` are always embedded — no need to make separate requests.

---

### 9. Disease Has Treatments

**Base path:** `/api/disease-has-treatments`

General recommended treatments for a disease (not tied to a specific diagnosis).

##### POST /api/disease-has-treatments — Request body

| Field          | Type   | Required | Rules                                  |
|----------------|--------|----------|----------------------------------------|
| `descriptions` | string | Yes      | treatment description, max 255 chars   |
| `disease_id`   | int    | No       | must exist in `disease` if provided    |
| `drugs`        | int    | No       | must exist in `drugs` if provided      |

```js
// List
const getDiseaseHasTreatments = async (page = 1) => {
  const response = await api.get('/disease-has-treatments', { params: { page } });
  return response.data;
};

// Get one
const getDiseaseHasTreatment = async (id) => {
  const response = await api.get(`/disease-has-treatments/${id}`);
  return response.data;
};

// Create
const createDiseaseHasTreatment = async (payload) => {
  /*
    payload: {
      descriptions: "Recommended first-line treatment",
      disease_id: 1,   // optional
      drugs: 2         // optional
    }
  */
  const response = await api.post('/disease-has-treatments', payload);
  return response.data;
};

// Update
const updateDiseaseHasTreatment = async (id, payload) => {
  const response = await api.put(`/disease-has-treatments/${id}`, payload);
  return response.data;
};

// Delete
const deleteDiseaseHasTreatment = async (id) => {
  await api.delete(`/disease-has-treatments/${id}`);
};
```

**Response object shape:**
```json
{
  "id": 1,
  "descriptions": "Recommended first-line treatment",
  "disease_id": 1,
  "disease": {
    "id": 1,
    "name": "Diabetes",
    "technincal_name": "Diabetes mellitus",
    "description": "Chronic metabolic disease",
    "priority_id": 1
  },
  "drugs": 2,
  "drug": {
    "id": 2,
    "name": "Metformin",
    "description": "First-line medication for type 2 diabetes",
    "type": "tablet"
  },
  "created_at": "2026-01-01T00:00:00.000000Z",
  "updated_at": "2026-01-01T00:00:00.000000Z"
}
```

> `drug` and `disease` are always embedded — no need to make separate requests.

---

## Data Dictionary

### Table: `users_types`

Catalog of user types in the system (e.g. administrator, doctor, nurse).

| Column | Type    | Null | Key | Description           |
|--------|---------|------|-----|-----------------------|
| id     | INT     | No   | PK  | Unique identifier     |
| name   | VARCHAR | No   |     | User type name        |

---

### Table: `users`

Registered users who can create diagnoses and register patients.

| Column       | Type    | Null | Key | Description                    |
|--------------|---------|------|-----|--------------------------------|
| id           | INT     | No   | PK  | Unique identifier              |
| name         | VARCHAR | No   |     | User's first name              |
| lastname     | VARCHAR | No   |     | User's last name               |
| email        | VARCHAR | No   | UQ  | Email address (unique)         |
| password     | VARCHAR | No   |     | Hashed password                |
| user_type_id | INT     | No   | FK  | Reference to `users_types.id`  |

**Relations:**
- `user_type_id` → `users_types.id`

---

### Table: `priority`

Catalog of priority levels assignable to diseases.

| Column | Type    | Null | Key | Description                               |
|--------|---------|------|-----|-------------------------------------------|
| id     | INT     | No   | PK  | Unique identifier                         |
| name   | VARCHAR | No   |     | Priority level name (e.g. High, Medium, Low) |

---

### Table: `drugs`

Catalog of medications available in the system.

| Column      | Type    | Null | Key | Description                                                      |
|-------------|---------|------|-----|------------------------------------------------------------------|
| id          | INT     | No   | PK  | Unique identifier                                                |
| name        | VARCHAR | No   |     | Medication name                                                  |
| description | VARCHAR | No   |     | Medication description                                           |
| type        | ENUM    | No   |     | Drug type (`tablet`, `capsule`, `syrup`, `injection`, `topical`, `other`) |

---

### Table: `disease`

Catalog of diseases registered in the system.

| Column           | Type    | Null | Key | Description                      |
|------------------|---------|------|-----|----------------------------------|
| id               | INT     | No   | PK  | Unique identifier                |
| name             | VARCHAR | No   |     | Common disease name              |
| technincal_name  | VARCHAR | No   |     | Technical / scientific name      |
| description      | VARCHAR | No   |     | Disease description              |
| priority_id      | INT     | No   | FK  | Reference to `priority.id`       |

**Relations:**
- `priority_id` → `priority.id`

---

### Table: `patient`

Patients registered in the system.

| Column      | Type    | Null | Key | Description                                        |
|-------------|---------|------|-----|----------------------------------------------------|
| id          | INT     | No   | PK  | Unique identifier                                  |
| name        | VARCHAR | No   |     | Patient's first name                               |
| lastname    | VARCHAR | No   |     | Patient's last name                                |
| nick        | VARCHAR | No   |     | Alias or nickname                                  |
| suffering   | INT     | Yes  | FK  | Primary disease the patient has (`disease.id`)     |
| register_by | INT     | Yes  | FK  | User who registered the patient (`users.id`)       |

**Relations:**
- `suffering` → `disease.id`
- `register_by` → `users.id`

---

### Table: `diagnoses`

Diagnoses performed on patients by system users.

| Column       | Type    | Null | Key | Description                                    |
|--------------|---------|------|-----|------------------------------------------------|
| id           | INT     | No   | PK  | Unique identifier                              |
| name         | VARCHAR | No   |     | Diagnosis title / name                         |
| disease_id   | INT     | Yes  | FK  | Diagnosed disease (`disease.id`)               |
| patient_id   | INT     | No   | FK  | Patient diagnosed (`patient.id`)               |
| diagnoses_by | INT     | No   | FK  | User who performed the diagnosis (`users.id`)  |

**Relations:**
- `disease_id` → `disease.id`
- `patient_id` → `patient.id`
- `diagnoses_by` → `users.id`

---

### Table: `diagnoses_has_treatments`

Treatments (medications) associated with a specific diagnosis.

| Column       | Type    | Null | Key | Description                                      |
|--------------|---------|------|-----|--------------------------------------------------|
| id           | INT     | No   | PK  | Unique identifier                                |
| diagnoses_id | INT     | No   | FK  | Diagnosis this belongs to (`diagnoses.id`)       |
| drugs        | INT     | No   | FK  | Assigned medication (`drugs.id`)                 |
| descriptions | VARCHAR | No   |     | Treatment instructions / description             |

**Relations:**
- `diagnoses_id` → `diagnoses.id`
- `drugs` → `drugs.id`

---

### Table: `disease_has_treatments`

General recommended treatments for a disease.

| Column       | Type    | Null | Key | Description                               |
|--------------|---------|------|-----|-------------------------------------------|
| id           | INT     | No   | PK  | Unique identifier                         |
| descriptions | VARCHAR | No   |     | Description of the recommended treatment  |
| disease_id   | INT     | Yes  | FK  | Related disease (`disease.id`)            |
| drugs        | INT     | Yes  | FK  | Recommended medication (`drugs.id`)       |

**Relations:**
- `disease_id` → `disease.id`
- `drugs` → `drugs.id`

---

### Entity Relationship Summary

```
users_types ←── users ──→ patient ──→ diagnoses ──→ diagnoses_has_treatments
                  ↑           ↑            ↑                    ↓
                  └───────────┘       disease ←──────── disease_has_treatments
                                          ↑                    ↓
                                       priority              drugs
```

---

### Notes

- All tables use an auto-incremented `id` as primary key.
- The `drugs.type` field is an ENUM: `tablet`, `capsule`, `syrup`, `injection`, `topical`, `other`.
- The `users` and `patient` tables use `register_by` and `diagnoses_by` for audit traceability.
- `password` is never exposed in API responses.
