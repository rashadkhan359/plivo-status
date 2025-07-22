<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SystemAdminService
{
    /**
     * Check if a system admin exists.
     */
    public function exists(): bool
    {
        return User::where('is_system_admin', true)->exists();
    }

    /**
     * Get the system admin user.
     */
    public function get(): ?User
    {
        return User::where('is_system_admin', true)->first();
    }

    /**
     * Create a system admin user.
     */
    public function create(string $name, string $email, string $password): User
    {
        // Check if system admin already exists
        if ($this->exists()) {
            throw new \Exception('A system admin already exists.');
        }

        // Check if user with this email exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            // Convert existing user to system admin
            $existingUser->update([
                'is_system_admin' => true,
            ]);
            
            Log::info("Existing user converted to system admin: {$email}");
            return $existingUser;
        }

        // Find or create demo organization for system admin
        $demoOrg = \App\Models\Organization::where('name', 'Demo Organization')->first();
        if (!$demoOrg) {
            // Create demo organization if it doesn't exist
            $demoOrg = \App\Models\Organization::create([
                'name' => 'Demo Organization',
                'slug' => 'demo-organization',
                'domain' => null,
                'settings' => [
                    'allow_registrations' => true,
                    'default_role' => 'member'
                ],
                'timezone' => 'UTC',
            ]);
            Log::info("Created demo organization for system admin");
        }

        // Create new system admin
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_system_admin' => true,
            'organization_id' => $demoOrg->id, // Connect to demo organization
            'email_verified_at' => now(),
        ]);

        Log::info("System admin created: {$email} connected to demo organization");
        return $user;
    }

    /**
     * Ensure a system admin exists, create one if needed.
     */
    public function ensureExists(): ?User
    {
        if ($this->exists()) {
            return $this->get();
        }

        $name = config('admin.system_admin.name');
        $email = config('admin.system_admin.email');
        $password = config('admin.system_admin.password');

        try {
            return $this->create($name, $email, $password);
        } catch (\Exception $e) {
            Log::error("Failed to create system admin: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Remove system admin status from a user.
     */
    public function removeSystemAdmin(User $user): bool
    {
        if (!$user->is_system_admin) {
            return false;
        }

        $user->update(['is_system_admin' => false]);
        Log::info("System admin status removed from user: {$user->email}");
        
        return true;
    }

    /**
     * Grant system admin status to a user.
     */
    public function grantSystemAdmin(User $user): bool
    {
        if ($user->is_system_admin) {
            return false;
        }

        $user->update(['is_system_admin' => true]);
        Log::info("System admin status granted to user: {$user->email}");
        
        return true;
    }

    /**
     * Get all system admins.
     */
    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return User::where('is_system_admin', true)->get();
    }

    /**
     * Count system admins.
     */
    public function count(): int
    {
        return User::where('is_system_admin', true)->count();
    }
} 