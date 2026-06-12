<?php

namespace App\Http\Responses;

/**
 * Example responses for the audit-log endpoints. The example payloads mirror
 * what LogUserResource / LogRoleResource return.
 */
class LogResponseConst
{
    public const USER_LOGS = [
        'status'      => 200,
        'description' => 'Paginated users audit log (most recent first).',
        'examples'    => [
            'data' => [
                [
                    'id'           => 12,
                    'action'       => 'UPDATE',
                    'performed_by' => ['id' => 1, 'name' => 'Carlos'],
                    'target_user'  => ['id' => 4, 'name' => 'María'],
                    'changes'      => [
                        'performed_by' => ['id' => 1, 'name' => 'Carlos'],
                        'target'       => ['id' => 4, 'name' => 'María'],
                        'fields'       => [
                            'email' => ['old' => 'maria@ccss.cr', 'new' => 'maria.g@ccss.cr'],
                        ],
                    ],
                    'timestamp'    => '2026-06-13T10:30:00.000000Z',
                ],
            ],
            'links' => ['first' => '...', 'last' => '...', 'prev' => null, 'next' => null],
            'meta'  => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 1],
        ],
    ];

    public const ROLE_LOGS = [
        'status'      => 200,
        'description' => 'Paginated roles audit log (most recent first).',
        'examples'    => [
            'data' => [
                [
                    'id'             => 7,
                    'action'         => 'ASSIGN',
                    'performed_by'   => ['id' => 1, 'name' => 'Carlos'],
                    'target_role_id' => 3,
                    'changes'        => [
                        'performed_by' => ['id' => 1, 'name' => 'Carlos'],
                        'target'       => ['id' => 3, 'name' => 'Enfermero'],
                        'fields'       => [
                            'target_user' => ['id' => 4, 'name' => 'María'],
                            'roles'       => ['old' => ['Paciente'], 'new' => ['Enfermero']],
                        ],
                    ],
                    'timestamp'      => '2026-06-13T10:35:00.000000Z',
                ],
            ],
            'links' => ['first' => '...', 'last' => '...', 'prev' => null, 'next' => null],
            'meta'  => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 1],
        ],
    ];
}
