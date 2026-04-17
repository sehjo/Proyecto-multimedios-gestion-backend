# Guía de Deploy Backend — Jenkins + Docker (paso a paso)

**Proyecto:** CCSS Consultory Backend (Laravel 12 / PHP 8.4)
**Fecha:** 2026-03-16
**Repositorio:** https://github.com/voluntarios/ccss_consultory_bk.git
**Rama:** `feat/deploy-backend`

Esta guía describe exactamente cómo se reproduce el entorno de CI/CD del backend
desde cero en cualquier máquina con Windows + Docker Desktop.

---

## Paso 1 — Verificar prerequisitos

Antes de empezar, comprobar que todo lo necesario está instalado y funcionando.

```powershell
node -v
# Esperado: v18 o superior
# Si falta: https://nodejs.org

npm -v
# Esperado: 9 o superior

wsl --status
# Esperado: WSL instalado (Default Version: 2)
# Si falta: wsl --install

wsl -l -v
# Muestra las distribuciones instaladas y su versión WSL
# Esperado: al menos una distro en STATE Running con VERSION 2

docker --version
# Esperado: Docker version 24 o superior
# Si falta: https://www.docker.com/products/docker-desktop

docker compose version
# Esperado: Docker Compose version v2.x
# Viene incluido con Docker Desktop

docker ps
# Si responde sin error, Docker está corriendo
# Si lanza "Cannot connect to the Docker daemon": abrir Docker Desktop primero
```

> **Nota:** Node y NPM son necesarios solo si el frontend también se despliega en
> esta máquina. Para el backend solo son estrictamente necesarios Docker y WSL2.

---

## Paso 2 — Crear volumen e instalar Jenkins en Docker

Jenkins se corre como contenedor Docker. El volumen `jenkins_home` guarda toda
la configuración y los jobs entre reinicios.

> **Importante:** Montar `docker.sock` no es suficiente. El contenedor de Jenkins
> tambien necesita tener instalado el comando `docker` (Docker CLI).

```powershell
# Crear volumen persistente (solo se hace una vez)
docker volume create jenkins_home

# Construir imagen de Jenkins con Docker CLI
@'
FROM jenkins/jenkins:lts
USER root
COPY --from=docker:27-cli /usr/local/bin/docker /usr/local/bin/docker
RUN chmod +x /usr/local/bin/docker
USER jenkins
'@ | Set-Content -Path .\Dockerfile.jenkins

docker build -t ccss-jenkins-docker:lts -f .\Dockerfile.jenkins .

# Correr Jenkins (Nota: en Windows se requiere --user root para permisos en docker.sock)
docker run -d `
  --name jenkins `
  --restart unless-stopped `
  --user root `
  -p 8080:8080 `
  -p 50000:50000 `
  -v jenkins_home:/var/jenkins_home `
  -v /var/run/docker.sock:/var/run/docker.sock `
  ccss-jenkins-docker:lts

# Verificar que Jenkins si tiene Docker CLI
docker exec jenkins docker --version
```

> **Nota:** El flag `-v /var/run/docker.sock:/var/run/docker.sock` es fundamental.
> Sin él, Jenkins no puede ejecutar comandos `docker` dentro del pipeline.
> En Docker Desktop con WSL2, este socket está disponible automáticamente.

Esperar unos 30s a que Jenkins arranque, luego abrir: http://localhost:8080

```powershell
# Obtener contraseña inicial de administrador
docker exec jenkins cat /var/jenkins_home/secrets/initialAdminPassword
```

> Copiar esa contraseña, pegarla en el formulario de setup de Jenkins y
> seleccionar **"Install suggested plugins"**. Esperar instalación.
> Crear usuario administrador con los datos que prefieras.

---

## Paso 3 — Instalar Git como herramienta en Jenkins

Jenkins necesita Git para hacer checkout del repositorio.

1. Ir a: **Jenkins → Administrar Jenkins → Tools**
2. En la sección **Git installations** → verificar que exista una entrada con Name `Default`
3. Si no existe: agregar con Name `Default` y `git` como ejecutable (lo toma del PATH del contenedor)
4. **Save**

> **Nota:** La imagen `jenkins/jenkins:lts` ya trae Git incluido.
> Este paso es solo para confirmar la configuración.

---

## Paso 4 — Agregar credenciales de GitHub en Jenkins

El pipeline hace checkout de un repositorio privado. Se necesita un token de GitHub.

1. Ir a: **Jenkins → Administrar Jenkins → Credentials → System → Global credentials → Add Credentials**
2. Completar así:

