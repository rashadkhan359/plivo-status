<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'created_at' => $this->created_at->toFormattedDateString(),
            'updated_at' => $this->updated_at->toFormattedDateString(),
            'users_count' => $this->whenCounted('users'),
            'services_count' => $this->whenCounted('services'),
            'incidents_count' => $this->whenCounted('incidents'),
            'maintenances_count' => $this->whenCounted('maintenances'),
            'users' => UserResource::collection($this->whenLoaded('users')),
            'services' => ServiceResource::collection($this->whenLoaded('services')),
        ];
    }
}
