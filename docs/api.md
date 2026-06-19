# Guía de API y Endpoints

Este backend es una API REST en **PHP puro** (sin framework). El router (`core/Router.php`) y los controladores (`controllers/`) viven directamente en el código; no hay generador de documentación interactiva como Scramble, por lo que esta guía y [frontend_api_guide.md](frontend_api_guide.md) son la referencia.

Una vez que tengas el servidor levantado (`php -S 127.0.0.1:8000 -t public`), todos los endpoints quedan disponibles bajo el prefijo `/api` (excepto el health check `/up`).

---

## Autenticación (tokens Bearer propios)

El proyecto usa un sistema de tokens de acceso propio (equivalente a Laravel Sanctum pero sin el paquete): al iniciar sesión se genera un token aleatorio, se guarda su hash SHA-256 en la tabla `personal_access_tokens`, y el cliente debe enviarlo en cada petición protegida vía el header `Authorization: Bearer <token>` (ver `core/Auth.php`).

- **Login Endpoint**: `POST /api/login`
  - *Payload*: `{ "email": "admin@ccss.cr", "password": "Admin1234!" }`
  - *Respuesta*: Devuelve el usuario y un `token` Bearer en texto plano.
- **Logout Endpoint**: `POST /api/logout`
  - *Header*: `Authorization: Bearer <TU_TOKEN>`

## Flujo de Trabajo

Para acceder a cualquier endpoint protegido (usuarios, diagnósticos, etc.):

1. Haz una petición `POST` a `/api/login` con tus credenciales.
2. Extrae la variable `token` de la respuesta JSON.
3. En tus futuras peticiones REST, incluye el encabezado HTTP:
   `Authorization: Bearer <TU_TOKEN>`
4. Peticiones de escritura como `POST`, `PUT` o `PATCH` usualmente requieren que envíes en formato JSON con los Headers `Accept: application/json` y `Content-Type: application/json`.

## Endpoints implementados

- **Auth**
  - `POST /api/login`
  - `POST /api/logout` (Protegido)
  - `GET /api/user` (Protegido - retorna usuario actual)
  - `POST /api/auth/forgot-password`
  - `POST /api/auth/reset-password`
- **Catálogos y Usuarios**
  - `/api/users` (CRUD Usuarios)
  - `/api/user-types` (CRUD Tipos de Usuario)
- **Gestión Médica**
  - `/api/patients` (CRUD Pacientes)
- **Otros**
  - `GET /api/dashboard` (Protegido - totales de usuarios/pacientes)
  - `GET /up` (health check, sin prefijo `/api`)

## Tablas sin endpoint propio (todavía)

Las tablas `priority`, `disease`, `drugs`, `diagnoses`, `disease_has_treatments` y `diagnoses_has_treatments` existen en el esquema y tienen datos de ejemplo (ver [database.md](database.md)), y cuentan con su modelo y repositorio en `models/`/`repositories/` listos para usarse, pero **no tienen controlador ni ruta expuesta** — igual que en el backend Laravel original, donde solo se sembraban (seed) sin CRUD propio.