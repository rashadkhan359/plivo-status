<?php

namespace App\Enums;

enum IncidentStatus: string
{
    case INVESTIGATING = 'investigating';
    case IDENTIFIED = 'identified';
    case MONITORING = 'monitoring';
    case RESOLVED = 'resolved';
}
