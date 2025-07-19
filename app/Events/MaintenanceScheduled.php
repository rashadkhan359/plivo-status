<?php

namespace App\Events;

use App\Models\Maintenance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaintenanceScheduled implements ShouldBroadcast
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
            new Channel('status.' . $this->organizationSlug),
            new PrivateChannel('organization.' . $this->organizationId),
        ];
    }

    public function broadcastAs()
    {
        return 'MaintenanceScheduled';
    }

    public function broadcastWith()
    {
        return [
            'maintenance' => $this->maintenance->load(['service'])->toArray(),
        ];
    }
} 