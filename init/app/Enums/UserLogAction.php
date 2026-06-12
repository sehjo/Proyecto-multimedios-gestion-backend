<?php

namespace App\Enums;

/**
 * Audit actions recorded in the users log (log_users).
 */
enum UserLogAction: string
{
    case Create        = 'CREATE';
    case Update        = 'UPDATE';
    case Delete        = 'DELETE';
    case PasswordReset = 'PASSWORD_RESET';
}
