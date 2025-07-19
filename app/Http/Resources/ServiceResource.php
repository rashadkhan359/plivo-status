<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'order' => $this->order,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
        
        // Only include sensitive data for authenticated users
        if (!$isPublic) {
            $data = array_merge($data, [
                'organization_id' => $this->organization_id,
                'team_id' => $this->team_id,
                'team' => $this->whenLoaded('team'),
                'created_by' => $this->created_by,
                'created_at' => $this->created_at,
            ]);
        }
        
        return $data;
    }
} 