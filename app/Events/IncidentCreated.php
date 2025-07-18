<?php

namespace App\Events;

use App\Models\Incident;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentCreated implements ShouldBroadcast
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
            new Channel('status.' . $this->organizationSlug),
            new PrivateChannel('organization.' . $this->organizationId),
        ];
    }

    public function broadcastAs()
    {
        return 'IncidentCreated';
    }

    public function broadcastWith()
    {
        return [
            'incident' => $this->incident->load(['services', 'creator', 'resolver'])->toArray(),
        ];
    }
} 