```
Kind: Username with password
Scope: Global
Username: (tu usuario de GitHub)
Password: (tu GitHub Personal Access Token)
ID: H-github-ccss
Description: token para el github
```

> **Cómo generar el token en GitHub:**
> GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
> → Generate new token → marcar scope `repo` → Generate → copiar.
>
> **Importante:** El token se muestra solo una vez. Guardarlo antes de cerrar.
> El ID `H-github-ccss` debe coincidir exactamente con el valor en `Jenkinsfile.backend`.

---

## Paso 4.1 — Agregar credenciales de base de datos en Jenkins

El pipeline ya no trae las contraseñas hardcodeadas en el `Jenkinsfile.backend`.
En su lugar usa `credentials()` de Jenkins, que inyecta los valores en tiempo de ejecución
sin exponerlos en el código fuente ni en los logs.

Crear **dos** credenciales del tipo **Secret text**:

**Credencial 1 — contraseña del usuario MySQL:**
1. **Jenkins → Administrar Jenkins → Credentials → System → Global credentials → Add Credentials**
2. Completar:
```
Kind: Secret text
Scope: Global
Secret: ccss_pass_123
ID: ccss-db-password
Description: Contraseña usuario MySQL del backend
```

**Credencial 2 — contraseña root de MySQL:**
1. Repetir el mismo proceso
2. Completar:
```
Kind: Secret text
Scope: Global
Secret: root_ccss_123
ID: ccss-db-root-password
Description: Contraseña root MySQL (health check)
```

> **Importante:** Los IDs `ccss-db-password` y `ccss-db-root-password` deben coincidir
> exactamente con los valores en `Jenkinsfile.backend` (bloque `environment`).
> Los valores de `Secret` son los que antes estaban hardcodeados en el Jenkinsfile.
> Ahora solo viven en Jenkins y nunca se suben al repositorio.

---

## Paso 4.2 — Agregar credenciales de correo (Gmail SMTP) en Jenkins

El pipeline inyecta la configuración SMTP al contenedor Docker en tiempo de ejecución.
Sin esto, la función de recuperación de contraseña no envía emails reales
(Laravel los envía al log por defecto).

El proyecto usa una cuenta Gmail con App Password de Google.

Crear **dos** credenciales del tipo **Secret text**:

**Credencial 1 — cuenta Gmail (usuario):**
```
Kind: Secret text
Scope: Global
Secret: practicarecuperacion.ccss@gmail.com
ID: ccss-mail-username
Description: Cuenta Gmail para envío de correos
```

**Credencial 2 — App Password de Gmail:**
```
Kind: Secret text
Scope: Global
Secret: pikdetumozzkbumr
ID: ccss-mail-password
Description: App Password Gmail para SMTP
```

> **Cómo obtener el App Password de Gmail:**
> 1. Ingresar a la cuenta Gmail → Manage your Google Account
> 2. Security → 2-Step Verification (debe estar activado)
> 3. App passwords → crear uno nuevo → copiar los 16 caracteres
>
> **Nota:** El App Password es distinto a la contraseña normal de Gmail.
> Si se desactiva la verificación en 2 pasos, el App Password deja de funcionar.

---

## Paso 5 — Crear el job de Jenkins para el backend

1. Ir a: **Jenkins → New Item**
2. Nombre: `CCSS-docker-jenkins-backend`
3. Tipo: **Pipeline**
4. OK

Dentro de la configuración del job:

**Sección Pipeline:**
- Definition: `Pipeline script from SCM`
- SCM: `Git`
- Repository URL: `https://github.com/voluntarios/ccss_consultory_bk.git`
- Credentials: seleccionar `H-github-ccss`
- Branch Specifier: `*/feat/deploy-backend`
- Script Path: `Jenkinsfile.backend`

5. **Save**

## Paso 6 — Preparar Docker para el backend (antes del primer Build)

Este paso va justo despues de crear el job. Aqui se valida que Jenkins pueda
escribir en su volumen y que Docker tenga listas las imagenes base.

Limpiar cache de git y workspace del job:
  ```powershell
  docker exec -u 0 jenkins sh -c "rm -rf /var/jenkins_home/caches/git-*"
  docker exec -u 0 jenkins sh -c "rm -rf /var/jenkins_home/workspace/CCSS-docker-jenkins-backend*"
  docker restart jenkins
  ```
- ejecutar **Build Now**.

## Paso 7 — Entender la estructura del repositorio

Antes de correr el pipeline, entender qué hace cada archivo:

