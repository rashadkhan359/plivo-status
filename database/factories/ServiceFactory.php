<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->word . ' Service',
            'description' => $this->faker->sentence,
            'status' => $this->faker->randomElement(['operational', 'degraded', 'partial_outage', 'major_outage']),
        ];
    }

    public function withOrganization($organization)
    {
        return $this->state(fn () => [
            'organization_id' => $organization->id,
        ]);
    }
} 