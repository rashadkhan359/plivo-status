<?php

namespace App\Events;

use App\Models\Service;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $service;
    public $organizationId;
    public $organizationSlug;

    public function __construct(Service $service)
    {
        $this->service = $service;
        $this->organizationId = $service->organization_id;
        $this->organizationSlug = $service->organization->slug;
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
            'service' => $this->service->toArray(),
        ];
    }
} 