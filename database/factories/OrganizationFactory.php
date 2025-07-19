<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company;
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->randomNumber(3),
            'domain' => $this->faker->boolean(0.7) ? $this->faker->unique()->domainName : null, // 70% chance of having a domain
            'logo' => null, // Will be set manually or via storage
            'settings' => [
                'allow_registrations' => $this->faker->boolean,
                'default_role' => $this->faker->randomElement(['member', 'team_lead']),
                'notification_email' => $this->faker->safeEmail,
            ],
            'timezone' => $this->faker->timezone,
            'created_by' => null, // Will be set after user creation
        ];
    }

    /**
     * Create organization with a creator
     */
    public function withCreator($user)
    {
        return $this->state(fn () => [
            'created_by' => $user->id,
        ]);
    }
} 