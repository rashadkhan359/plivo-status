<?php

namespace App\Enums;

enum MaintenanceStatus: string
{
    case SCHEDULED = 'scheduled';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
}
