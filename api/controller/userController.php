<?php

require_once __DIR__ . '/../views/helpers.php';
require_once __DIR__ . '/../dao/userDao.php';
require_once __DIR__ . '/../dao/authDao.php';
require_once __DIR__ . '/../dao/permissionDao.php';
require_once __DIR__ . '/../models/user.php';
require_once __DIR__ . '/../config/mailer.php';

class UserController
{
    private UserDao $dao;
    private PermissionDao $permisoDao;

    public function __construct()
    {
        $this->dao       = new UserDao();
        $this->permisoDao = new PermissionDao();
    }

    // -----------------------------------------------------------------------
    // GET /api/v1/users
    // -----------------------------------------------------------------------
    public function index(): void
    {
        requireAuth();

        $perPage = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 15;
        $page    = isset($_GET['page'])     ? max(1, (int) $_GET['page'])     : 1;
        $offset  = ($page - 1) * $perPage;

        $filters = [
            'name'   => $_GET['name']   ?? null,
            'status' => $_GET['status'] ?? null,
        ];

        $usuarios = $this->dao->index($perPage, $offset, $filters);
        $total    = $this->dao->countTotal($filters);

        foreach ($usuarios as &$u) {
            $u['roles'] = $this->dao->userRoles((int) $u['id']);
        }

        jsonResponse('success', 'Usuarios obtenidos correctamente.', $usuarios, null, [
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ],
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /api/v1/users/register  (public)
    // -----------------------------------------------------------------------
    public function register(): void
    {
        $json    = getJsonInput();
        $errors = validar($json, [
            'name'       => 'required|max:255',
            'identifier' => 'required|max:50',
            'email'      => 'required|email|max:254',
            'password'   => 'required|min:8|password_strength',
        ]);

        if ($this->dao->emailExists($json['email'] ?? '')) {
            $errors['email'][] = 'El correo ya está registrado.';
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $newId = $this->dao->store([
            'name'       => $json['name'],
            'identifier' => $json['identifier'],
            'email'      => $json['email'],
            'password'   => $json['password'],
            'status'     => 'inactive',
            'is_guest'   => 0,
        ]);

        $created = $this->dao->findById($newId);
        $created['roles'] = $this->dao->userRoles($newId);

        jsonResponse('success', 'Usuario registrado correctamente.', $created, null, null, 201);
    }

    // -----------------------------------------------------------------------
    // POST /api/v1/users  (admin)
    // -----------------------------------------------------------------------
    public function store(): void
    {
        $actor   = requireAuth();
        $json    = getJsonInput();
        $errors = validar($json, [
            'name'       => 'required|max:255',
            'identifier' => 'required|max:50',
            'email'      => 'required|email|max:254',
            'password'   => 'required|min:8|password_strength',
            'status'     => 'required|in:active,inactive',
        ]);

        if ($this->dao->emailExists($json['email'] ?? '')) {
            $errors['email'][] = 'El correo ya está registrado.';
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $passwordPlain = $json['password'];
        $newId       = $this->dao->store($json);
        $created         = $this->dao->findById($newId);
        $created['roles'] = $this->dao->userRoles($newId);

        $this->dao->logAction(
            $actor['id'] ?? null, $newId, 'Create',
            json_encode(['email' => ['from' => null, 'to' => $created['email']]])
        );

        try {
            (new Mailer())->send(
                $created['email'],
                '[MediCode] Tu cuenta ha sido creada',
                $this->buildCredentialsTemplate($created['name'], $created['email'], $passwordPlain)
            );
        } catch (\RuntimeException) {
            // Email fails silently; the user has already been created
        }

        jsonResponse('success', 'Usuario creado correctamente.', $created, null, null, 201);
    }

    // -----------------------------------------------------------------------
    // GET /api/v1/users/{id}
    // -----------------------------------------------------------------------
    public function show(int $id): void
    {
        requireAuth();

        $user = $this->dao->findById($id);
        if (!$user) {
            jsonResponse('error', 'Usuario no encontrado.', null, null, null, 404);
        }

        $user['roles']       = $this->dao->userRoles($id);
        $user['permissions'] = $this->permisoDao->userDirectPermissions($id);
        jsonResponse('success', 'Usuario obtenido correctamente.', $user);
    }

    // -----------------------------------------------------------------------
    // PUT /api/v1/users/{id}
    // -----------------------------------------------------------------------
    public function update(int $id): void
    {
        $actor = requireAuth();

        // Security check before any DB lookup or input validation
        if ((int) $actor['id'] === $id) {
            jsonResponse('error', 'No puedes editar tu propia cuenta desde este endpoint.', null, null, null, 403);
        }

        $user = $this->dao->findById($id);

        if (!$user) {
            jsonResponse('error', 'Usuario no encontrado.', null, null, null, 404);
        }

        $json    = getJsonInput();
        $errors = validar($json, [
            'name'  => 'required|max:255',
            'email' => 'required|email|max:254',
        ]);

        if (($json['email'] ?? '') !== $user['email'] && $this->dao->emailExists($json['email'] ?? '', $id)) {
            $errors['email'][] = 'El correo ya está registrado.';
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $changes = [];
        foreach (['name', 'email'] as $c) {
            if (isset($json[$c]) && $json[$c] !== $user[$c]) {
                $changes[$c] = ['from' => $user[$c], 'to' => $json[$c]];
            }
        }

        $this->dao->updateData($id, $json);
        $updated = $this->dao->findById($id);
        $updated['roles'] = $this->dao->userRoles($id);

        if ($changes) {
            $this->dao->logAction($actor['id'] ?? null, $id, 'Update', json_encode($changes));
        }

        jsonResponse('success', 'Usuario actualizado correctamente.', $updated);
    }

    // -----------------------------------------------------------------------
    // PATCH /api/v1/users/{id}/status
    // -----------------------------------------------------------------------
    public function changeStatus(int $id): void
    {
        $actor   = requireAuth();
        $json    = getJsonInput();
        $errors = validar($json, ['status' => 'required|in:active,inactive']);

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $user = $this->dao->findById($id);
        if (!$user) {
            jsonResponse('error', 'Usuario no encontrado.', null, null, null, 404);
        }

        // Last-admin guard takes precedence over self-modification check:
        // if the actor is the last admin trying to deactivate themselves, 409 wins over 403.
        if ($json['status'] === 'inactive' && $this->dao->hasAdminRole($id)) {
            if ($this->dao->countActiveAdmins() <= 1) {
                jsonResponse('error', 'No se puede desactivar al último administrador activo.', null, null, null, 409);
            }
        }

        if ((int) $actor['id'] === $id) {
            jsonResponse('error', 'No puedes cambiar tu propio estado.', null, null, null, 403);
        }

        $oldStatus = $user['status'];
        $this->dao->updateStatus($id, $json['status']);

        if ($json['status'] === 'inactive') {
            $authDao = new AuthDao();
            $authDao->revokeAllTokens($id);
        }

        $this->dao->logAction(
            $actor['id'] ?? null, $id,
            $json['status'] === 'inactive' ? 'Deactivate' : 'Activate',
            json_encode(['status' => ['from' => $oldStatus, 'to' => $json['status']]])
        );

        $updated = $this->dao->findById($id);
        $updated['roles'] = $this->dao->userRoles($id);
        jsonResponse('success', 'Estado del usuario actualizado correctamente.', $updated);
    }

    // -----------------------------------------------------------------------
    // POST /api/v1/users/{id}/roles
    // -----------------------------------------------------------------------
    public function assignRole(int $id): void
    {
        $actor = requireAuth();

        // Security: prevent self-role assignment
        if ((int) $actor['id'] === $id) {
            jsonResponse('error', 'No puedes asignarte roles a ti mismo.', null, null, null, 403);
        }

        // Only admins/super-admins may assign roles to others
        $actorRoleNames = array_column($this->dao->userRoles((int) $actor['id']), 'name');
        $isAdmin = false;
        foreach ($actorRoleNames as $nombre) {
            if (str_contains(strtolower($nombre), 'admin')) { $isAdmin = true; break; }
        }
        if (!$isAdmin) {
            jsonResponse('error', 'No tienes permisos para asignar roles.', null, null, null, 403);
        }

        $json    = getJsonInput();

        // Reject array values for 'role' — must be a scalar
        if (isset($json['role']) && is_array($json['role'])) {
            jsonResponse('error', 'Error de validación.', null, [
                'role' => ['El campo role debe ser un valor escalar, no un arreglo.'],
            ], null, 422);
        }

        $errors = validar($json, ['role' => 'required']);

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $user = $this->dao->findById($id);
        if (!$user) {
            jsonResponse('error', 'Usuario no encontrado.', null, null, null, 404);
        }

        $roleInput = $json['role'];

        $role = is_numeric($roleInput)
            ? $this->dao->findRoleById((int) $roleInput)
            : $this->dao->findRoleByName((string) $roleInput);

        if (!$role) {
            jsonResponse('error', 'Rol no encontrado.', null, null, null, 404);
        }

        // Privilege escalation guard: assigning a 'super-*' role requires the actor to hold one.
        // Check is done against the resolved role name (works for both numeric ID and name input).
        if (str_contains(strtolower($role['name']), 'super')) {
            $hasSuperRole = array_filter(
                $actorRoleNames,
                fn($n) => str_contains(strtolower($n), 'super')
            );
            if (empty($hasSuperRole)) {
                jsonResponse('error', 'No tienes permisos para asignar este rol.', null, null, null, 403);
            }
        }

        $this->dao->assignRole($id, (int) $role['id']);
        $this->dao->logAction(
            $actor['id'] ?? null, $id, 'RoleAssigned',
            json_encode(['role' => ['from' => null, 'to' => $role['name']]])
        );

        $user['roles'] = $this->dao->userRoles($id);
        jsonResponse('success', 'Rol asignado correctamente.', $user);
    }

    // -----------------------------------------------------------------------
    // DELETE /api/v1/users/{id}/roles/{role}
    // -----------------------------------------------------------------------
    public function revokeRole(int $userId, string $rolParam): void
    {
        $actor = requireAuth();

        // Security check before any DB lookup
        if ((int) $actor['id'] === $userId) {
            jsonResponse('error', 'No puedes revocar tus propios roles.', null, null, null, 403);
        }

        $role = is_numeric($rolParam)
            ? $this->dao->findRoleById((int) $rolParam)
            : $this->dao->findRoleByName(urldecode($rolParam));

        if (!$role) {
            jsonResponse('error', 'Rol no encontrado.', null, null, null, 404);
        }

        $user = $this->dao->findById($userId);

        if (!$user) {
            jsonResponse('error', 'Usuario no encontrado.', null, null, null, 404);
        }

        $currentRoles = $this->dao->userRoles($userId);
        if (count($currentRoles) <= 1) {
            jsonResponse('error', 'No se puede revocar el único rol del usuario.', null, null, null, 409);
        }

        $revoked = $this->dao->revokeRole($userId, (int) $role['id']);
        if (!$revoked) {
            jsonResponse('error', 'El usuario no tenía ese rol asignado.', null, null, null, 422);
        }

        $this->dao->logAction(
            $actor['id'] ?? null, $userId, 'RoleRevoked',
            json_encode(['role' => ['from' => $role['name'], 'to' => null]])
        );

        http_response_code(204);
        exit;
    }

    // -----------------------------------------------------------------------
    // POST /api/v1/users/{id}/permissions
    // -----------------------------------------------------------------------
    public function assignPermission(int $id): void
    {
        $actor   = requireAuth();
        $json    = getJsonInput();
        $errors = validar($json, ['permission' => 'required']);

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $user = $this->dao->findById($id);
        if (!$user) {
            jsonResponse('error', 'Usuario no encontrado.', null, null, null, 404);
        }

        $permission = is_numeric($json['permission'])
            ? $this->permisoDao->findById((int) $json['permission'])
            : $this->permisoDao->findByName($json['permission']);

        if (!$permission) {
            jsonResponse('error', 'Permiso no encontrado.', null, null, null, 404);
        }

        // Delegation check: actor must possess this permission as a direct (non-role) assignment
        $permisosDirectos = array_column($this->permisoDao->userDirectPermissions((int) $actor['id']), 'name');
        if (!in_array($permission['name'], $permisosDirectos, true)) {
            jsonResponse('error', 'No puedes delegar permisos que no posees directamente.', null, null, null, 403);
        }

        $this->permisoDao->assignPermissionToUser($id, (int) $permission['id']);
        $this->dao->logAction(
            $actor['id'] ?? null, $id, 'PermissionAssigned',
            json_encode(['permission' => ['from' => null, 'to' => $permission['name']]])
        );

        $user['permissions'] = $this->permisoDao->userDirectPermissions($id);
        jsonResponse('success', 'Permiso asignado correctamente.', $user);
    }

    // -----------------------------------------------------------------------
    // DELETE /api/v1/users/{id}/permissions/{permission}
    // -----------------------------------------------------------------------
    public function revokePermission(int $userId, string $permisoParam): void
    {
        $actor   = requireAuth();
        $user = $this->dao->findById($userId);

        if (!$user) {
            jsonResponse('error', 'Usuario no encontrado.', null, null, null, 404);
        }

        $permission = is_numeric($permisoParam)
            ? $this->permisoDao->findById((int) $permisoParam)
            : $this->permisoDao->findByName(urldecode($permisoParam));

        if (!$permission) {
            jsonResponse('error', 'Permiso no encontrado.', null, null, null, 404);
        }

        $revoked = $this->permisoDao->revokePermissionFromUser($userId, (int) $permission['id']);
        if (!$revoked) {
            jsonResponse('error', 'El usuario no tenía ese permiso asignado directamente.', null, null, null, 422);
        }

        $this->dao->logAction(
            $actor['id'] ?? null, $userId, 'PermissionRevoked',
            json_encode(['permission' => ['from' => $permission['name'], 'to' => null]])
        );

        http_response_code(204);
        exit;
    }

    // -----------------------------------------------------------------------
    // GET /api/v1/stats/users/status
    // -----------------------------------------------------------------------
    public function statsByStatus(): void
    {
        requireAuth();
        jsonResponse('success', 'Usuarios por estado obtenidos correctamente.', $this->dao->countByStatus());
    }

    // -----------------------------------------------------------------------
    // GET /api/v1/stats/users/by-role
    // -----------------------------------------------------------------------
    public function statsByRole(): void
    {
        requireAuth();
        jsonResponse('success', 'Usuarios por rol obtenidos correctamente.', $this->dao->countByRole());
    }

    // -----------------------------------------------------------------------
    // GET /api/v1/users/logs
    // -----------------------------------------------------------------------
    public function logs(): void
    {
        requireAuth();

        $perPage = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 50;
        $page    = isset($_GET['page'])     ? max(1, (int) $_GET['page'])     : 1;
        $offset  = ($page - 1) * $perPage;
        $filters = [
            'user_id'   => $_GET['user_id']   ?? null,
            'action'    => $_GET['action']     ?? null,
            'date_from' => $_GET['date_from']  ?? null,
            'date_to'   => $_GET['date_to']    ?? null,
        ];

        $datos = $this->dao->listLogs($perPage, $offset, $filters);
        $total = $this->dao->countLogs($filters);

        jsonResponse('success', 'Logs de usuarios obtenidos correctamente.', $datos, null, [
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ],
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /api/v1/users/logs/{id}
    // -----------------------------------------------------------------------
    public function showLog(int $id): void
    {
        requireAuth();
        $log = $this->dao->findLogById($id);
        if (!$log) {
            jsonResponse('error', 'Log no encontrado.', null, null, null, 404);
        }
        jsonResponse('success', 'Log obtenido correctamente.', $log);
    }

    // -----------------------------------------------------------------------
    // Credentials email template
    // -----------------------------------------------------------------------
    private function buildCredentialsTemplate(string $nombre, string $email, string $password): string
    {
        return <<<HTML
        <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>
        <body style="font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0;">
          <div style="max-width:600px;margin:40px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);">
            <div style="background:#1a56db;padding:32px;text-align:center;">
              <h1 style="color:#fff;margin:0;font-size:24px;">MediCode UCR</h1>
            </div>
            <div style="padding:32px;color:#333;">
              <p>Hola, <strong>{$nombre}</strong>.</p>
              <p>Un administrador creó una cuenta para ti en MediCode. Usa las siguientes credenciales para iniciar sesión:</p>
              <div style="background:#f3f4f6;border-radius:6px;padding:20px 24px;margin:24px 0;">
                <p style="margin:0 0 12px 0;"><strong>Correo:</strong> {$email}</p>
                <p style="margin:0;"><strong>Contraseña temporal:</strong>
                  <span style="font-family:'Courier New',monospace;font-size:16px;color:#1a56db;">{$password}</span>
                </p>
              </div>
              <div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:12px 16px;border-radius:4px;font-size:14px;color:#92400e;">
                <strong>Importante:</strong> por tu seguridad, cambia esta contraseña la primera vez que inicies sesión.
              </div>
              <p style="margin-top:24px;font-size:14px;color:#6b7280;">Si no esperabas esta cuenta, comunícate con el administrador del sistema.</p>
            </div>
            <div style="padding:24px 32px;border-top:1px solid #e5e7eb;font-size:12px;color:#9ca3af;text-align:center;">
              MediCode — Sistema de Citas Médicas UCR &bull; Correo automático, no responder.
            </div>
          </div>
        </body></html>
        HTML;
    }
}
