<?php

namespace App\Enums;

/**
 * Days of the week used for the institution's weekly schedule.
 */
enum DayOfWeek: string
{
    case Monday    = 'MONDAY';
    case Tuesday   = 'TUESDAY';
    case Wednesday = 'WEDNESDAY';
    case Thursday  = 'THURSDAY';
    case Friday    = 'FRIDAY';
    case Saturday  = 'SATURDAY';
    case Sunday    = 'SUNDAY';
}
