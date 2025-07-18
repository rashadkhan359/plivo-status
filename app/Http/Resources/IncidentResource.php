<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
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
            'status' => $this->status,
            'severity' => $this->severity,
            'resolved_at' => $this->resolved_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'service' => $this->whenLoaded('service', function () {
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                ];
            }),
            'services' => $this->whenLoaded('services', function () {
                return $this->services->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'status' => $service->status,
                        'team' => $service->team ? [
                            'id' => $service->team->id,
                            'name' => $service->team->name,
                            'color' => $service->team->color,
                        ] : null,
                    ];
                });
            }),
        ];
    }
} 