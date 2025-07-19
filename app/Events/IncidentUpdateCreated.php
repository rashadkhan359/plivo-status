<?php

namespace App\Events;

use App\Models\IncidentUpdate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentUpdateCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $incidentUpdate;
    public $organizationId;
    public $organizationSlug;

    public function __construct(IncidentUpdate $incidentUpdate)
    {
        $this->incidentUpdate = $incidentUpdate;
        $this->organizationId = $incidentUpdate->incident->organization_id;
        $this->organizationSlug = $incidentUpdate->incident->organization->slug;
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
        return 'IncidentUpdateCreated';
    }

    public function broadcastWith()
    {
        return [
            'incident_update' => $this->incidentUpdate->load(['incident', 'creator'])->toArray(),
        ];
    }
} 