<?php

namespace App\Enums;

/**
 * User account status. INACTIVE users cannot log in.
 */
enum UserStatus: string
{
    case Active   = 'ACTIVE';
    case Inactive = 'INACTIVE';
}
