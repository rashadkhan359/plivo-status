<?php

namespace Database\Factories;

use App\Models\IncidentUpdate;
use App\Models\Incident;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentUpdateFactory extends Factory
{
    protected $model = IncidentUpdate::class;

    public function definition(): array
    {
        return [
            'incident_id' => Incident::factory(),
            'message' => $this->faker->sentence,
            'status' => $this->faker->randomElement(['investigating', 'identified', 'monitoring', 'resolved']),
        ];
    }
} 