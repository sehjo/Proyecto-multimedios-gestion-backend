<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\AssignRoleRequest;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Role and permission management (Administrador only).
 *
 * Permissions are only ever assigned TO ROLES; never directly to users.
 */
class RoleController extends Controller
{
    /**
     * System base roles. They cannot be renamed or deleted, but their
     * permissions can still be adjusted.
     *
     * @var array<int, string>
     */
    private const PROTECTED_ROLES = ['Administrador', 'Medico', 'Enfermero', 'Paciente'];

    /**
     * List roles.
     *
     * Returns all roles with their permissions. Requires the Administrador role.
     */
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->orderBy('name')->get();

        return response()->json(RoleResource::collection($roles));
    }

    /**
     * Show role.
     *
     * Returns a role and its permissions. Requires the Administrador role.
     */
    public function show(Role $role): JsonResponse
    {
        return response()->json(new RoleResource($role->load('permissions')));
    }

    /**
     * Create role.
     *
     * Creates a new role and, optionally, assigns it permissions.
     * Requires the Administrador role.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $role = Role::create([
            'name'       => $data['name'],
            'guard_name' => PermissionCatalog::GUARD,
        ]);

        if (! empty($data['permissions'])) {
            // Apply the cascade: create/update/delete imply read.
            $role->syncPermissions(PermissionCatalog::expand($data['permissions']));
        }

        return response()->json(new RoleResource($role->load('permissions')), 201);
    }

    /**
     * Update role.
     *
     * Renames the role and/or re-syncs its permissions. Base roles cannot be
     * renamed (their permissions can still be adjusted).
     * Requires the Administrador role.
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('name', $data) && $data['name'] !== $role->name) {
            if ($this->isProtected($role)) {
                return $this->protectedRoleError('Este rol del sistema no se puede renombrar.');
            }

            $role->name = $data['name'];
            $role->save();
        }

        if (array_key_exists('permissions', $data)) {
            // Apply the cascade: create/update/delete imply read.
            $role->syncPermissions(PermissionCatalog::expand($data['permissions']));
        }

        return response()->json(new RoleResource($role->load('permissions')));
    }

    /**
     * Delete role.
     *
     * Deletes a role. System base roles cannot be deleted.
     * Requires the Administrador role.
     */
    public function destroy(Role $role): Response|JsonResponse
    {
        if ($this->isProtected($role)) {
            return $this->protectedRoleError('Este rol del sistema no se puede eliminar.');
        }

        $role->delete();

        return response()->noContent();
    }

    /**
     * List permissions.
     *
     * Returns all available permissions (to build the role management UI).
     * Requires the Administrador role.
     */
    public function permissions(): JsonResponse
    {
        $permissions = Permission::orderBy('name')->pluck('name');

        return response()->json([
            'success' => true,
            'data'    => $permissions,
        ]);
    }

    /**
     * Assign a role to a user.
     *
     * Replaces the user's role. Requires the `users.update` permission.
     */
    public function assignToUser(AssignRoleRequest $request, User $user): JsonResponse
    {
        $user->syncRoles([$request->validated()['role']]);

        return response()->json([
            'success' => true,
            'message' => 'Rol asignado correctamente.',
            'data'    => [
                'user_id' => $user->id,
                'roles'   => $user->getRoleNames(),
            ],
        ]);
    }

    private function isProtected(Role $role): bool
    {
        return in_array($role->name, self::PROTECTED_ROLES, true);
    }

    private function protectedRoleError(string $message): JsonResponse
    {
        return response()->json([
            'success'    => false,
            'message'    => $message,
            'error_code' => 'PROTECTED_ROLE',
        ], 422);
    }
}
