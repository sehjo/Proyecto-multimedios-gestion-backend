<?php

namespace App\Http\Responses;

/**
 * Example responses for the role/permission management endpoints. The example
 * payloads mirror exactly what RoleController returns (RoleResource shape,
 * fine-grained permissions, etc.).
 */
class RoleResponseConst
{
    /** A single role as serialized by RoleResource. */
    private const ROLE_EXAMPLE = [
        'id'          => 3,
        'name'        => 'Enfermero',
        'guard_name'  => 'web',
        'permissions' => ['patients.read', 'diagnoses.read', 'diagnoses.create', 'diseases.read'],
        'created_at'  => '2026-06-12T15:00:00.000000Z',
        'updated_at'  => '2026-06-12T15:00:00.000000Z',
    ];

    public const ROLE_LIST = [
        'status'      => 200,
        'description' => 'List of roles with their permissions.',
        'examples'    => [
            [
                'id'          => 1,
                'name'        => 'Administrador',
                'guard_name'  => 'web',
                'permissions' => ['users.read', 'users.create', 'roles.read', 'patients.read'],
                'created_at'  => '2026-06-12T15:00:00.000000Z',
                'updated_at'  => '2026-06-12T15:00:00.000000Z',
            ],
            self::ROLE_EXAMPLE,
        ],
    ];

    public const ROLE_SHOW = [
        'status'      => 200,
        'description' => 'A single role with its permissions.',
        'examples'    => self::ROLE_EXAMPLE,
    ];

    public const ROLE_CREATED = [
        'status'      => 201,
        'description' => 'Role created. Permissions are returned with the cascade applied.',
        'examples'    => [
            'id'          => 5,
            'name'        => 'Recepcion',
            'guard_name'  => 'web',
            // create cascades to read automatically
            'permissions' => ['patients.read', 'patients.create'],
            'created_at'  => '2026-06-12T15:00:00.000000Z',
            'updated_at'  => '2026-06-12T15:00:00.000000Z',
        ],
    ];

    public const ROLE_UPDATED = [
        'status'      => 200,
        'description' => 'Role updated (renamed and/or permissions re-synced).',
        'examples'    => self::ROLE_EXAMPLE,
    ];

    public const ROLE_DELETED_NO_CONTENT = [
        'status'      => 204,
        'description' => 'Role deleted. No content is returned.',
        'examples'    => null,
    ];

    public const PROTECTED_ROLE = [
        'status'      => 422,
        'description' => 'The targeted role is a protected system role and cannot be renamed/deleted.',
        'examples'    => [
            'success'    => false,
            'message'    => 'Este rol del sistema no se puede eliminar.',
            'error_code' => 'PROTECTED_ROLE',
        ],
    ];

    public const PERMISSIONS_LIST = [
        'status'      => 200,
        'description' => 'All available permissions (to build the role management UI).',
        'examples'    => [
            'success' => true,
            'data'    => [
                'patients.read', 'patients.create', 'patients.update', 'patients.delete',
                'roles.read', 'users.read',
            ],
        ],
    ];

    public const ROLE_ASSIGNED = [
        'status'      => 200,
        'description' => 'Role added to the user (a user can hold several roles).',
        'examples'    => [
            'success' => true,
            'message' => 'Rol asignado correctamente.',
            'data'    => [
                'user_id' => 4,
                'roles'   => ['Medico', 'Enfermero'],
            ],
        ],
    ];

    public const ROLE_REVOKED = [
        'status'      => 200,
        'description' => 'Role removed from the user.',
        'examples'    => [
            'success' => true,
            'message' => 'Rol revocado correctamente.',
            'data'    => [
                'user_id' => 4,
                'roles'   => ['Medico'],
            ],
        ],
    ];

    public const LAST_ROLE = [
        'status'      => 409,
        'description' => 'The user must keep at least one role.',
        'examples'    => [
            'success'    => false,
            'message'    => 'El usuario debe conservar al menos un rol.',
            'error_code' => 'LAST_ROLE',
        ],
    ];

    public const ROLES_SYNCED = [
        'status'      => 200,
        'description' => "The user's complete role set was replaced atomically.",
        'examples'    => [
            'success' => true,
            'message' => 'Roles actualizados correctamente.',
            'data'    => [
                'user_id' => 4,
                'roles'   => ['Administrador'],
            ],
        ],
    ];
}
