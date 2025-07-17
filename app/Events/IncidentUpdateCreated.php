<?php

namespace App\Events;

use App\Models\IncidentUpdate;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentUpdateCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $update;
    public $organizationId;
    public $organizationSlug;

    public function __construct(IncidentUpdate $update)
    {
        $this->update = $update;
        $this->organizationId = $update->incident->organization_id;
        $this->organizationSlug = $update->incident->organization->slug;
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
            'update' => $this->update->toArray(),
            'incident' => $this->update->incident->toArray(),
        ];
    }
} 