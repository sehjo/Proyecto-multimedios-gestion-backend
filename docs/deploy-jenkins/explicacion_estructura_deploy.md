
## Entender la estructura del repositorio

Antes de correr el pipeline, entender qué hace cada archivo:

```
ccss_consultory_bk/
├── Jenkinsfile.backend        ← Pipeline del backend (6 stages)
├── init/                      ← Código Laravel (app real)
│   ├── Dockerfile             ← Build de la imagen Docker del backend
│   ├── .dockerignore          ← Excluye vendor, .env, tests, etc.
│   ├── docker-entrypoint.sh   ← Limpia cache y regenera config al iniciar
│   ├── config/
│   │   └── cors.php           ← Permite CORS desde localhost:3000 (frontend)
│   └── ...
└── docs/                      ← Documentación
```

> **Nota sobre `init/`:** El proyecto tiene una subcarpeta `init/` que contiene
> todo el código Laravel. El Dockerfile y el pipeline apuntan a esa subcarpeta,
> no a la raíz del repositorio.

---

## Qué hace cada stage del pipeline

El pipeline `Jenkinsfile.backend` tiene 6 stages. Este es el orden y qué hace cada uno:

```
Stage 1: Checkout
  → Descarga el código del repo (rama feat/deploy-backend) al workspace de Jenkins.

Stage 2: Build Docker Image
  → docker build -t ccss-backend:latest -f init/Dockerfile init
  → Construye la imagen PHP con todas las dependencias de Composer incluidas.

Stage 3: Provision Database
  → Crea la red Docker ccss-net (si no existe)
  → Corre el contenedor ccss-mysql (MySQL 8.0) en esa red
  → Espera hasta que MySQL esté listo antes de continuar (hasta 2 minutos)

Stage 4: Deploy Container
  → Elimina el contenedor backend anterior si existía
  → Corre ccss-backend-app en la red ccss-net con variables DB apuntando a ccss-mysql
  → Expone el puerto 8000

Stage 5: Migrate and Seed
  → php artisan config:clear    ← elimina configuración cacheada vieja
  → php artisan cache:clear     ← elimina caché de app
  → php artisan config:cache    ← regenera caché desde variables de entorno actuales
  → php artisan migrate --force ← crea las tablas en MySQL
  → php artisan db:seed --force ← inserta datos de prueba (usuarios, etc.)

Stage 6: Smoke Test
  → Espera 8s a que el servidor esté respondiendo
  → curl http://127.0.0.1:8000/up dentro del contenedor
  → Si responde "Application up" → pipeline SUCCESS
```

> **Por qué config:cache antes de migrate:**
> Si hay una caché de configuración vieja con `DB_HOST=127.0.0.1`,
> Laravel la usará aunque Docker inyecte `DB_HOST=ccss-mysql` por variable de entorno.
> El `config:clear` + `config:cache` garantiza que la configuración activa
> sea la correcta antes de intentar la migración.

---

## Paso 9 — Variables de entorno del pipeline

Estas variables están definidas en el bloque `environment` del `Jenkinsfile.backend`:

| Variable | Valor | Propósito |
|---|---|---|
| `REPO_URL` | URL del repositorio en GitHub | Checkout |
| `BRANCH` | `feat/deploy-backend` | Rama a desplegar |
| `IMAGE_NAME` | `ccss-backend` | Nombre de la imagen Docker |
| `CONTAINER_NAME` | `ccss-backend-app` | Nombre del contenedor backend |
| `NETWORK_NAME` | `ccss-net` | Red Docker interna compartida con MySQL |
| `DB_CONTAINER_NAME` | `ccss-mysql` | Nombre del contenedor MySQL |
| `DB_DATABASE` | `db_ccss` | Nombre de la base de datos |
| `DB_USERNAME` | `ccss_user` | Usuario MySQL |
| `DB_PASSWORD` | `ccss_pass_123` | Contraseña MySQL |
| `DB_ROOT_PASSWORD` | `root_ccss_123` | Contraseña root MySQL (para health check) |
| `HOST_PORT` | `8000` | Puerto expuesto en el host |
| `CREDENTIALS_ID` | `H-github-ccss` | ID de la credential de GitHub en Jenkins |

> **Importante:** `DB_HOST` NO se define como variable global porque su valor
> es `${DB_CONTAINER_NAME}` (nombre del contenedor MySQL), que se pasa
> directamente como `-e DB_HOST=ccss-mysql` en el `docker run` del backend.
> Esto es lo que permite que los dos contenedores se comuniquen dentro de `ccss-net`.
