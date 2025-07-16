<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\Service;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'service_id' => Service::factory(),
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'status' => $this->faker->randomElement(['investigating', 'identified', 'monitoring', 'resolved']),
            'severity' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'resolved_at' => $this->faker->optional()->dateTime,
        ];
    }

    public function withService($service)
    {
        return $this->state(fn () => [
            'service_id' => $service->id,
            'organization_id' => $service->organization_id,
        ]);
    }

    public function configure()
    {
        return $this->afterCreating(function (Incident $incident) {
            $incident->updates()->create([
                'message' => 'Initial incident created.',
                'status' => $incident->status,
            ]);
        });
    }
} 