```
ccss_consultory_bk/
├── Jenkinsfile.backend        ← Pipeline del backend (6 stages)
├── init/                      ← Código Laravel (app real)
│   ├── Dockerfile             ← Build de la imagen Docker del backend
│   ├── .dockerignore          ← Excluye vendor, .env, tests, etc.
│   ├── docker-entrypoint.sh   ← Limpia cache y regenera config al iniciar
│   ├── config/
│   │   └── cors.php           ← Permite CORS desde localhost:5173 (frontend Vite)
│   └── ...
└── docs/                      ← Documentación
```

> **Nota sobre `init/`:** El proyecto tiene una subcarpeta `init/` que contiene
> todo el código Laravel. El Dockerfile y el pipeline apuntan a esa subcarpeta,
> no a la raíz del repositorio.

---

## Paso 8 — Qué hace cada stage del pipeline

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
| `DB_PASSWORD` | credential `ccss-db-password` | Contraseña MySQL (guardada en Jenkins Credentials) |
| `DB_ROOT_PASSWORD` | credential `ccss-db-root-password` | Contraseña root MySQL (guardada en Jenkins Credentials) |
| `HOST_PORT` | `8000` | Puerto expuesto en el host |
| `CREDENTIALS_ID` | `H-github-ccss` | ID de la credential de GitHub en Jenkins |

> **Importante:** `DB_HOST` NO se define como variable global porque su valor
> es `${DB_CONTAINER_NAME}` (nombre del contenedor MySQL), que se pasa
> directamente como `-e DB_HOST=ccss-mysql` en el `docker run` del backend.
> Esto es lo que permite que los dos contenedores se comuniquen dentro de `ccss-net`.
>
> **Sobre DB_PASSWORD y DB_ROOT_PASSWORD:** Estas variables usan `credentials()` de Jenkins.
> Los valores reales se guardan en Jenkins Credentials (Paso 4.1) y Jenkins los inyecta
> en tiempo de ejecución. Nunca aparecen en el código fuente ni en los logs del pipeline.


# Solución de problemas comunes

**Pipeline falla en "Checkout": `Couldn't find any revision to build`**
- La rama en el job no coincide con la del repo
- Verificar que Branch Specifier sea `*/feat/deploy-backend`

**Pipeline falla al iniciar con `Could not create directory '/var/jenkins_home/caches/...'`**
- Es un problema de permisos/estado del volumen `jenkins_home`
- Reparar ownership dentro del contenedor:
  ```powershell
  docker exec -u 0 jenkins sh -c "chown -R 1000:1000 /var/jenkins_home"
  docker restart jenkins
  ```
- Si persiste, borrar y recrear `jenkins_home` (ver Paso 6.1)

**Pipeline falla en Checkout con `RPC failed; curl 92 HTTP/2 ... early EOF`**
- No es falta de imagen Docker del backend/MySQL.
- Es un corte de red durante `git fetch` (o cache/workspace corrupto en Jenkins).
- Forzar Git en Jenkins a usar HTTP/1.1 y tolerancia de transferencia:
  ```powershell
  docker exec -u 0 jenkins sh -c "git config --global http.version HTTP/1.1"
  docker exec -u 0 jenkins sh -c "git config --global http.postBuffer 524288000"
  docker exec -u 0 jenkins sh -c "git config --global core.compression 0"
  docker exec -u 0 jenkins sh -c "git config --global http.lowSpeedLimit 0"
  docker exec -u 0 jenkins sh -c "git config --global http.lowSpeedTime 999999"
  ```
- Limpiar cache de git y workspace del job:
  ```powershell
  docker exec -u 0 jenkins sh -c "rm -rf /var/jenkins_home/caches/git-*"
  docker exec -u 0 jenkins sh -c "rm -rf /var/jenkins_home/workspace/CCSS-docker-jenkins-backend*"
  docker restart jenkins
  ```
- Volver a ejecutar **Build Now**.

**Build Docker Image falla con `/script.sh: docker: not found` (exit code 127)**
- Jenkins tiene montado `docker.sock`, pero no tiene Docker CLI instalado.
- Solucion: reconstruir Jenkins usando imagen `ccss-jenkins-docker:lts` (Paso 2).
- Verificar dentro del contenedor:
  ```powershell
  docker exec jenkins docker --version
  ```
