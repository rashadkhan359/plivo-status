<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        $teamTypes = ['Engineering', 'DevOps', 'Frontend', 'Backend', 'QA', 'Security', 'Design', 'Mobile', 'Infrastructure', 'Analytics'];
        $teamType = $this->faker->randomElement($teamTypes);
        $teamName = $teamType . ' ' . $this->faker->randomElement(['Team', 'Squad', 'Unit']) . ' ' . $this->faker->randomLetter . $this->faker->randomNumber(2);
        
        return [
            'organization_id' => Organization::factory(),
            'name' => $teamName,
            'description' => $this->faker->paragraph,
            'color' => $this->faker->randomElement([
                '#3B82F6', // Blue
                '#EF4444', // Red  
                '#10B981', // Green
                '#F59E0B', // Yellow
                '#8B5CF6', // Purple
                '#F97316', // Orange
                '#06B6D4', // Cyan
                '#84CC16', // Lime
                '#F472B6', // Pink
                '#14B8A6', // Teal
            ]),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Create team for specific organization
     */
    public function forOrganization($organization)
    {
        return $this->state(fn () => [
            'organization_id' => $organization->id,
        ]);
    }
} 