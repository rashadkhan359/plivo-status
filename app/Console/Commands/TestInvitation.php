<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\TeamInvitation;
use Illuminate\Console\Command;

class TestInvitation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:invitation {email} {--organization=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the invitation system by creating an invitation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $organizationId = $this->option('organization');

        $organization = Organization::find($organizationId);
        if (!$organization) {
            $this->error("Organization with ID {$organizationId} not found.");
            return 1;
        }

        $admin = $organization->users()->wherePivot('role', 'admin')->first();
        if (!$admin) {
            $this->error("No admin user found in organization {$organization->name}.");
            return 1;
        }

        // Create invitation
        $invitation = Invitation::createInvitation([
            'organization_id' => $organization->id,
            'invited_by' => $admin->id,
            'email' => $email,
            'name' => 'Test User',
            'role' => 'member',
            'message' => 'This is a test invitation from the command line.',
        ]);

        $this->info("Invitation created successfully!");
        $this->info("Token: {$invitation->token}");
        $this->info("Accept URL: " . route('invitation.show', $invitation->token));
        $this->info("Expires: {$invitation->expires_at}");

        // Send notification
        \Illuminate\Support\Facades\Notification::route('mail', $email)
            ->notify(new TeamInvitation($invitation));

        $this->info("Invitation email sent to {$email}");

        return 0;
    }
}
