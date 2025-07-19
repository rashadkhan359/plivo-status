<?php

namespace Database\Factories;

use App\Models\IncidentUpdate;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentUpdateFactory extends Factory
{
    protected $model = IncidentUpdate::class;

    public function definition(): array
    {
        return [
            'incident_id' => Incident::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'status' => $this->faker->randomElement(['investigating', 'identified', 'monitoring', 'resolved']),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Create update for specific incident
     */
    public function forIncident($incident)
    {
        return $this->state(fn () => [
            'incident_id' => $incident->id,
        ]);
    }
} 