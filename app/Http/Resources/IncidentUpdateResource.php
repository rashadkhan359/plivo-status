<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class IncidentUpdateResource extends JsonResource
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
            'message' => $this->description, // Map description to message for frontend compatibility
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
        
        // Only include sensitive data for authenticated users
        if (!$isPublic) {
            $data = array_merge($data, [
                'incident_id' => $this->incident_id,
            ]);
        }
        
        return $data;
    }
} 