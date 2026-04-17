# Guía de Instalación y Configuración

## Requisitos Previos

- **PHP**: >= 8.2 (Extensiones recomendadas: PDO, OpenSSL, Mbstring, Tokenizer, XML)
- **Composer**: >= 2.x
- **MySQL**: >= 8.0

## Pasos para Levantar el Proyecto Localmente

1. **Clonar y Acceder al Proyecto**
   El código principal de Laravel se encuentra dentro de la carpeta `init/`.
   ```bash
   git clone <URL_DEL_REPO>
   cd ccss_consultory_bk/init
   ```

2. **Instalar Dependencias**
   ```bash
   composer install
   ```

3. **Configurar el Entorno**
   Copia el archivo de entorno base:
   ```bash
   cp .env.example .env
   ```
   Genera la clave de la aplicación:
   ```bash
   php artisan key:generate
   ```

4. **Configurar la Base de Datos (`.env`)**
   Asegúrate de que tus credenciales de MySQL estén correctamente configuradas.
   *Nota: Si estás usando MySQL y experimentas tiempos de espera porque no existe la tabla de sesiones, cambia el controlador de sesiones a archivo en tu `.env`.*
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_ccss
   DB_USERNAME=root
   DB_PASSWORD=1234 # O tu contraseña

   SESSION_DRIVER=file # Muy importante si fallan las peticiones sin session table
   ```

5. **Ejecutar Migraciones y Seeders**
   Esto creará todas las tablas e inyectará datos de prueba en español.
   *(Asegúrate de haber creado la base de datos `db_ccss` en tu gestor MySQL antes).*
   ```bash
   php artisan migrate:fresh --seed
   ```

6. **Levantar el Servidor**
   ```bash
   php artisan serve
   ```
   El backend estará escuchando en `http://127.0.0.1:8000`.