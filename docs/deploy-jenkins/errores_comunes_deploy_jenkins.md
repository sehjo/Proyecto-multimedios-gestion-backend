

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
