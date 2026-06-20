# Cambios para el frontend — backend Auth/Usuarios/Roles (PHP plano)

El backend pasó de **Laravel** a la **API en PHP plano** de `dev`. Auth, Usuarios y
Roles fueron portados. La autenticación es por **token Bearer** (no cookies).

Base URL: `http://localhost:8000/api`

---

## 1. Autenticación (sin cambios para el front)

- `POST /login` con `{ email, password }` → `{ token, user }`.
  - Guardar `token` y enviarlo en cada petición como `Authorization: Bearer <token>`.
  - Login rechaza cuentas inactivas con **403** y `error_code: INACTIVE_ACCOUNT`.
- `GET /user` → datos del usuario autenticado **+ `permissions` (array de strings)**.
  Úsalo para los gates de UI con `can(permission)`.
- `POST /logout`, `POST /auth/forgot-password`, `POST /auth/reset-password`: igual.

> El cliente axios actual (`src/api/client.ts`) y el `AuthContext` ya son compatibles.

---

## 2. Endpoints que CAMBIARON respecto al Laravel viejo ⚠️

| Acción | Antes (Laravel) | Ahora (PHP plano) |
|--------|-----------------|-------------------|
| Activar/desactivar usuario | `PATCH /users/{id}/status` | **`PUT /users/{id}/status`** |
| Asignar rol a un usuario | `PUT /users/{id}/roles` (plural, set) | **`PUT /users/{id}/role`** (singular) |
| Cuerpo al asignar rol | `{ roles: [...] }` | **`{ user_type_id: <id> }`** |
| has-permission | body/route | **`GET /auth/has-permission?permission=xxx`** (query) |

El resto de rutas se mantienen:
`GET/POST /users`, `GET/PUT/DELETE /users/{id}`, `GET/POST /roles`,
`GET/PUT/DELETE /roles/{id}`, `GET /roles/permissions`,
`GET /stats/users/by-role`, `GET /stats/users/by-status`,
`GET /logs/users`, `GET /logs/roles`.

---

## 3. Forma del objeto `user` (cambió)

```json
{
  "id": 1,
  "name": "Carlos",
  "lastname": "Ramírez",
  "email": "admin@ccss.cr",
  "user_type_id": 1,
  "role": "Administrador",   // ← NUEVO: nombre del rol (uno solo)
  "status": "ACTIVE",        // ← NUEVO: ACTIVE | INACTIVE
  "created_at": "...",
  "updated_at": "..."
}
```

**Un solo rol por usuario** (es `user_type_id`). El modelo multi-rol del Laravel
viejo ya no aplica: el campo `role` es un string, no un array.

---

## 4. Roles y permisos

- Los **roles son los `users_types`** (Administrador, Medico, Enfermero, Paciente).
  - `GET /roles` → cada rol incluye su array `permissions`.
  - `GET /roles/permissions` → catálogo completo de permisos (para la grilla).
- Crear/editar rol acepta `{ name, permissions: [...] }`. La cascada read/write se
  aplica en el backend (un permiso de escritura implica el `read` del módulo).
- Permisos por módulo: `<modulo>.<read|create|update|delete>` para
  `users, roles, patients, diagnoses, diseases, drugs, priorities, treatments`,
  más `logs_users.read` y `logs_roles.read`.

---

## 5. Cambio de estado por dirección (importante para la UI) ⚠️

`PUT /users/{id}/status` con `{ status: "ACTIVE" | "INACTIVE" }`. La autorización
depende de la dirección:

- **Desactivar** (→ INACTIVE) requiere permiso **`users.delete`**.
- **Reactivar** (→ ACTIVE) requiere permiso **`users.update`**.

Para mostrar/ocultar el botón correctamente:
- Botón **desactivar** → `can('users.delete')`.
- Botón **activar** → `can('users.update')`.

(Antes ambos se gobernaban con un único permiso; por eso fallaba inactivar.)

---

## 6. Códigos de error (`error_code`) que el front puede mapear a mensajes

| HTTP | error_code | Cuándo |
|------|------------|--------|
| 401 | `UNAUTHENTICATED` | sin token o token inválido |
| 403 | `FORBIDDEN` | autenticado pero sin el permiso requerido |
| 403 | `SELF_ACTION_FORBIDDEN` | acción sobre la propia cuenta (editar/estado/rol) |
| 403 | `INACTIVE_ACCOUNT` | login de una cuenta inactiva |
| 409 | `LAST_ADMIN` | desactivar/eliminar al último administrador activo |
| 409 | `ROLE_IN_USE` | eliminar un rol con usuarios asignados |
| 422 | `VALIDATION_ERROR` | validación; trae `errors: { campo: [mensajes] }` |

Forma del error: `{ success: false, message, error_code, errors? }`.

---

## 7. CORS / entorno

- El backend ya emite headers CORS para `http://localhost:5173` (token Bearer, sin
  credenciales). Mantener el front en el puerto **5173** (vite `strictPort`).
- `.env` del front: `VITE_API_URL=http://localhost:8000/api`.

---

## 8. Usuarios de prueba (seed)

| Correo | Contraseña | Rol |
|--------|-----------|-----|
| admin@ccss.cr | Admin1234! | Administrador |
| doctor1@ccss.cr | Doctor1234! | Medico |
| doctor2@ccss.cr | Doctor1234! | Medico |
| nurse1@ccss.cr | Nurse1234! | Enfermero |
