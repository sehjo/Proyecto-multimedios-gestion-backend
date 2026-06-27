# Proyecto Multimedios — Backend PHP

API REST para el sistema de gestión de citas médicas MediCode UCR, construida en PHP puro con PDO y MySQL.

---

## Requisitos

- PHP 8.2 o superior (con extensiones `pdo`, `pdo_mysql`, `mbstring`)
- MySQL 8.0 o superior
- Servidor web con soporte `.htaccess` (Apache/Laragon) **o** el servidor integrado de PHP

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone <url-del-repositorio>
cd Proyecto-multimedios-gestion-backend
```

### 2. Configurar variables de entorno

```bash
cp .env.example .env
```

Edita `.env` con tus credenciales:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_ccss
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=log        # usar "log" en desarrollo (no envía correos reales)
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_FROM_ADDRESS=hello@example.com
MAIL_FROM_NAME=MediCode
```

### 3. Crear la base de datos y cargar el esquema

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS db_ccss CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p db_ccss < api/config/schema.sql
```

### 4. Cargar datos de prueba (opcional)

Carga el seeder para tener roles, permisos y usuarios listos. La contraseña de todos los usuarios seed es `Password123!`.

```bash
mysql -u root -p db_ccss < api/config/seed.sql
```

---

## Levantar el servidor

### Con Laragon (recomendado)

Coloca el proyecto dentro de `laragon/www/`. Laragon lo sirve automáticamente en:

```
http://localhost/Proyecto-multimedios-gestion-backend/api/v1/
```

### Con el servidor integrado de PHP

```bash
php -S localhost:8000 -t public
```

La API queda disponible en:

```
http://localhost:8000/api/v1/
```

---

## Estructura del proyecto

```
api/
├── config/
│   ├── connection.php   # Conexión PDO
│   ├── env.php          # Carga de variables de entorno
│   ├── mailer.php       # Servicio de correo
│   ├── schema.sql       # Esquema de la base de datos
│   └── seed.sql         # Datos iniciales
├── controller/          # Controladores HTTP
├── dao/                 # Acceso a datos (Data Access Objects)
├── models/              # Modelos de dominio
├── routes/
│   └── api.php          # Definición de rutas
└── views/
    └── helpers.php      # Funciones auxiliares (jsonResponse, validar, etc.)
```

---

## Endpoints principales

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/v1/auth/login` | Iniciar sesión |
| POST | `/api/v1/auth/logout` | Cerrar sesión |
| POST | `/api/v1/auth/forgot-password` | Solicitar restablecimiento de contraseña |
| GET | `/api/v1/users` | Listar usuarios |
| POST | `/api/v1/users` | Crear usuario (admin) |
| POST | `/api/v1/users/register` | Registro público |
| GET | `/api/v1/patients` | Listar pacientes |
| POST | `/api/v1/patients` | Crear paciente |
| GET | `/api/v1/appointments` | Listar citas |
| POST | `/api/v1/appointments` | Crear cita |
| POST | `/api/v1/guest-appointments/start` | Iniciar flujo de cita como invitado |
| POST | `/api/v1/guest-appointments` | Crear cita como invitado |
| GET | `/api/v1/roles` | Listar roles |
| GET | `/api/v1/permissions` | Listar permisos |
| GET | `/api/v1/institution-availability` | Disponibilidad institucional |
| GET | `/api/v1/institution-blocks` | Bloqueos institucionales |
| GET | `/api/v1/institution-logs` | Logs institucionales |
| GET | `/api/v1/notifications` | Notificaciones del usuario autenticado |
| GET | `/api/v1/stats/appointments/by-status` | Estadísticas de citas |

La colección completa de Postman está en `postman_collection.json`.

---

## Autenticación

La API usa tokens Bearer. Incluye el header en cada petición protegida:

```
Authorization: Bearer <token>
```

El token se obtiene al hacer login en `/api/v1/auth/login`.

---

## Usuarios seed

| Correo | Contraseña | Rol |
|--------|------------|-----|
| `superadmin@ucr.ac.cr` | `Password123!` | Super Admin |
| `admin@ucr.ac.cr` | `Password123!` | Admin |
| `doctor@ucr.ac.cr` | `Password123!` | Doctor |
