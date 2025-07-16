<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
            'organization_id' => Organization::factory(),
            'role' => 'admin',
        ];
    }

    public function withOrganization($organization)
    {
        return $this->state(fn () => [
            'organization_id' => $organization->id,
        ]);
    }

    public function member()
    {
        return $this->state(fn () => [
            'role' => 'member',
        ]);
    }
}
