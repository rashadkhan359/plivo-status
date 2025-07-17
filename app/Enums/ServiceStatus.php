<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case OPERATIONAL = 'operational';
    case DEGRADED = 'degraded';
    case PARTIAL_OUTAGE = 'partial_outage';
    case MAJOR_OUTAGE = 'major_outage';
}
