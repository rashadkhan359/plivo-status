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
        // Check if this is a public request (no authenticated user)
        $isPublic = !$request->user();
        
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'severity' => $this->severity,
            'resolved_at' => $this->resolved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'service' => $this->whenLoaded('service', function () {
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                    'status' => $this->service->status,
                ];
            }),
            'services' => $this->whenLoaded('services', function () use ($isPublic) {
                return $this->services->map(function ($service) use ($isPublic) {
                    $serviceData = [
                        'id' => $service->id,
                        'name' => $service->name,
                        'status' => $service->status,
                    ];
                    
                    // Only include team info for authenticated users
                    if (!$isPublic && $service->team) {
                        $serviceData['team'] = [
                            'id' => $service->team->id,
                            'name' => $service->team->name,
                            'color' => $service->team->color,
                        ];
                    }
                    
                    return $serviceData;
                });
            }),
        ];
        
        // Only include sensitive data for authenticated users
        if (!$isPublic) {
            $data = array_merge($data, [
                'organization_id' => $this->organization_id,
                'service_id' => $this->service_id,
            ]);
        }
        
        return $data;
    }
} 