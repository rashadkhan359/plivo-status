<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class InvitationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_invitation_not_found_shows_error_page()
    {
        $response = $this->get('/invitation/invalid-token');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('auth/invitation-error')
                ->where('error', 'not_found')
        );
    }

    public function test_expired_invitation_shows_error_page()
    {
        $organization = Organization::factory()->create();
        $invitedBy = User::factory()->create();
        
        $invitation = Invitation::create([
            'organization_id' => $organization->id,
            'invited_by' => $invitedBy->id,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'role' => 'member',
            'token' => 'test-token',
            'expires_at' => now()->subDay(), // Expired yesterday
        ]);

        $response = $this->get("/invitation/{$invitation->token}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('auth/invitation-error')
                ->where('error', 'expired')
                ->has('invitation.organization.name')
                ->has('invitation.invited_by.name')
        );
    }

    public function test_accepted_invitation_shows_error_page()
    {
        $organization = Organization::factory()->create();
        $invitedBy = User::factory()->create();
        
        $invitation = Invitation::create([
            'organization_id' => $organization->id,
            'invited_by' => $invitedBy->id,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'role' => 'member',
            'token' => 'test-token',
            'expires_at' => now()->addDay(),
            'accepted_at' => now(), // Already accepted
        ]);

        $response = $this->get("/invitation/{$invitation->token}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('auth/invitation-error')
                ->where('error', 'already_accepted')
                ->has('invitation.organization.name')
                ->has('invitation.invited_by.name')
        );
    }

    public function test_valid_invitation_shows_accept_page()
    {
        $organization = Organization::factory()->create();
        $invitedBy = User::factory()->create();
        
        $invitation = Invitation::create([
            'organization_id' => $organization->id,
            'invited_by' => $invitedBy->id,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'role' => 'member',
            'token' => 'test-token',
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->get("/invitation/{$invitation->token}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('auth/accept-invitation')
                ->has('invitation')
        );
    }
} 