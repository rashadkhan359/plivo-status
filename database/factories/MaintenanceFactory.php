<?php

namespace Database\Factories;

use App\Models\Maintenance;
use App\Models\Organization;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaintenanceFactory extends Factory
{
    protected $model = Maintenance::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 week', '+1 week');
        $end = (clone $start)->modify('+2 hours');
        return [
            'organization_id' => Organization::factory(),
            'service_id' => Service::factory(),
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'scheduled_start' => $start,
            'scheduled_end' => $end,
            'status' => $this->faker->randomElement(['scheduled', 'in_progress', 'completed']),
        ];
    }
} 