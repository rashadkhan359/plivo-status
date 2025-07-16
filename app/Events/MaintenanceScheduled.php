<?php

namespace App\Events;

use App\Models\Maintenance;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaintenanceScheduled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $maintenance;
    public $organizationId;
    public $organizationSlug;

    public function __construct(Maintenance $maintenance)
    {
        $this->maintenance = $maintenance;
        $this->organizationId = $maintenance->organization_id;
        $this->organizationSlug = $maintenance->organization->slug;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('organization.' . $this->organizationId),
            new Channel('status.' . $this->organizationSlug),
        ];
    }

    public function broadcastWith()
    {
        return [
            'maintenance' => $this->maintenance->toArray(),
        ];
    }
} 