<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\ServiceStatusChanged;
use App\Models\Service;
use App\Models\Organization;

class TestBroadcasting extends Command
{
    protected $signature = 'broadcasting:test';
    protected $description = 'Test broadcasting configuration and send a test event';

    public function handle()
    {
        $this->info('Testing broadcasting configuration...');
        
        // Check broadcasting config
        $this->info('Broadcasting default: ' . config('broadcasting.default'));
        $this->info('Pusher key: ' . config('broadcasting.connections.pusher.key'));
        $this->info('Pusher cluster: ' . config('broadcasting.connections.pusher.options.cluster'));
        $this->info('Pusher useTLS: ' . (config('broadcasting.connections.pusher.options.useTLS') ? 'true' : 'false'));
        
        // Try to find a service to test with
        $service = Service::with('organization')->first();
        
        if (!$service) {
            $this->error('No services found. Please create a service first.');
            return 1;
        }
        
        $this->info('Testing with service: ' . $service->name);
        $this->info('Organization: ' . $service->organization->name);
        
        try {
            // Broadcast a test event
            broadcast(new ServiceStatusChanged($service));
            $this->info('✅ Test event broadcasted successfully!');
            $this->info('Check your browser console and Laravel logs for any errors.');
        } catch (\Exception $e) {
            $this->error('❌ Broadcasting failed: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
