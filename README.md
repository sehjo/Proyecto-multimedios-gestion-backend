# CCSS Consultory Backend

API REST construida en **Laravel 12** para la gesti�n del consultorio m�dico CCSS. Permite la administraci�n de pacientes, doctores, diagn�sticos, enfermedades y tratamientos m�dicos.

## Caracter�sticas

- Autenticaci�n con **Laravel Sanctum** (Tokens).
- CRUD completo para usuarios, pacientes, enfermedades, medicamentos, diagn�sticos y tratamientos.
- Eliminaci�n f�sica de registros (Hard deletes) - *No usa soft deletes*.
- Documentaci�n de API interactiva generada con **Scramble**.
- Seeders con datos precargados en Espa�ol para probar el sistema.
- Conexi�n a **MySQL** (base de datos `db_ccss`).

## Documentaci�n

La documentaci�n completa de este proyecto ha sido movida a la carpeta `docs/`.

- [Gu�a de Instalaci�n y Configuraci�n](docs/installation.md)
- [Gu�a de API y Endpoints](docs/api.md)
- [Estructura de Base de Datos y Seeders](docs/database.md)

---

### Requisitos R�pidos
- PHP >= 8.2
- Composer
- MySQL 8+

### Setup R�pido (Local)

`ash
cd init
cp .env.example .env
composer install
php artisan key:generate
# Configurar .env para MySQL y recordar poner SESSION_DRIVER=file si no hay tabla sessions
php artisan migrate:fresh --seed
php artisan serve
``n
La documentaci�n de los endpoints estar� en `http://127.0.0.1:8000/docs/api`.
 