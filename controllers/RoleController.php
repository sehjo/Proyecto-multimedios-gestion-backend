<?php

/**
 * Role management. Roles are users_types rows; permissions hang off them.
 * Mirrors the Laravel RoleController (CRUD + permission catalog + assign role
 * to a user). All actions are guarded by roles.* / users.* permissions.
 */
class RoleController
{
    /**
     * List roles (paginated). Requires roles.read.
     */
    public function index(Request $request): void
    {
        if (!Guard::permission($request, 'roles.read')) {
            return;
        }

        $params = Paginator::params($request, 15);
        $roles = RoleRepository::paginate($params['offset'], $params['perPage']);
        $total = RoleRepository::count();

        // Include each role's permission names.
        $data = array_map(
            fn (UserType $role) => RoleResource::toArray($role, RoleRepository::permissions($role->getId())),
            $roles
        );

        Response::json([
            'data' => $data,
            'meta' => Paginator::meta($total, $params['page'], $params['perPage']),
        ]);
    }

    /**
     * Full permission catalog (for the role editor grid). Requires roles.read.
     */
    public function permissions(Request $request): void
    {
        if (!Guard::permission($request, 'roles.read')) {
            return;
        }

        Response::json(['data' => PermissionRepository::allNames()]);
    }

    /**
     * Show a role with its permissions. Requires roles.read.
     */
    public function show(Request $request, $id): void
    {
        if (!Guard::permission($request, 'roles.read')) {
            return;
        }

        $role = RoleRepository::findById((int) $id);

        if (!$role) {
            Response::json(['message' => 'Rol no encontrado.'], 404);

            return;
        }

        Response::json(RoleResource::toArray($role, RoleRepository::permissions($role->getId())));
    }

    /**
     * Create a role and assign its permissions. Requires roles.create.
     */
    public function store(Request $request): void
    {
        if (!$actor = Guard::permission($request, 'roles.create')) {
            return;
        }

        $data = $request->all();

        $errors = Validator::make($data, [
            'name' => ['required', 'string', 'max:255', 'unique:users_types,name'],
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);

            return;
        }

        // A role must hold at least one (valid) permission. The Validator has no
        // 'array'/'min' rule for lists, so this is checked explicitly here.
        $permissions = self::validPermissions($data['permissions'] ?? []);

        if ($permissions === []) {
            Response::validationError(['permissions' => ['El rol debe tener al menos un permiso válido.']]);

            return;
        }

        $role = RoleRepository::create($data['name'], $permissions);

        AuditLogger::roleLog('CREATE', $actor, $role, [
            'name' => ['old' => null, 'new' => $role->getName()],
            'permissions' => ['old' => null, 'new' => RoleRepository::permissions($role->getId())],
        ]);

        Response::json(RoleResource::toArray($role, RoleRepository::permissions($role->getId())));
    }

    /**
     * Update a role's name and/or permissions. Requires roles.update.
     */
    public function update(Request $request, $id): void
    {
        if (!$actor = Guard::permission($request, 'roles.update')) {
            return;
        }

        $role = RoleRepository::findById((int) $id);

        if (!$role) {
            Response::json(['message' => 'Rol no encontrado.'], 404);

            return;
        }

        $data = $request->all();

        $errors = Validator::make($data, [
            'name' => ['required', 'string', 'max:255', 'unique:users_types,name,' . $role->getId()],
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);

            return;
        }

        $oldName = $role->getName();
        $oldPermissions = RoleRepository::permissions($role->getId());

        $updated = RoleRepository::update((int) $id, $data['name']);

        if (array_key_exists('permissions', $data)) {
            RoleRepository::syncPermissions((int) $id, self::validPermissions($data['permissions']));
        }

        $newPermissions = RoleRepository::permissions((int) $id);

        $fields = AuditLogger::diff(['name' => $oldName], ['name' => $updated->getName()]);
        if ($oldPermissions !== $newPermissions) {
            $fields['permissions'] = ['old' => $oldPermissions, 'new' => $newPermissions];
        }
        if ($fields !== []) {
            AuditLogger::roleLog('UPDATE', $actor, $updated, $fields);
        }

        Response::json(RoleResource::toArray($updated, $newPermissions));
    }

