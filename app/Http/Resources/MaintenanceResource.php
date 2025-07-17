<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\OrganizationResource;

class MaintenanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'service_id' => $this->service_id,
            'title' => $this->title,
            'description' => $this->description,
            'scheduled_start' => $this->scheduled_start,
            'scheduled_end' => $this->scheduled_end,
            'status' => $this->status,
            'created_at' => $this->created_at,
                        'updated_at' => $this->updated_at,
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
        ];
    }
} 