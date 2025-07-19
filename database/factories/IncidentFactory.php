<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\Service;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    public function definition(): array
    {
        $isResolved = $this->faker->boolean(30); // 30% chance of being resolved
        $resolvedAt = $isResolved ? $this->faker->dateTimeBetween('-1 week', 'now') : null;
        
        return [
            'organization_id' => Organization::factory(),
            'service_id' => Service::factory(), // Legacy single service relationship
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'status' => $resolvedAt ? 'resolved' : $this->faker->randomElement(['investigating', 'identified', 'monitoring']),
            'severity' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'created_by' => User::factory(),
            'resolved_by' => $resolvedAt ? User::factory() : null,
            'resolved_at' => $resolvedAt,
        ];
    }

    /**
     * Create incident for specific service
     */
    public function forService($service)
    {
        return $this->state(fn () => [
            'service_id' => $service->id,
            'organization_id' => $service->organization_id,
        ]);
    }

    /**
     * Create resolved incident
     */
    public function resolved()
    {
        return $this->state(fn () => [
            'status' => 'resolved',
            'resolved_by' => User::factory(),
            'resolved_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Create critical incident
     */
    public function critical()
    {
        return $this->state(fn () => [
            'severity' => 'critical',
        ]);
    }

    public function withService($service)
    {
        return $this->state(fn () => [
            'service_id' => $service->id,
            'organization_id' => $service->organization_id,
        ]);
    }

    /**
     * Configure the model factory
     */
    public function configure()
    {
        return $this->afterCreating(function (Incident $incident) {
            // Create initial incident update
            $incident->updates()->create([
                'title' => 'Incident Created',
                'description' => 'Initial incident report.',
                'status' => $incident->status,
                'created_by' => $incident->created_by,
            ]);
        });
    }
} 