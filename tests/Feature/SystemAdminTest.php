<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SystemAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_service_can_create_admin()
    {
        $service = app(SystemAdminService::class);

        $admin = $service->create('Test Admin', 'admin@test.com', 'password123');

        $this->assertTrue($admin->is_system_admin);
        $this->assertEquals('Test Admin', $admin->name);
        $this->assertEquals('admin@test.com', $admin->email);
    }

    public function test_system_admin_service_cannot_create_duplicate_admin()
    {
        $service = app(SystemAdminService::class);

        // Create first admin
        $service->create('Test Admin', 'admin@test.com', 'password123');

        // Try to create second admin
        $this->expectException(\Exception::class);
        $service->create('Another Admin', 'admin2@test.com', 'password123');
    }

    public function test_system_admin_service_can_convert_existing_user()
    {
        $service = app(SystemAdminService::class);

        // Create regular user
        $user = User::factory()->create([
            'email' => 'user@test.com',
            'is_system_admin' => false,
        ]);

        // Convert to system admin
        $admin = $service->create('Test Admin', 'user@test.com', 'password123');

        $this->assertTrue($admin->is_system_admin);
        $this->assertEquals($user->id, $admin->id);
    }

    public function test_system_admin_service_ensure_exists_creates_admin_when_none_exists()
    {
        $service = app(SystemAdminService::class);

        $admin = $service->ensureExists();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->is_system_admin);
        $this->assertEquals(config('admin.system_admin.email'), $admin->email);
    }

    public function test_system_admin_service_ensure_exists_returns_existing_admin()
    {
        $service = app(SystemAdminService::class);

        // Create admin
        $firstAdmin = $service->create('Test Admin', 'admin@test.com', 'password123');

        // Ensure exists should return the same admin
        $secondAdmin = $service->ensureExists();

        $this->assertEquals($firstAdmin->id, $secondAdmin->id);
    }

    public function test_system_admin_service_can_grant_and_remove_admin_status()
    {
        $service = app(SystemAdminService::class);

        // Create regular user
        $user = User::factory()->create(['is_system_admin' => false]);

        // Grant admin status
        $result = $service->grantSystemAdmin($user);
        $this->assertTrue($result);
        $this->assertTrue($user->fresh()->is_system_admin);

        // Remove admin status
        $result = $service->removeSystemAdmin($user);
        $this->assertTrue($result);
        $this->assertFalse($user->fresh()->is_system_admin);
    }

    public function test_system_admin_service_can_count_admins()
    {
        $service = app(SystemAdminService::class);

        $this->assertEquals(0, $service->count());

        // Create admin
        $service->create('Test Admin', 'admin@test.com', 'password123');

        $this->assertEquals(1, $service->count());
    }

    public function test_system_admin_service_can_get_all_admins()
    {
        $service = app(SystemAdminService::class);

        // Create regular users
        User::factory()->count(3)->create(['is_system_admin' => false]);

        // Create admin
        $admin = $service->create('Test Admin', 'admin@test.com', 'password123');

        $admins = $service->getAll();

        $this->assertCount(1, $admins);
        $this->assertEquals($admin->id, $admins->first()->id);
    }

    public function test_user_model_has_system_admin_methods()
    {
        $user = User::factory()->create(['is_system_admin' => true]);

        $this->assertTrue($user->isSystemAdmin());
    }

    public function test_user_factory_can_create_system_admin()
    {
        $admin = User::factory()->systemAdmin()->create();

        $this->assertTrue($admin->is_system_admin);
        $this->assertTrue($admin->isSystemAdmin());
    }
} 