<?php

namespace App\Events;

use App\Models\Incident;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentResolved implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $incident;
    public $organizationId;
    public $organizationSlug;

    public function __construct(Incident $incident)
    {
        $this->incident = $incident;
        $this->organizationId = $incident->organization_id;
        $this->organizationSlug = $incident->organization->slug;
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
            'incident' => $this->incident->toArray(),
        ];
    }
} 