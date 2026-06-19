# Secuencia de restablecimiento de contraseña

Este documento explica el flujo completo desde que el usuario solicita el restablecimiento hasta que la contraseña se actualiza en base de datos.

## Endpoints involucrados

- `POST /api/auth/forgot-password`
- `POST /api/auth/reset-password`

Definidos en [public/index.php](../public/index.php), implementados en [controllers/AuthController.php](../controllers/AuthController.php).

---

## 1) Solicitud de restablecimiento

### Request
`POST /api/auth/forgot-password`

```json
{
  "email": "usuario@correo.com"
}
```

### Proceso interno
1. Se valida que `email` exista y tenga formato correcto.
2. Se busca el usuario por correo.
3. Si no existe, **igual** se responde éxito genérico (para evitar enumeración de usuarios).
4. Si existe:
   - Se eliminan tokens anteriores de ese correo en `password_reset_tokens` (`PasswordResetRepository::deleteByEmail`).
   - Se genera un token en texto plano (`bin2hex(random_bytes(32))`, 64 caracteres hex).
   - Se crea el hash `sha256(token_plano)`.
   - Se guarda en `password_reset_tokens`:
     - `email`
     - `token` (hash, no el token plano)
     - `created_at` (generado en PHP, no con `NOW()` de MySQL, para que la comparación de expiración use siempre el mismo reloj sin importar la zona horaria del servidor de base de datos)
   - Se arma URL de frontend:
     - `${FRONTEND_URL}/reset-password?token=<token_plano>`
   - Se envía el correo con `core/Mailer.php` (`Mailer::sendPasswordReset`): en modo `log` escribe el HTML en `storage/logs/mail.log`, en otro caso usa la función nativa `mail()`.

### Respuesta API
Siempre retorna mensaje genérico:

```json
{
  "message": "Si este correo está registrado, recibirás un enlace para restablecer tu contraseña en breve."
}
```

---

## 2) Usuario recibe el correo

1. El usuario abre el correo.
2. Hace click en **Restablecer contraseña**.
3. El frontend recibe el `token` por query string (`?token=...`).
4. El frontend muestra formulario de nueva contraseña.

---

## 3) Confirmación de nueva contraseña

### Request
`POST /api/auth/reset-password`

```json
{
  "token": "TOKEN_PLANO_DEL_LINK",
  "password": "NuevaClaveSegura123"
}
```

### Proceso interno
1. Se valida `token` y `password` (`min:8`).
2. Se calcula hash `sha256(token)`.
3. Se busca el registro en `password_reset_tokens` por ese hash.
4. Si no existe, retorna `422` (token inválido).
5. Se valida expiración por `created_at`.
   - Actualmente el backend expira en **60 minutos** (`config('auth.password_reset_expire_minutes')`).
6. Se busca usuario por `email` del registro de token.
7. Si existe:
   - Se actualiza `users.password` con `password_hash($password, PASSWORD_BCRYPT)` (`UserRepository::updatePassword`).
   - Se revocan todos sus tokens API (`Auth::revokeAllTokens($userId)` → borra sus filas en `personal_access_tokens`).
   - Se elimina el token usado de `password_reset_tokens`.
8. Se retorna éxito.

### Respuestas principales
- `422`: token inválido
- `422`: token expirado
- `422`: usuario no encontrado para el token
- `200`: contraseña actualizada correctamente

---

## 4) Cambios en base de datos

### Tabla `password_reset_tokens`
- **Insert** al solicitar restablecimiento.
- **Delete** al generar uno nuevo para el mismo correo.
- **Delete** cuando token expira o cuando se usa correctamente.

### Tabla `users`
- **Update** de `password` al confirmar nueva contraseña.

### Tabla `personal_access_tokens`
- **Delete** de tokens activos del usuario al cambiar contraseña (cerrar sesiones API).

---

## Configuración de correo (`.env`)

Ejemplo para usar SMTP real en vez del driver `log` (ver [config/config.php](../config/config.php)):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_correo@gmail.com
MAIL_PASSWORD=tu_app_password
MAIL_FROM_ADDRESS=tu_correo@gmail.com
MAIL_FROM_NAME=API
```