<?php

namespace Database\Factories;

use App\Models\Maintenance;
use App\Models\Organization;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaintenanceFactory extends Factory
{
    protected $model = Maintenance::class;

    public function definition(): array
    {
        $scheduledStart = $this->faker->dateTimeBetween('-1 week', '+2 weeks');
        $scheduledEnd = (clone $scheduledStart)->modify('+' . $this->faker->numberBetween(1, 6) . ' hours');
        
        $status = $this->faker->randomElement(['scheduled', 'in_progress', 'completed']);
        
        // Set actual times based on status
        $actualStart = null;
        $actualEnd = null;
        
        if ($status === 'in_progress') {
            // For in-progress, actual start should be before now but after scheduled start (if in the past)
            if ($scheduledStart < now()) {
                $actualStart = $this->faker->dateTimeBetween($scheduledStart, 'now');
            } else {
                // If scheduled for future, just set to null
                $actualStart = null;
                $status = 'scheduled'; // Change back to scheduled
            }
        } elseif ($status === 'completed') {
            // For completed, both actual start and end should be in the past
            $actualStart = $this->faker->dateTimeBetween('-2 weeks', '-1 day');
            $actualEnd = $this->faker->dateTimeBetween($actualStart, 'now');
        }

        return [
            'organization_id' => Organization::factory(),
            'service_id' => $this->faker->boolean(80) ? Service::factory() : null, // 80% chance of being service-specific
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'scheduled_start' => $scheduledStart,
            'scheduled_end' => $scheduledEnd,
            'actual_start' => $actualStart,
            'actual_end' => $actualEnd,
            'status' => $status,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Create maintenance for specific service
     */
    public function forService($service)
    {
        return $this->state(fn () => [
            'service_id' => $service->id,
            'organization_id' => $service->organization_id,
        ]);
    }

    /**
     * Create organization-wide maintenance
     */
    public function organizationWide()
    {
        return $this->state(fn () => [
            'service_id' => null,
        ]);
    }

    /**
     * Create scheduled maintenance
     */
    public function scheduled()
    {
        return $this->state(fn () => [
            'status' => 'scheduled',
            'actual_start' => null,
            'actual_end' => null,
        ]);
    }

    /**
     * Create completed maintenance
     */
    public function completed()
    {
        return $this->state(function () {
            $actualStart = $this->faker->dateTimeBetween('-2 weeks', '-1 hour');
            $actualEnd = $this->faker->dateTimeBetween($actualStart, 'now');
            
            return [
                'status' => 'completed',
                'actual_start' => $actualStart,
                'actual_end' => $actualEnd,
            ];
        });
    }
} 