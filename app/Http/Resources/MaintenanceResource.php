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
        // Check if this is a public request (no authenticated user)
        $isPublic = !$request->user();
        
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'scheduled_start' => $this->scheduled_start?->toISOString(),
            'scheduled_end' => $this->scheduled_end?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'service' => $this->whenLoaded('service', function () {
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                    'status' => $this->service->status,
                ];
            }),
        ];
        
        // Only include sensitive data for authenticated users
        if (!$isPublic) {
            $data = array_merge($data, [
                'organization_id' => $this->organization_id,
                'service_id' => $this->service_id,
                'organization' => new OrganizationResource($this->whenLoaded('organization')),
            ]);
        }
        
        return $data;
    }
} 