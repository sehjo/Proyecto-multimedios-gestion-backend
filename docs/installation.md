# Guía de Instalación y Configuración

## Requisitos Previos

- **PHP**: >= 8.2 (extensiones requeridas: `pdo`, `pdo_mysql`)
- **MySQL**: >= 8.0
- Sin Composer ni ningún framework: el backend es PHP puro, las clases se cargan con `require_once` (ver `core/bootstrap.php`).

## Pasos para Levantar el Proyecto Localmente

1. **Clonar el proyecto**
   ```bash
   git clone <URL_DEL_REPO>
   cd Proyecto-multimedios-gestion-backend
   ```

2. **Configurar el Entorno**
   Copia el archivo de entorno base y ajusta tus credenciales de MySQL:
   ```bash
   cp .env.example .env
   ```
   ```env
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_ccss
   DB_USERNAME=root
   DB_PASSWORD=tu_password
   ```

3. **Crear la base de datos y cargar el esquema**
   ```bash
   mysql --default-character-set=utf8mb4 -uroot -p -e "CREATE DATABASE db_ccss CHARACTER SET utf8mb4;"
   mysql --default-character-set=utf8mb4 -uroot -p db_ccss < database/schema.sql
   ```
   *Importante: usa `--default-character-set=utf8mb4` al cargar los `.sql`; de lo contrario los acentos (á, é, í, ó, ú, ñ) se guardan corruptos.*

4. **(Opcional) Cargar datos de ejemplo en español**
   ```bash
   mysql --default-character-set=utf8mb4 -uroot -p db_ccss < database/seed.sql
   ```

5. **Levantar el servidor**
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```
   El backend estará escuchando en `http://127.0.0.1:8000`, con todos los endpoints bajo el prefijo `/api` (ver [api.md](api.md)).

6. **(Opcional) Ejecutar las pruebas**
   ```bash
   php tests/run.php
   ```
