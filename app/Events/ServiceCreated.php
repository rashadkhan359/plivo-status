<?php

namespace App\Events;

use App\Models\Service;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceCreated implements ShouldBroadcast
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
            new Channel('status.' . $this->organizationSlug),
            new PrivateChannel('organization.' . $this->organizationId),
        ];
    }

    public function broadcastAs()
    {
        return 'ServiceCreated';
    }

    public function broadcastWith()
    {
        return [
            'service' => $this->service->load(['team'])->toArray(),
        ];
    }
} 