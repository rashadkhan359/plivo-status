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
            'organization_id' => Organization::factory(), // Legacy field for backward compatibility
            'role' => 'member', // Legacy field for backward compatibility
        ];
    }

    /**
     * Create user with legacy organization relationship
     */
    public function withOrganization($organization)
    {
        return $this->state(fn () => [
            'organization_id' => $organization->id,
        ]);
    }

    /**
     * Create user as organization owner
     */
    public function owner()
    {
        return $this->state(fn () => [
            'role' => 'admin', // Legacy role for backward compatibility
        ]);
    }

    /**
     * Create user as organization admin
     */
    public function admin()
    {
        return $this->state(fn () => [
            'role' => 'admin',
        ]);
    }

    /**
     * Create user as team lead
     */
    public function teamLead()
    {
        return $this->state(fn () => [
            'role' => 'member', // Legacy role doesn't have team_lead
        ]);
    }

    /**
     * Create user as member
     */
    public function member()
    {
        return $this->state(fn () => [
            'role' => 'member',
        ]);
    }

    /**
     * Create user as system admin
     */
    public function systemAdmin()
    {
        return $this->state(fn () => [
            'is_system_admin' => true,
            'role' => 'admin', // Legacy role for backward compatibility
        ]);
    }
}
