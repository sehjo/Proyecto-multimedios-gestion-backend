<?php

namespace App\Enums;

/**
 * Audit actions recorded in the roles log (log_roles).
 */
enum RoleLogAction: string
{
    case Create = 'CREATE';
    case Update = 'UPDATE';
    case Delete = 'DELETE';
    case Assign = 'ASSIGN';
}
