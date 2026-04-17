# Guía de API y Endpoints

Este backend fue construido como una API REST usando **Laravel 12**.

## Documentación Interactiva (Scramble)

La forma más sencilla de entender, visualizar y probar todos los endpoints disponibles es a través de la documentación interactiva generada por **Scramble**.

Una vez que tengas el servidor levantado (`php artisan serve`), ingresa a:

👉 **http://127.0.0.1:8000/docs/api**

Ahí verás todos los schemas, requerimientos y parámetros exactos aceptados por la API.

---

## Autenticación (Sanctum)

El proyecto utiliza **Laravel Sanctum** con un enfoque basado en tokens para la autenticación de la API.

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

## Endpoints Principales

*Recuerda revisar `/docs/api` para el CRUD completo (GET, POST, PUT, DELETE) de cada uno.*

- **Auth**
  - `POST /api/login`
  - `POST /api/logout` (Protegido)
  - `GET /api/user` (Protegido - retorna usuario actual)
- **Catálogos y Usuarios**
  - `/api/users` (CRUD Usuarios)
  - `/api/user-types` (CRUD Tipos de Usuario)
  - `/api/priorities` (CRUD Prioridades)
  - `/api/drugs` (CRUD Medicamentos)
- **Gestión Médica**
  - `/api/diseases` (CRUD Enfermedades)
  - `/api/patients` (CRUD Pacientes)
  - `/api/diagnoses` (CRUD Diagnósticos)
  - `/api/diagnoses-has-treatments` (Asignar/Ver tratamientos dados en un diagnóstico)
  - `/api/disease-has-treatments` (Relación base entre enfermedad y tratamiento)