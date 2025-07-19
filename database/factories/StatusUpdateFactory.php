<?php

namespace Database\Factories;

use App\Models\StatusUpdate;
use App\Models\Organization;
use App\Models\Service;
use App\Models\Incident;
use App\Models\Maintenance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatusUpdateFactory extends Factory
{
    protected $model = StatusUpdate::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['service_status', 'incident', 'maintenance']);
        
        $data = [
            'organization_id' => Organization::factory(),
            'type' => $type,
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'created_by' => User::factory(),
        ];

        // Add specific relationships based on type
        switch ($type) {
            case 'service_status':
                $data['service_id'] = Service::factory();
                $data['old_status'] = $this->faker->randomElement(['operational', 'degraded']);
                $data['new_status'] = $this->faker->randomElement(['degraded', 'partial_outage', 'major_outage']);
                break;
            case 'incident':
                $data['incident_id'] = Incident::factory();
                $data['old_status'] = 'investigating';
                $data['new_status'] = $this->faker->randomElement(['identified', 'monitoring', 'resolved']);
                break;
            case 'maintenance':
                $data['maintenance_id'] = Maintenance::factory();
                $data['old_status'] = 'scheduled';
                $data['new_status'] = $this->faker->randomElement(['in_progress', 'completed']);
                break;
        }

        return $data;
    }

    /**
     * Create service status update
     */
    public function serviceStatus()
    {
        return $this->state(fn () => [
            'type' => 'service_status',
            'service_id' => Service::factory(),
            'incident_id' => null,
            'maintenance_id' => null,
        ]);
    }

    /**
     * Create incident update
     */
    public function incident()
    {
        return $this->state(fn () => [
            'type' => 'incident',
            'incident_id' => Incident::factory(),
            'service_id' => null,
            'maintenance_id' => null,
        ]);
    }

    /**
     * Create maintenance update
     */
    public function maintenance()
    {
        return $this->state(fn () => [
            'type' => 'maintenance',
            'maintenance_id' => Maintenance::factory(),
            'service_id' => null,
            'incident_id' => null,
        ]);
    }
} 