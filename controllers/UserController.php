<?php

class UserController
{
    /**
     * Paginated users with optional filters (name, role, status) and per_page.
     * Requires users.read.
     */
    public function index(Request $request): void
    {
        if (!Guard::permission($request, 'users.read')) {
            return;
        }

        $params = Paginator::params($request, 15);
        $filters = [
            'name' => $request->query('name'),
            'role' => $request->query('role'),
            'status' => $request->query('status'),
        ];

        $users = UserRepository::paginateFiltered($filters, $params['offset'], $params['perPage']);
        $total = UserRepository::countFiltered($filters);

        Response::json([
            'data' => UserResource::collection($users),
            'meta' => Paginator::meta($total, $params['page'], $params['perPage']),
        ]);
    }

    /**
     * User count per role (for dashboards). Requires users.read.
     */
    public function statsByRole(Request $request): void
    {
        if (!Guard::permission($request, 'users.read')) {
            return;
        }

        Response::json(['success' => true, 'data' => UserRepository::countByRole()]);
    }

    /**
     * User count per status (ACTIVE / INACTIVE). Requires users.read.
     */
    public function statsByStatus(Request $request): void
    {
        if (!Guard::permission($request, 'users.read')) {
            return;
        }

        Response::json(['success' => true, 'data' => UserRepository::countByStatus()]);
    }

    /**
     * Create a user with a role (user_type_id) and ACTIVE status. Requires users.create.
     */
    public function store(Request $request): void
    {
        if (!$actor = Guard::permission($request, 'users.create')) {
            return;
        }

        $data = $request->all();

        $errors = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'user_type_id' => ['required', 'integer', 'exists:users_types,id'],
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);