    /**
     * Delete a role. Requires roles.delete. Cannot delete a role still in use.
     */
    public function destroy(Request $request, $id): void
    {
        if (!$actor = Guard::permission($request, 'roles.delete')) {
            return;
        }

        $role = RoleRepository::findById((int) $id);

        if (!$role) {
            Response::json(['message' => 'Rol no encontrado.'], 404);

            return;
        }

        if (RoleRepository::usersCount((int) $id) > 0) {
            Response::error('No puedes eliminar un rol que tiene usuarios asignados.', 'ROLE_IN_USE', 409);

            return;
        }

        RoleRepository::delete((int) $id);
        AuditLogger::roleLog('DELETE', $actor, $role->getName(), []);

        Response::noContent();
    }

    /**
     * Assign a role to a user (sets user_type_id). Requires users.update.
     * Self-protection: a user cannot change their own role.
     */
    public function assignToUser(Request $request, $userId): void
    {
        if (!$actor = Guard::permission($request, 'users.update')) {
            return;
        }

        $user = UserRepository::findById((int) $userId);

        if (!$user) {
            Response::json(['message' => 'Usuario no encontrado.'], 404);

            return;
        }

        if ($actor->getId() === $user->getId()) {
            Response::error('No puedes cambiar tu propio rol.', 'SELF_ACTION_FORBIDDEN', 403);

            return;
        }

        $data = $request->all();
        $errors = Validator::make($data, [
            'user_type_id' => ['required', 'integer', 'exists:users_types,id'],
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);

            return;
        }

        $oldRoles = UserRepository::roleNames((int) $userId);
        // Single-role assignment = a one-element role set (keeps the pivot in sync).
        $updated = UserRepository::syncRoles((int) $userId, [(int) $data['user_type_id']]);
        $updated->setRoleNames(UserRepository::roleNames((int) $userId));

        AuditLogger::userLog('UPDATE', $actor, $updated, [
            'roles' => ['old' => $oldRoles, 'new' => $updated->getRoleNames()],
        ]);

        Response::json(UserResource::toArray($updated));
    }

    /**
     * Replace a user's whole role set (multi-role). Requires users.update.
     * Body: { roles: [user_type_id, ...] }; the first id becomes the primary
     * role (users.user_type_id). Self-protection applies.
     */
    public function syncUserRoles(Request $request, $userId): void
    {
        if (!$actor = Guard::permission($request, 'users.update')) {
            return;
        }

        $user = UserRepository::findById((int) $userId);

        if (!$user) {
            Response::json(['message' => 'Usuario no encontrado.'], 404);

            return;
        }

        if ($actor->getId() === $user->getId()) {
            Response::error('No puedes cambiar tus propios roles.', 'SELF_ACTION_FORBIDDEN', 403);

            return;
        }

        $roles = $request->input('roles');

        if (!is_array($roles) || $roles === []) {
            Response::validationError(['roles' => ['Debes enviar al menos un rol.']]);

            return;
        }

        // Validate every id exists as a role (users_types).
        $roleIds = array_map('intval', $roles);
        foreach ($roleIds as $rid) {
            if (!RoleRepository::findById($rid)) {
                Response::validationError(['roles' => ["El rol $rid no es válido."]]);

                return;
            }
        }

        $oldRoles = UserRepository::roleNames((int) $userId);
        $updated = UserRepository::syncRoles((int) $userId, $roleIds);
        $updated->setRoleNames(UserRepository::roleNames((int) $userId));

        AuditLogger::userLog('UPDATE', $actor, $updated, [
            'roles' => ['old' => $oldRoles, 'new' => $updated->getRoleNames()],
        ]);

        Response::json(UserResource::toArray($updated));
    }

    /**
     * Keeps only names that exist in the permission catalog (defensive).
     *
     * @param  mixed  $permissions
     * @return array<int, string>
     */
    private static function validPermissions($permissions): array
    {
        if (!is_array($permissions)) {
            return [];
        }

        $catalog = PermissionRepository::allNames();

        return array_values(array_filter(
            $permissions,
            fn ($name) => is_string($name) && in_array($name, $catalog, true)
        ));
    }
}
