<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

// Private channel for organization (dashboard, management)
Broadcast::channel('organization.{orgId}', function ($user, $orgId) {
    return $user->organization_id == $orgId;
});

// Public channel for status page (no auth required)
Broadcast::channel('status.{orgSlug}', function ($user, $orgSlug) {
    // Log the authorization attempt for debugging
    Log::info('Public channel authorization attempt', [
        'channel' => 'status.' . $orgSlug,
        'user' => $user ? $user->id : 'guest',
        'orgSlug' => $orgSlug,
    ]);
    
    // Always authorize public channels
    return true;
}); 