            return;
        }

        $data['status'] = 'ACTIVE';
        $user = UserRepository::create($data);

        AuditLogger::userLog('CREATE', $actor, $user, [
            'name' => ['old' => null, 'new' => $user->getName()],
            'lastname' => ['old' => null, 'new' => $user->getLastname()],
            'email' => ['old' => null, 'new' => $user->getEmail()],
            'role' => ['old' => null, 'new' => $user->getRoleName()],
            'status' => ['old' => null, 'new' => $user->getStatus()],
        ]);

        Response::json(UserResource::toArray($user));
    }

    /**
     * Show a user by id. Requires users.read.
     */
    public function show(Request $request, $id): void
    {
        if (!Guard::permission($request, 'users.read')) {
            return;
        }

        $user = UserRepository::findById((int) $id);

        if (!$user) {
            Response::json(['message' => 'Usuario no encontrado.'], 404);

            return;
        }

        Response::json(UserResource::toArray($user));
    }

    /**
     * Update a user's data (never the role here). Requires users.update.
     * Self-protection: a user cannot edit their own account.
     */
    public function update(Request $request, $id): void
    {
        if (!$actor = Guard::permission($request, 'users.update')) {
            return;
        }

        $user = UserRepository::findById((int) $id);

        if (!$user) {
            Response::json(['message' => 'Usuario no encontrado.'], 404);

            return;
        }

        if ($actor->getId() === $user->getId()) {
            Response::error('No puedes editar tu propia cuenta.', 'SELF_ACTION_FORBIDDEN', 403);

            return;
        }

        $data = $request->all();

        $errors = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->getId()],
            'password' => ['sometimes', 'string', 'min:8', 'max:255'],
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);

            return;
        }

        // The role is managed via the roles endpoints; ignore it here.
        unset($data['user_type_id'], $data['role']);

        $old = [
            'name' => $user->getName(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
        ];

        $updated = UserRepository::update((int) $id, $data);

        $fields = AuditLogger::diff($old, [
            'name' => $updated->getName(),
            'lastname' => $updated->getLastname(),
            'email' => $updated->getEmail(),
        ]);
        if (array_key_exists('password', $data) && $data['password'] !== null && $data['password'] !== '') {
            $fields['password'] = AuditLogger::maskedPassword();
        }

        if ($fields !== []) {
            AuditLogger::userLog('UPDATE', $actor, $updated, $fields);
        }

        Response::json(UserResource::toArray($updated));
    }

    /**
     * Activate / deactivate a user. Direction-based permission:
     *   → INACTIVE requires users.delete; → ACTIVE requires users.update.
     * Guards: self-protection and anti-lockout (last active admin).
     */
    public function changeStatus(Request $request, $id): void
    {
        // Must be authenticated first; the per-direction permission is checked below.
        if (!$actor = Guard::user($request)) {
            return;
        }

        $newStatus = is_string($request->input('status')) ? strtoupper($request->input('status')) : '';
        if (!in_array($newStatus, ['ACTIVE', 'INACTIVE'], true)) {
            Response::validationError(['status' => ['El campo status debe ser ACTIVE o INACTIVE.']]);

            return;
        }

        $user = UserRepository::findById((int) $id);

        if (!$user) {
            Response::json(['message' => 'Usuario no encontrado.'], 404);

            return;
        }

        $oldStatus = $user->getStatus();

        // Self-protection: a user cannot change their own status.
        if ($actor->getId() === $user->getId()) {
            Response::error('No puedes cambiar tu propio estado.', 'SELF_ACTION_FORBIDDEN', 403);

            return;
        }

        // Direction-based permission.
        if ($newStatus === 'INACTIVE' && !Auth::can($actor, 'users.delete')) {
            Response::forbidden('No tienes permiso para desactivar usuarios.');

            return;
        }
        if ($newStatus === 'ACTIVE' && !Auth::can($actor, 'users.update')) {
            Response::forbidden('No tienes permiso para activar usuarios.');

            return;
        }

        // No-op: same status requested.
        if ($newStatus === $oldStatus) {
            Response::json(UserResource::toArray($user));

            return;
        }

        // Anti-lockout: cannot deactivate the last active administrator.
        if ($newStatus === 'INACTIVE' && self::isLastActiveAdmin($user)) {
            Response::error('No puedes desactivar al último administrador activo.', 'LAST_ADMIN', 409);

            return;
        }

        $updated = UserRepository::updateStatus((int) $id, $newStatus);

        // Deactivating closes the user's sessions.
        if ($newStatus === 'INACTIVE') {
            Auth::revokeAllTokens($user->getId());
        }

        AuditLogger::userLog(
            $newStatus === 'INACTIVE' ? 'INACTIVE' : 'REACTIVE',
            $actor,
            $updated,
            ['status' => ['old' => $oldStatus, 'new' => $newStatus]]
        );

        Response::json(UserResource::toArray($updated));
    }

    /**
     * Delete a user. Requires users.delete. Self-protection + anti-lockout apply.
     */
    public function destroy(Request $request, $id): void
    {
        if (!$actor = Guard::permission($request, 'users.delete')) {
            return;
        }

        $user = UserRepository::findById((int) $id);

        if (!$user) {
            Response::json(['message' => 'Usuario no encontrado.'], 404);

            return;
        }

        if ($actor->getId() === $user->getId()) {
            Response::error('No puedes eliminar tu propia cuenta.', 'SELF_ACTION_FORBIDDEN', 403);

            return;
        }

        if (self::isLastActiveAdmin($user)) {
            Response::error('No puedes eliminar al último administrador activo.', 'LAST_ADMIN', 409);

            return;
        }

        UserRepository::delete((int) $id);
        AuditLogger::userLog('DELETE', $actor, $user, []);

        Response::noContent();
    }

    /**
     * True when the given user is an ACTIVE administrator and the only one left.
     */
    private static function isLastActiveAdmin(User $user): bool
    {
        $adminRole = RoleRepository::findByName('Administrador');

        if (!$adminRole || $user->getUserTypeId() !== $adminRole->getId()) {
            return false;
        }

        if ($user->getStatus() !== 'ACTIVE') {
            return false;
        }

        return UserRepository::activeAdminCount($adminRole->getId()) <= 1;
    }
}