- Si `docker --version` sigue fallando, borrar la imagen vieja y reconstruir sin cache:
  ```powershell
  docker rm -f jenkins
  docker rmi ccss-jenkins-docker:lts
  docker build --no-cache -t ccss-jenkins-docker:lts -f .\Dockerfile.jenkins .
  docker run -d `
    --name jenkins `
    --restart unless-stopped `
    --user root `
    -p 8080:8080 `
    -p 50000:50000 `
    -v jenkins_home:/var/jenkins_home `
    -v /var/run/docker.sock:/var/run/docker.sock `
    ccss-jenkins-docker:lts
  docker exec jenkins docker --version
  ```

**Build Docker Image falla con `permission denied while trying to connect to the Docker daemon socket`**
- Jenkins ya tiene Docker CLI, pero el usuario del proceso no puede abrir `/var/run/docker.sock`.
- En Docker Desktop para Windows, ese socket puede quedar como `root:root` con permisos `660`.
- Si antes te funcionaba sin `root`, no cambies esa configuracion: `root` no es requisito del proyecto.
- Solo si este error aparece en tu maquina actual, usar `root` como workaround temporal.
- Esta solucion es aceptable para laboratorio local, pero no recomendada para produccion.
- Verificar dentro del contenedor (usuario y permisos):
  ```powershell
  docker inspect jenkins --format "User={{.Config.User}} Image={{.Config.Image}}"
  docker exec jenkins sh -c "id; ls -l /var/run/docker.sock"
  ```
- Workaround temporal si necesitas destrabar la prueba local:
  ```powershell
  docker rm -f jenkins
  docker run -d `
    --name jenkins `
    --restart unless-stopped `
    --user root `
    -p 8080:8080 `
    -p 50000:50000 `
    -v jenkins_home:/var/jenkins_home `
    -v /var/run/docker.sock:/var/run/docker.sock `
    ccss-jenkins-docker:lts
  ```

**Pipeline falla en "Provision Database": el loop de espera agota los 40 intentos**
- MySQL tardó más de 2 minutos en inicializar
- Aumentar el loop de 40 a 60 iteraciones en el Jenkinsfile
- O revisar si hay un contenedor MySQL zombie: `docker rm -f ccss-mysql`

**Pipeline falla en "Migrate and Seed" con `SQLSTATE Connection refused`**
- El backend no puede conectarse a MySQL
- Verificar que ambos contenedores están en `ccss-net`: `docker network inspect ccss-net`
- Verificar que el contenedor MySQL está corriendo: `docker ps | grep ccss-mysql`

**Pipeline falla con `SQLSTATE database.sqlite does not exist`**
- Laravel está usando config cacheada vieja con `DB_CONNECTION=sqlite`
- El stage Migrate ya incluye `config:clear` + `config:cache`, pero si persiste:
  ```powershell
  docker exec ccss-backend-app php artisan config:clear
  docker exec ccss-backend-app php artisan cache:clear
  docker exec ccss-backend-app php artisan config:cache
  ```

**Smoke Test falla con `curl: (7) Failed to connect`**
- El servidor aún no terminó de arrancar
- Es normal en la primera arrancada; el pipeline ya tiene `sleep 8` antes del curl

**Pipeline SUCCESS pero frontend recibe SQLSTATE al intentar login**
- El contenedor de una corrida anterior está corriendo sin MySQL
- Siempre ejecutar **Build Now** para forzar el recreado del stack completo

---

## Comandos de diagnóstico rápido

```powershell
# Ver estado de todos los contenedores del proyecto
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# Ver logs del backend en tiempo real
docker logs -f ccss-backend-app

# Ver logs de MySQL
docker logs -f ccss-mysql

# Ver logs de los últimos errores del backend
docker logs --tail 100 ccss-backend-app

# Inspeccionar variables de entorno que recibió el contenedor backend
docker inspect ccss-backend-app | grep -A 30 '"Env"'

# Verificar que ambos contenedores están en la misma red
docker network inspect ccss-net

# Ejecutar un comando dentro del contenedor backend
docker exec ccss-backend-app php artisan tinker

# Limpiar todos los contenedores del proyecto (para empezar desde cero)
docker rm -f ccss-backend-app ccss-mysql
docker network rm ccss-net
```

---

## Referencia rápida de credenciales de prueba

Las seeders crean estas cuentas después de `php artisan db:seed`:

| Rol | Email | Contraseña |
|---|---|---|
| Administrador | admin@ccss.cr | Admin1234! |
| Médico 1 | doctor1@ccss.cr | Doctor1234! |
| Médico 2 | doctor2@ccss.cr | Doctor1234! |
| Enfermero | nurse1@ccss.cr | Nurse1234! |
