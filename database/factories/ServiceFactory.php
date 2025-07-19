<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'team_id' => null, // Can be assigned to a team or organization-wide
            'name' => $this->faker->word . ' Service',
            'description' => $this->faker->sentence,
            'status' => $this->faker->randomElement(['operational', 'degraded', 'partial_outage', 'major_outage']),
            'visibility' => $this->faker->randomElement(['public', 'private']),
            'order' => $this->faker->numberBetween(0, 100),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Create service for specific organization
     */
    public function forOrganization($organization)
    {
        return $this->state(fn () => [
            'organization_id' => $organization->id,
        ]);
    }

    /**
     * Create service for specific team
     */
    public function forTeam($team)
    {
        return $this->state(fn () => [
            'organization_id' => $team->organization_id,
            'team_id' => $team->id,
        ]);
    }

    /**
     * Create public service
     */
    public function public()
    {
        return $this->state(fn () => [
            'visibility' => 'public',
        ]);
    }

    /**
     * Create private service
     */
    public function private()
    {
        return $this->state(fn () => [
            'visibility' => 'private',
        ]);
    }

    public function withOrganization($organization)
    {
        return $this->state(fn () => [
            'organization_id' => $organization->id,
        ]);
    }
} 