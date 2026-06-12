<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\AssignRoleRequest;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Gestión de roles y permisos (solo Administrador).
 *
 * Se trabaja únicamente con permisos asignados A ROLES; nunca permisos
 * directos a usuarios.
 */
class RoleController extends Controller
{
    /**
     * Roles base del sistema. No se pueden renombrar ni eliminar, pero sí
     * se les pueden ajustar los permisos.
     *
     * @var array<int, string>
     */
    private const PROTECTED_ROLES = ['Administrador', 'Medico', 'Enfermero', 'Paciente'];

    /**
     * Listar todos los roles con sus permisos.
     */
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->orderBy('name')->get();

        return response()->json(RoleResource::collection($roles));
    }

    /**
     * Mostrar un rol y sus permisos.
     */
    public function show(Role $role): JsonResponse
    {
        return response()->json(new RoleResource($role->load('permissions')));
    }

    /**
     * Crear un rol nuevo y, opcionalmente, asignarle permisos.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $role = Role::create([
            'name'       => $data['name'],
            'guard_name' => 'web',
        ]);

        if (! empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return response()->json(new RoleResource($role->load('permissions')), 201);
    }

    /**
     * Actualizar un rol: renombrarlo y/o re-sincronizar sus permisos.
     * Los roles base no se pueden renombrar (sí ajustar permisos).
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
            $role->syncPermissions($data['permissions']);
        }

        return response()->json(new RoleResource($role->load('permissions')));
    }

    /**
     * Eliminar un rol. Los roles base del sistema no se pueden eliminar.
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
     * Listar todos los permisos disponibles (para construir la UI de roles).
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
     * Asignar (reemplazar) el rol de un usuario. Solo roles, nunca permisos directos.
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
